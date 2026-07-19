**Purpose**: AI's persistent knowledge base for project context and learnings
**Last Updated**: [Auto-updated by AI]

## Memory Maintenance Guidelines

### Structure Standards

- Entry Format: ### [ID] [Title (YYYY-MM-DD)] ✅ STATUS
- Required Fields: Date, Challenge/Decision, Solution, Key Learning
- Length Limit: 3-6 lines per entry (excluding sub-bullets)
- Status Indicators: ✅ COMPLETE, ⚠️ PARTIAL, ❌ BLOCKED

### Content Guidelines

- Focus: Architecture decisions, critical bugs, security fixes, major technical challenges
- Exclude: Routine features, minor bug fixes, documentation updates
- Learning: Each entry must include actionable learning or decision rationale
- Redundancy: Remove duplicate information, consolidate similar issues

### File Management

- Archive Trigger: When file exceeds 500 lines or 6 months old
- Archive Format: `memory-YYYY-MM.md` (e.g., `memory-2025-01.md`)
- New File: Start fresh with current date and carry forward only active decisions

---

## Project Memory Entries

### [013] Equipment bulk RFU/B/D update (2026-07-16) ✅ COMPLETE
- Legacy parity: index buttons open multi-select modals; POST updates `is_rfu` for Active units only (`Unitstatus` name = Active, not hardcoded id).
- B/D is derived UI state (`is_rfu = false` on Active); shared tag helper at `resources/js/utils/equipmentStatus.jsx`.

### [012] Equipment identity fields from legacy (2026-07-16) ✅ COMPLETE
- Added to `equipment`: serial_no, chasis_no, engine_model, machine_no, nomor_polisi, bahan_bakar, warna, capacity, remarks.
- Migrator copies them + `is_rfu`; existing 989 rows backfilled (960 serial, 877 engine, 871 machine).
- Skipped empty legacy cols: unit_pic, cart_flag, assign_to.

### [011] Duplicate PAYREQ_API_URL in .env (2026-07-16) ✅ COMPLETE
- **Symptom**: Equipment Payreq tab showed "not configured" despite URL set.
- **Cause**: `.env` had `PAYREQ_API_URL` twice; empty second entry overrode the real URL.
- **Fix**: Keep a single `PAYREQ_API_URL=http://192.168.32.17/payreq-x-v3`; run `php artisan config:clear` after .env edits.

### [010] Legacy DB migration executed (2026-07-16) ✅ COMPLETE
- **Source**: `arkfleet_db` via `LEGACY_DB_*` env; backup `storage/backups/arkfleet_next_before_legacy_20260716.sql`.
- **Command**: `php artisan legacy:migrate --execute --fresh --force` — truncates equipment/master tables, upserts projects/departments/users.
- **Results**: 989 equipment, 546/555 documents (9 skipped — missing FK), 11 active users + roles; manufactures/unit_models deduped by name (129→127, 396→387).
- **Gaps**: No IPA history (`movings`); equipment financial fields null; `herry`/`rizky` had no legacy roles.

### [009] Phase 6 seeders, legacy migration draft, UAT gate (2026-07-16) ✅ COMPLETE
- **Seed**: `DemoDataSeeder` adds `DT-002`, expiring STNK on `EX-001`; `DatabaseSeeder` chains all demo seeders for fresh install.
- **Migration**: `legacy:migrate` dry-run default; `LegacyMigrationService` + `legacy` DB connection; mappings in `config/legacy_migration.php`.
- **UAT gate**: `SapPostingGate` requires `SAP_POSTING_UAT_SIGNED_OFF` + module flags; checklist `docs/UAT-checklist.md`; tests in `FreshInstallTest`, `PostingIdempotencyTest`.

### [009] Movings/IPA redesign (2026-07-19) ✅ COMPLETE
- **Flow**: DRAFT header form → add-equipment cart (scoped by `ipa_transfer_id`) → submit → optional approve; index is filterable ProTable (no cart).
- **Schema**: `ipa_transfers` gains `ipa_no`, `ipa_date`, tujuan/CC, status, approval; `cart_items.ipa_transfer_id`; `projects.bowheer`/`location` for PDF Dari/Tujuan.
- **PDF**: `resources/views/pdf/ipa-transfer.blade.php` — legacy letterhead layout via DomPDF table HTML; Indonesian date uses `->locale('id')->translatedFormat()`.
- **Routes**: `/movings/{id}/show`, `/movings/{id}/pdf`, `/movings/{id}/equipment` (old `/movings/transfers/*` removed).
- **Run**: `php artisan migrate` after deploy; `projects.bowheer`/`location` UI still manual/DB until Projects master edit is built.

