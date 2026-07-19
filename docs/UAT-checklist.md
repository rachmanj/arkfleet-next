# ARKFleet v2 — UAT Checklist

**Purpose**: Sign-off checklist before enabling SAP financial posting in production  
**Last Updated**: 2026-07-16

## Prerequisites

- [ ] Fresh install: `composer install`, `npm install`, `php artisan migrate`, `php artisan db:seed`, `npm run build`
- [ ] Login works: `admin@arkfleet.local` / `password`, `accountant@arkfleet.local` / `password`
- [ ] SAP connectivity: `php artisan sap:ping` succeeds against **non-production** company DB
- [ ] `SAP_VERIFY_SSL` set appropriately for environment

## Phase 1 — Masters & SAP sync

- [ ] Projects sync populates rows; `is_selectable` preserved after re-sync
- [ ] Departments sync populates rows; visibility toggle works
- [ ] Business partners sync completes; vendor `CardType = S` available for loans
- [ ] Equipment CRUD with financial fields

## Phase 2 — Operations

- [ ] IPA cart: add equipment, submit transfer, PDF downloads
- [ ] Documents: create, extend expiry, dashboard expiry alert shows
- [ ] Reports: expiring documents, IPA summary, active equipment — Excel/PDF export

## Phase 3 — Fixed assets & depreciation

- [ ] Capitalize equipment as fixed asset
- [ ] `php artisan depreciation:run {year} {month}` creates idempotent entries
- [ ] Deferred tax report loads
- [ ] Asset disposal records gain/loss

## Phase 4 — Loans

- [ ] Create loan with vendor CardCode
- [ ] Upload PDF (or manual schedule); confirm draft installments
- [ ] Confirm installment; edit GL/tax overrides
- [ ] **Staging only**: post AP Invoice — DocEntry/DocNum stored; re-post refused
- [ ] **Staging only**: outgoing payment — status `paid`; re-post refused
- [ ] Schedule locks after first successful AP post

## Phase 5 — API & AI

- [ ] Create API token at `/settings/api-keys`; `GET /api/v1/equipment` returns data
- [ ] NLQ page loads; with `OPENROUTER_API_KEY`, sample question returns table (read-only)

## SAP posting enablement (production gate)

Posting requires **both**:

1. `SAP_POSTING_UAT_SIGNED_OFF=true` — set only after this checklist passes
2. Per-module flag:
   - `SAP_LOAN_POSTING_ENABLED=true`
   - `SAP_DEPRECIATION_POSTING_ENABLED=true`

Until UAT sign-off, posting buttons remain disabled in UI and services throw if called directly.

## Idempotency verification

- [ ] Duplicate AP Invoice post for same installment returns existing log (no second SAP doc)
- [ ] Duplicate depreciation journal post for same run returns existing log
- [ ] Failed posts logged in `/sap/posting-logs` with error payload; retry allowed after fix

## Legacy migration (when ready)

- [ ] `php artisan legacy:migrate` dry-run reviewed
- [ ] Legacy DB snapshot taken
- [ ] `php artisan legacy:migrate --execute` on staging only
- [ ] Post-migration: capitalize fixed assets, re-sync SAP masters

## Sign-off

| Role | Name | Date | Notes |
|------|------|------|-------|
| Finance | | | SAP field workshop complete |
| IT | | | |
| Operations | | | |

After sign-off, set `SAP_POSTING_UAT_SIGNED_OFF=true` in production `.env` and enable module flags per rollout plan.
