**Purpose**: Record technical decisions and rationale for future reference
**Last Updated**: [Auto-updated by AI]

# Technical Decision Records

## Decision Template

Decision: [Title] - [YYYY-MM-DD]

**Context**: [What situation led to this decision?]

**Options Considered**:

1. **Option A**: [Description]
   - ✅ Pros: [Benefits]
   - ❌ Cons: [Drawbacks]
2. **Option B**: [Description]
   - ✅ Pros: [Benefits]
   - ❌ Cons: [Drawbacks]

**Decision**: [What we chose]

**Rationale**: [Why we chose this option]

**Implementation**: [How this affects the codebase]

**Review Date**: [When to revisit this decision]

---

## Recent Decisions

Decision: Legacy DB migration strategy — 2026-07-16

**Context**: Legacy `arkfleet_db` (Laravel 10) schema differs from v2: `equipments` vs `equipment`, `documents` vs `equipment_documents`, projects use `project_code`/`bowheer`/`isActive`, departments lack SAP fields.

**Options Considered**:

1. **One-shot SQL dump/import**: Fast but breaks FK remapping and column transforms.
2. **Config-driven Laravel importer with per-entity handlers** (chosen).
   - ✅ Pros: ID remapping, upsert merge with SAP-synced masters, role mapping, dry-run, per-entity stats.
   - ❌ Cons: More code to maintain; IPA history out of scope.

**Decision**: `LegacyMigrationService` with ordered entities (masters → projects/departments → users/roles → equipment → documents); upsert by natural keys; `--fresh` truncates equipment/master tables only.

**Implementation**: `config/legacy_migration.php`, `php artisan legacy:migrate`; role map `superadmin`→`Admin`; document table `documents`→`equipment_documents`; legacy `model_no`→v2 `unit_models.name`.

**Review Date**: Before production cutover; re-run if legacy DB receives new data.

---

Decision: ARKFleet v2 + AccountingOne PRD merge — 2026-07-16

**Context**: AccountingOne Support PRD defined SAP B1 sync, loans, and posting requirements on the same stack (Laravel + Inertia + React + AntD) as the ARKFleet v2 rebuild.

**Options Considered**:

1. **Separate AccountingOne app**: Independent codebase for finance modules.
   - ✅ Pros: Clear domain boundary.
   - ❌ Cons: Duplicate stack, auth, SAP layer; shared masters (Projects/Departments) would drift.
2. **Fold into ARKFleet v2 as first-class modules** (chosen).
   - ✅ Pros: One SAP integration layer, one RBAC, shared masters and equipment→fixed-asset path.
   - ❌ Cons: Larger single codebase; phased delivery required.

**Decision**: Fold PRD features into ARKFleet v2; keep ARKFleet stack choices (Laravel 12, AntD Pro, Sanctum, AI NLQ, dual-book depreciation).

**Rationale**: Same target stack; Projects/Departments/Equipment already overlap; `SapService` + `PostingService` reusable across masters, loans, and depreciation.

**Implementation**: Updated `docs/ARKFleet-Rebuild-Plan.md` with phased action plan P0–P6; mirrored tasks in `docs/todo.md`. Loan AP Invoice: Service-type, principal G/L 22201017 + interest G/L 71201004; hybrid PDF parse with accountant confirm.

**Review Date**: After SAP field workshop (series, tax, dimensions) and UAT sign-off.

---

Decision: Stack decision for AccountingOne Support — 2026-07-15

**Context**: Brainstorming produced multiple stack options; SAP B1 docs were written for Laravel patterns.

**Decision**: Laravel + Inertia.js + React.js + Ant Design + MySQL.

**Rationale**: Direct reuse of `SapService`, CookieJar session handling, sync services, queues/scheduler from existing SAP docs.

**Implementation**: Captured in `docs/PRD-AccountingOne-Support.md` v1.1; now merged into ARKFleet rebuild plan.

**Review Date**: N/A — adopted.

---

Decision: SAP posting UAT feature gate — 2026-07-16

**Context**: Loan AP Invoice and depreciation journal posting touch SAP financial documents. Production enablement must wait for finance UAT and SAP field workshop validation.

**Options Considered**:

1. **Per-module env flags only** (`SAP_LOAN_POSTING_ENABLED`, etc.).
   - ✅ Pros: Simple toggle.
   - ❌ Cons: Easy to enable accidentally in production without formal sign-off.
2. **Master UAT gate + per-module flags** (chosen).
   - ✅ Pros: Two-step enablement; UI and services share `SapPostingGate`; explicit audit trail via checklist.
   - ❌ Cons: Extra env var to manage.

**Decision**: Require `SAP_POSTING_UAT_SIGNED_OFF=true` before any module posting flag takes effect.

**Implementation**: `App\Support\SapPostingGate`; `config/sap.php` `posting.uat_signed_off`; shared `sapPosting` status in `HandleInertiaRequests` for users with `sap.post`; checklist at `docs/UAT-checklist.md`.

**Review Date**: After first production posting rollout.

