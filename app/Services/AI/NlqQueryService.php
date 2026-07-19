<?php

namespace App\Services\AI;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class NlqQueryService
{
    public function __construct(
        protected OpenRouterClient $openRouter,
    ) {}

    public function catalog(): array
    {
        $sources = config('nlq.sources', []);

        return collect($sources)->map(fn ($meta, $key) => [
            'source' => $key,
            'label' => $meta['label'],
            'columns' => $meta['columns'],
            'filters' => $meta['filters'] ?? [],
        ])->values()->all();
    }

    public function buildQuerySpec(string $question): array
    {
        if (! config('nlq.enabled')) {
            throw new RuntimeException('Natural-language queries are disabled.');
        }

        $catalogJson = json_encode($this->catalog(), JSON_PRETTY_PRINT);

        $content = $this->openRouter->chat([
            [
                'role' => 'system',
                'content' => <<<PROMPT
You translate fleet/accounting questions into a JSON query spec for a read-only reporting API.
Return ONLY valid JSON with this shape:
{
  "source": "equipment|projects|fixed_assets|depreciation_entries|loan_installments",
  "columns": ["column1", "column2"],
  "filters": [{"column":"col","operator":"=","value":"x"}],
  "order_by": {"column":"col","direction":"asc|desc"},
  "limit": 50
}
Rules:
- Use only sources/columns/filters from the catalog below.
- Operators allowed: =, !=, >, >=, <, <=, like
- Never invent tables or SQL.
- Default limit 50, max {$this->maxRows()}.
Catalog:
{$catalogJson}
PROMPT,
            ],
            [
                'role' => 'user',
                'content' => $question,
            ],
        ]);

        $spec = json_decode($content, true);

        if (! is_array($spec)) {
            throw new RuntimeException('AI returned invalid JSON.');
        }

        return $this->validateSpec($spec);
    }

    public function execute(array $spec): array
    {
        $validated = $this->validateSpec($spec);
        $source = config("nlq.sources.{$validated['source']}");
        $modelClass = $source['model'];

        /** @var Builder $query */
        $query = $modelClass::query();

        if (! empty($source['relations'])) {
            $query->with(array_keys($source['relations']));
        }

        foreach ($validated['filters'] as $filter) {
            $this->applyFilter($query, $filter);
        }

        if ($validated['order_by']) {
            $query->orderBy(
                $validated['order_by']['column'],
                $validated['order_by']['direction'],
            );
        }

        $rows = $query
            ->limit($validated['limit'])
            ->get()
            ->map(function ($row) use ($validated, $source) {
                $array = $row->only($validated['columns']);

                if (! empty($source['relations'])) {
                    foreach ($source['relations'] as $relation => $cols) {
                        if ($row->relationLoaded($relation) && $row->{$relation}) {
                            foreach ($cols as $col) {
                                $array["{$relation}.{$col}"] = $row->{$relation}->{$col};
                            }
                        }
                    }
                }

                return $array;
            })
            ->values()
            ->all();

        return [
            'spec' => $validated,
            'columns' => array_keys($rows[0] ?? array_fill_keys($validated['columns'], null)),
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    public function ask(string $question): array
    {
        $spec = $this->buildQuerySpec($question);

        return $this->execute($spec);
    }

    public function validateSpec(array $spec): array
    {
        $sourceKey = $spec['source'] ?? null;
        $source = config("nlq.sources.{$sourceKey}");

        if (! $source) {
            throw new InvalidArgumentException("Unknown source: {$sourceKey}");
        }

        $allowedColumns = $source['columns'];
        $allowedFilters = $source['filters'] ?? [];
        $relationColumns = [];

        foreach ($source['relations'] ?? [] as $relation => $cols) {
            foreach ($cols as $col) {
                $relationColumns[] = "{$relation}.{$col}";
            }
        }

        $columns = $spec['columns'] ?? $allowedColumns;
        $columns = array_values(array_intersect($columns, array_merge($allowedColumns, $relationColumns)));

        if ($columns === []) {
            $columns = $allowedColumns;
        }

        $filters = [];
        foreach ($spec['filters'] ?? [] as $filter) {
            $column = $filter['column'] ?? null;
            $operator = strtolower($filter['operator'] ?? '=');
            $value = $filter['value'] ?? null;

            if (! in_array($column, $allowedFilters, true)) {
                continue;
            }

            if (! in_array($operator, ['=', '!=', '>', '>=', '<', '<=', 'like'], true)) {
                continue;
            }

            $filters[] = compact('column', 'operator', 'value');
        }

        $orderBy = null;
        if (! empty($spec['order_by']['column']) && in_array($spec['order_by']['column'], $allowedColumns, true)) {
            $direction = strtolower($spec['order_by']['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $orderBy = ['column' => $spec['order_by']['column'], 'direction' => $direction];
        }

        $limit = min(max(1, (int) ($spec['limit'] ?? 50)), $this->maxRows());

        return [
            'source' => $sourceKey,
            'columns' => $columns,
            'filters' => $filters,
            'order_by' => $orderBy,
            'limit' => $limit,
        ];
    }

    protected function applyFilter(Builder $query, array $filter): void
    {
        $column = $filter['column'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        if ($operator === 'like') {
            $query->where($column, 'like', '%'.$value.'%');

            return;
        }

        $query->where($column, $operator, $value);
    }

    protected function maxRows(): int
    {
        return (int) config('nlq.max_rows', 100);
    }
}