### [008] Phase 5 REST API + AI NLQ (2026-07-16) ✅ COMPLETE
- **API**: Sanctum `/api/v1` for equipment, projects, fixed-assets, depreciation; `api:read` token ability; 60 req/min throttle; Settings → API Keys UI.
- **NLQ**: `OpenRouterClient` + `NlqQueryService` with allowlisted schema catalog; `/reports/ai-nlq` renders guarded read-only results in ProTable.
- **Config**: `OPENROUTER_API_KEY`, `OPENROUTER_MODEL`, `config/nlq.php`.

### [007] Phase 4 loan administration + AP posting (2026-07-16) ✅ COMPLETE
- **Built**: `loans`/`loan_installments`/`loan_documents`; PDF upload + `LoanPdfParserService` (smalot/pdfparser); confirm → post AP Invoice (principal `22201017` + interest `71201004`, tax `B100`) + Outgoing Payment via `PostingService`.
- **Guards**: Idempotency keys per installment; schedule locks after first successful AP post; posting requires `SAP_POSTING_UAT_SIGNED_OFF` + `SAP_LOAN_POSTING_ENABLED`.
- **Seed**: Demo loan `LN-2024-001` with vendor `V-LOAN-DEMO` and 3 draft installments.

### [006] Phase 3 fixed assets + dual-book depreciation (2026-07-16) ✅ COMPLETE
- **Built**: `asset_classes`/`fixed_assets`/`depreciation_runs`/`depreciation_entries`/`asset_disposals`; DepreciationCalculator (SL, DB, SYD, UoP) for book + tax books; `depreciation:run` + UI; disposal + deferred-tax report.
- **SAP**: `DepreciationPostingService` preview → confirm → `PostingService` journal post; requires UAT sign-off + `SAP_DEPRECIATION_POSTING_ENABLED`.
- **Seed**: `EX-001` capitalized as sample fixed asset (MP-K2 class, Rp 1.2B).

### [005] Phase 2 operational parity (2026-07-16) ✅ COMPLETE
- **Built**: Per-user `cart_items` IPA flow (`IpaTransferService`), DomPDF transfer doc, Documents CRUD/extend with file upload, reports (expiring/IPA/active) + Excel/PDF exports, dashboard expiry widgets.
- **Routes**: `/movings`, `/documents`, `/reports`; sample equipment `EX-001` + document type seeder for UAT.
- **Tests**: `tests/Feature/OperationsTest.php` — cart submit, document extend, dashboard stats.

### [004] Phase 1 masters + SAP sync live (2026-07-16) ✅ COMPLETE
- **Synced from SAP**: 22 projects, 27 departments (ProfitCenters), 2406 business partners.
- **Key fix**: BP `CardType` maps `cCustomer`/`cSupplier` → C/S; active = `Valid` and not `Frozen` (not `Active` OData filter).
- **UI**: ProTable pages at `/masters/projects`, `/masters/departments`, `/masters/business-partners`, `/equipment`; sync preserves `is_selectable`.

- **Built**: SapService singleton + PostingService scaffold, sap_sync_runs/sap_posting_logs tables, sap:ping command, ProLayout shell with permission-gated menu.
- **RBAC**: Seeded view/sync/manage-visibility/sap.post; roles Admin/Accountant/Manager (+ legacy admin/manager/user).
- **Packages**: laravel/sanctum, maatwebsite/excel, barryvdh/laravel-dompdf, @ant-design/pro-components (antd pinned to v5).
- **Verify**: `php artisan sap:ping` (needs SAP_* env); login as admin@arkfleet.local / password.

- **Decision**: Merge SAP B1 accounting support into ARKFleet v2 rebuild as first-class modules (not a separate app).
- **Adds**: SAP master sync (Projects/Departments/BPs), loan admin (PDF→parse→review→AP Invoice), SAP posting via PostingService, depreciation journals to SAP.
- **Keeps**: Laravel 12, AntD 5 + Pro, Sanctum API, AI NLQ, dual-book PSAK/UU PPh depreciation.
- **Plan**: `docs/ARKFleet-Rebuild-Plan.md` — 7 phases (P0–P6); close SAP field workshop blockers before P3/P4 build.

- **Decision**: Target stack is Laravel + Inertia.js + React.js + Ant Design + MySQL (not Next.js/Prisma/NextAuth).
- **Why**: Matches existing SAP B1 Service Layer docs (`SapService`, CookieJar, sync services, queues/scheduler).
- **PRD**: `docs/PRD-AccountingOne-Support.md` v1.1 — approve Section 8 recommendations before action plan.
