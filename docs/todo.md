**Purpose**: Track current work and immediate priorities for ARKFleet v2 rebuild  
**Last Updated**: 2026-07-19

# Current Tasks

Aligned with [ARKFleet-Rebuild-Plan.md](./ARKFleet-Rebuild-Plan.md) phased implementation.

## Working On Now

- _(none — P0–P6 complete; legacy migration executed 2026-07-16)_

## Up Next (Phased)

- Post-UAT: enable `SAP_POSTING_UAT_SIGNED_OFF=true` and per-module posting flags after [UAT-checklist.md](./UAT-checklist.md) sign-off
- Post-migration: capitalize equipment → `fixed_assets` (financial fields still null on imported rows)
- Optional: migrate IPA history (`movings`/`moving_details`) if finance needs transfer audit trail in v2

## Blocked / Waiting

- `[blocked] P2: SAP depreciation journal + loan AP Invoice field mapping [waiting: SAP field workshop — series, tax groups, dimensions, B100 semantics]`
- `[blocked] P2: Principal GL target — vendor-linked payable 22201017 vs loan-liability account [finance decision]`
- `[blocked] P2: Consolidated vs per-asset depreciation journal [finance decision]`

## Recently Completed

- `[done] Movings/IPA redesign — DRAFT→SUBMITTED→APPROVED lifecycle, filterable index, legacy PDF layout, cart scoped per IPA (completed: 2026-07-19)`

- `[done] Equipment bulk RFU/B/D update — Update RFU Units / Update B/D Units modals on index; Active units only; Status column + filter (completed: 2026-07-16)`

- `[done] Equipment identity fields parity with legacy — serial/engine/machine/plate/fuel/color/capacity/remarks + migrator backfill (completed: 2026-07-16)`

- `[done] P1: Legacy DB migration executed — arkfleet_db → arkfleet_next via legacy:migrate --execute --fresh; 989 equipment, 546 documents, 11 users + roles (completed: 2026-07-16)`
- `[done] P1: Phase 6 data migration, docs & UAT — DemoDataSeeder, legacy:migrate dry-run, SapPostingGate (UAT + module flags), UAT checklist, idempotency tests (completed: 2026-07-16)`
- `[done] P1: Phase 5 REST API + AI NLQ — Sanctum /api/v1, API key UI, OpenRouter guarded NLQ at /reports/ai-nlq (completed: 2026-07-16)`
- `[done] P1: Phase 4 loan administration — loans/installments/documents CRUD, PDF parse, AP Invoice + Outgoing Payment via PostingService (feature-gated) (completed: 2026-07-16)`
- `[done] P1: Phase 3 fixed assets + dual-book depreciation — asset_classes/fixed_assets CRUD, DepreciationCalculator (SL/DB/SYD/UoP), runs/entries, disposal, deferred-tax report, SAP journal scaffold (feature-gated) (completed: 2026-07-16)`
- `[done] P1: Phase 2 operational parity — Movings/IPA cart + PDF, Documents + expiry/extend, reports (Excel/PDF), dashboard alerts (completed: 2026-07-16)`
- `[done] P1: Phase 1 masters + SAP sync — Projects/Departments/BP sync, Equipment CRUD with financial fields, ProTable UIs (completed: 2026-07-16)`
- `[done] P0: Phase 0 foundation — Sanctum/excel/dompdf, AntD 5 + ProLayout, RBAC permissions, SapService + posting logs, sap:ping (completed: 2026-07-16)`

## Quick Notes

- Live SAP sync verified: 22 projects, 27 departments, 2406 business partners from `LAB_SBO_Temp_20052026`.
- Artisan: `sap:sync-projects`, `sap:sync-departments`, `sap:sync-business-partners`, `legacy:migrate` (dry-run default; `--execute --fresh --force` for full import).
- Legacy migration (2026-07-16): `arkfleet_db` → `arkfleet_next`; backup at `storage/backups/arkfleet_next_before_legacy_20260716.sql`.
- UI routes: `/masters/*`, `/equipment`, `/movings`, `/documents`, `/reports`, `/reports/ai-nlq`, `/fixed-assets`, `/depreciation`, `/loans`, `/settings/api-keys`, `/sap/sync`.
- API: `GET /api/v1/*` with `Authorization: Bearer {token}` (ability `api:read`), 60 req/min.
- SAP posting gated by `SAP_POSTING_UAT_SIGNED_OFF` **and** `SAP_LOAN_POSTING_ENABLED` / `SAP_DEPRECIATION_POSTING_ENABLED`.
- UAT checklist: `docs/UAT-checklist.md`.
- Sample data: `EX-001`, `DT-002`, expiring STNK on EX-001, demo loan `LN-2024-001`.
