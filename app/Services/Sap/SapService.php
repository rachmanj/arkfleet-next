<?php

namespace App\Services\Sap;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SapService
{
    protected Client $client;

    protected CookieJar $cookieJar;

    protected array $config;

    protected bool $loggedIn = false;

    public function __construct()
    {
        $this->config = config('services.sap');
        $this->cookieJar = new CookieJar;

        $this->client = new Client([
            'base_uri' => rtrim($this->config['base_url'], '/').'/',
            'cookies' => $this->cookieJar,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'verify' => $this->config['verify_ssl'],
            'timeout' => (int) $this->config['timeout'],
        ]);
    }

    public function isConfigured(): bool
    {
        return filled($this->config['base_url'])
            && filled($this->config['company_db'])
            && filled($this->config['username'])
            && filled($this->config['password']);
    }

    public function ensureSession(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('SAP B1 Service Layer is not configured. Set SAP_* environment variables.');
        }

        if ($this->loggedIn && $this->cookieJar->count() > 0) {
            return;
        }

        $this->login();
    }

    public function login(): bool
    {
        $response = $this->client->post('Login', [
            'json' => [
                'CompanyDB' => $this->config['company_db'],
                'UserName' => $this->config['username'],
                'Password' => $this->config['password'],
            ],
        ]);

        $this->loggedIn = $response->getStatusCode() === 200;

        return $this->loggedIn;
    }

    public function logout(): void
    {
        try {
            if ($this->cookieJar->count() > 0) {
                $this->client->post('Logout');
            }
        } catch (GuzzleException $exception) {
            Log::warning('SAP logout failed', ['message' => $exception->getMessage()]);
        } finally {
            $this->loggedIn = false;
        }
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, ['json' => $payload]);
    }

    public function patch(string $path, array $payload = []): array
    {
        return $this->request('PATCH', $path, ['json' => $payload]);
    }

    public function hasActiveSession(): bool
    {
        return $this->loggedIn && $this->cookieJar->count() > 0;
    }

    public function sessionCookieCount(): int
    {
        return $this->cookieJar->count();
    }

    public function fetchAll(string $entity, array $query = [], int $pageSize = 100): array
    {
        $results = [];
        $skip = 0;

        do {
            $response = $this->get($entity, array_merge($query, [
                '$top' => $pageSize,
                '$skip' => $skip,
            ]));

            $batch = $response['value'] ?? [];
            $results = array_merge($results, $batch);
            $skip += $pageSize;
        } while (count($batch) === $pageSize);

        return $results;
    }

    public function getProjects(): array
    {
        return $this->fetchAll('Projects', [
            '$select' => 'Code,Name,Active,ValidFrom,ValidTo',
        ]);
    }

    public function getProfitCenters(): array
    {
        return $this->fetchAll('ProfitCenters', [
            '$select' => 'CenterCode,CenterName,Active,InWhichDimension',
        ]);
    }

    public function getBusinessPartners(array $filters = []): array
    {
        $query = [
            '$select' => 'CardCode,CardName,CardType,Valid,Frozen,FederalTaxID,Currency,CreditLimit,CurrentAccountBalance',
        ];

        if (! empty($filters['card_type'])) {
            $query['$filter'] = "CardType eq '{$filters['card_type']}'";
        }

        $partners = $this->fetchAll('BusinessPartners', $query);

        if (! empty($filters['active_only'])) {
            $partners = array_values(array_filter($partners, function (array $partner) {
                $valid = self::sapYesNo($partner['Valid'] ?? 'tYES');
                $frozen = self::sapYesNo($partner['Frozen'] ?? 'tNO');

                return $valid && ! $frozen;
            }));
        }

        return $partners;
    }

    public static function sapYesNo(mixed $value): bool
    {
        return in_array($value, ['tYES', 'Y', 'y', true, 1, '1'], true);
    }

    public static function mapCardType(mixed $value): string
    {
        $normalized = strtolower((string) $value);

        return match (true) {
            str_contains($normalized, 'supplier') || $normalized === 's' => 'S',
            str_contains($normalized, 'lead') || $normalized === 'l' => 'L',
            default => 'C',
        };
    }

    protected function request(string $method, string $path, array $options = [], bool $retried = false): array
    {
        $this->ensureSession();

        try {
            $response = $this->client->request($method, ltrim($path, '/'), $options);
            $body = (string) $response->getBody();

            return $body !== '' ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : [];
        } catch (GuzzleException $exception) {
            if (! $retried && $this->isUnauthorized($exception)) {
                $this->loggedIn = false;
                $this->login();

                return $this->request($method, $path, $options, true);
            }

            throw $exception;
        }
    }

    protected function isUnauthorized(GuzzleException $exception): bool
    {
        if (! method_exists($exception, 'getResponse') || $exception->getResponse() === null) {
            return false;
        }

        return $exception->getResponse()->getStatusCode() === 401;
    }
}
