# Product Requirements Document (PRD): AccountingOne Support

**Document status:** Draft — recommendations ready; action plan pending approval  
**Derived from:** [brain-storming.md](./brain-storming.md)  
**References:** [SAP_B1_BUSINESS_PARTNERS_SYNC_RECOMMENDATION.md](./SAP_B1_BUSINESS_PARTNERS_SYNC_RECOMMENDATION.md), [SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md](./SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md), [SAP-B1-SESSION-MANAGEMENT.md](./SAP-B1-SESSION-MANAGEMENT.md)

---

## 1. Executive summary

**AccountingOne Support** is an enterprise accounting support application that keeps operational master data aligned with SAP Business One (SAP B1), supports loan lifecycle workflows with optional posting to SAP, and manages fixed assets with depreciation reporting and journal integration into SAP B1.

The product targets finance operations teams who need a consistent UI (dark/light), role-based access, and reliable SAP Service Layer connectivity under cookie-based sessions.

**Target stack:** Laravel (backend), Inertia.js (SPA bridge), React.js (UI), Ant Design (component library), MySQL (database).

---

## 2. Vision & goals

| Goal | Description |
|------|-------------|
| **Single source of truth for SAP-aligned masters** | Projects, departments (cost centers / profit centers), and business partners mirror SAP where synced; local extensions remain explicit. |
| **Operational efficiency** | Reduce manual CardCode/project/department errors via sync, validation, and admin visibility controls. |
| **Controlled financial posting** | Loan installments and asset depreciation can be pushed to SAP as AP invoices, outgoing payments, and journals where configured. |
| **Secure, auditable access** | RBAC governs who can view data, trigger syncs, toggle visibility, and submit documents to SAP. |
| **Reusable SAP integration layer** | One `SapService` + sync/posting pattern reusable across masters and transactional modules. |

---

## 3. Technology stack

| Layer | Choice | Rationale |
|-------|--------|-----------|
| **Backend** | Laravel 11+ | Aligns with existing SAP B1 docs (`SapService`, CookieJar, sync services, Artisan commands, queues). |
| **SPA bridge** | Inertia.js | Server-driven routing + React pages without a separate API-first SPA; forms, validation, and auth stay Laravel-native. |
| **Frontend** | React.js | Component model for Ant Design tables, forms, drawers, and theme switching. |
| **UI kit** | Ant Design (AntD) | Enterprise tables, filters, modals, forms; good fit for admin/finance screens. |
| **Database** | MySQL + Eloquent | Same persistence model as reference SAP sync docs; migrations/seeders first-class. |
| **Auth** | Laravel session auth (Inertia) | Cookie sessions work well with Inertia; password change + logout from navbar. |
| **RBAC** | Spatie Laravel Permission (recommended) | Permissions for view / sync / visibility / SAP post; roles for Admin, Accountant, Manager. |
| **Jobs / schedule** | Laravel Queue + Scheduler | Long SAP syncs and postings off the HTTP request; daily master sync. |

**Explicitly not in scope for this PRD:** Next.js, Prisma, NextAuth (superseded by the stack above).

---

## 4. Scope

### 4.1 In scope

- Master data synchronization with SAP B1: **Departments**, **Projects**, **Business Partners** (customers, vendors, leads as applicable).
- **Loan administration**: loan creation, installment schedules, SAP posting (AP Invoice per installment; Outgoing Payment for installment payments).
- **Assets & depreciation**: asset recording, depreciation and accumulated depreciation lists, depreciation journal submission to SAP B1.
- **Application foundation**: Laravel + Inertia + React + AntD + MySQL; session auth; RBAC.
- **UX**: dark/light themes (default dark), sidebar menu, top navbar with user dropdown (change password, logout).

### 4.2 Out of scope (unless later amended)

- Full ERP replacement for SAP B1.
- SAP UI customization or SAP client deployment.
- Detailed treasury/cash management beyond outgoing payment flows for loans.
- Building a separate public REST API for third parties (Inertia first; JSON APIs only where needed for DataTables/select search).

---

## 5. Personas & stakeholders

| Persona | Needs |
|---------|--------|
| **Finance admin** | Trigger syncs, manage master visibility (`is_selectable`), resolve mapping issues. |
| **Accountant / AP** | Valid business partners and loan/AP workflows; trustworthy SAP posting. |
| **Fixed asset accountant** | Asset registers, depreciation schedules, journal alignment with SAP. |
| **IT / integration owner** | Session stability, observability (`last_synced_at`, logs), safe migrations. |

---

## 6. Product modules & functional requirements

### 6.1 Departments (SAP B1 alignment)

**Intent:** Departments map to SAP organizational structures (profit centers / cost centers).

**Requirements:**

- Persist departments with stable IDs for FK usage; preserve existing `sap_code` when migrating from legacy data.
- Sync from SAP Service Layer (ProfitCenters / cost centers per integration plan): upsert by **`sap_code`**; update names/descriptions and `synced_at`; **do not overwrite** `is_selectable`, `parent_id`, or local acronym fields on update.
- Support `is_active`, `is_selectable`, `synced_at`, optional `parent_id` hierarchy, soft deletes where needed for safe rollout.
- Admin: list/filter, permission-gated sync, visibility toggle for dropdowns.

**Reference:** [SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md](./SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md)

---

### 6.2 Projects (SAP B1 alignment)

**Intent:** Projects remain **code-centric** where modules store project as string codes; SAP supplies authoritative names/metadata.

**Requirements:**

- Schema: `code`, `sap_code`, `name`, `description`, `is_active`, `is_selectable`, `synced_at`, soft deletes as needed.
- Migration: populate `name` from `code` where missing; set `sap_code` from `code` until SAP match refreshes — **preserve all historical string references**.
- Sync: match by `sap_code`; preserve `is_selectable` on updates.
- Admin: listing, sync, visibility toggle, last-sync indicators.
- Forms: constrain dropdowns to `selectable()` + `active()` scopes.

**Reference:** [SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md](./SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md)

---

### 6.3 Business partners (SAP B1 alignment)

**Intent:** Customers, vendors, and leads for invoices, payments, and `CardCode` validation.

**Requirements:**

- **Dual storage:** `sap_business_partners` (full SAP mirror + metadata JSON) linked to local customer/vendor tables via `code` ↔ `CardCode`.
- Sync full set or filtered (`CardType` C/S/L, active-only); chunked upserts; `last_synced_at`; correct SAP boolean (`tYES`/`tNO`) and decimal handling.
- Map core fields: `CardCode`, `CardName`, `CardType`, `Active`, `FederalTaxID`, contacts, addresses, currency, credit/balance, VAT, retain raw payload.
- UI: AntD Select/AutoComplete backed by synced data; validate before SAP submissions; optional credit-limit checks.
- Admin: filters (type, active), sync trigger, metadata inspection.

**Reference:** [SAP_B1_BUSINESS_PARTNERS_SYNC_RECOMMENDATION.md](./SAP_B1_BUSINESS_PARTNERS_SYNC_RECOMMENDATION.md)

---

### 6.4 Loan administration

**Requirements:**

- **Loan creation:** principal, terms, counterpart BP (validated `CardCode`), currency, dates, project/department dimensions as required by policy.
- **Installment schedule:** generate and display; edits only under locked business rules (define before build).
- **SAP posting:**
  - Installment → SAP **AP Invoice**.
  - Installment payment → SAP **Outgoing Payment**.
- **Idempotency & audit:** store SAP DocEntry/DocNum, status, error payload, user, timestamps per attempt; block duplicate posts for the same installment unless explicitly “retry after failure”.

---

### 6.5 Assets & depreciation

**Requirements:**

- **Asset recording:** capitalization date, basis, useful life, method; optional project/department/cost center dimensions.
- **Depreciation:** calculate and list depreciation + accumulated depreciation; exportable views.
- **SAP:** post depreciation **Journal Entries** via Service Layer with validation, clear period locking rules, and consistent session/error handling.

---

## 7. Non-functional requirements

### 7.1 Security & access

- Laravel session authentication with Inertia (login, logout, change password from navbar).
- RBAC: separate permissions for **view**, **sync**, **manage visibility**, and **SAP post** (loans / journals). Suggested starter roles: Admin (all), Accountant (view + sync + post), Manager (view).

### 7.2 UX & UI

- Themes: dark (default) and light; persist preference (user setting or localStorage + optional DB column).
- Layout: AntD `Layout` + sidebar + header; user dropdown with change password and logout.
- Tables: AntD Table with server-side pagination/filter where datasets are large (BPs, installments, assets).

### 7.3 SAP B1 Service Layer — session & reliability

- Cookie-based sessions (`B1SESSION`, `ROUTEID`) via Guzzle **CookieJar**; no bearer-token-only assumptions.
- On **401**: re-login and **retry** once (avoid infinite loops).
- Register **`SapService` as singleton** in the container so one PHP process reuses one SAP session where practical.
- Chunk large pulls; run heavy sync/post on **queues**; daily scheduler for master data.
- Log sync duration, counts (created/updated/failed), and SAP error bodies.

**Reference:** [SAP-B1-SESSION-MANAGEMENT.md](./SAP-B1-SESSION-MANAGEMENT.md)

### 7.4 Data & operations

- MySQL + Eloquent migrations; additive, data-safe changes; backups before production migrate.
- Observability: `synced_at` / `last_synced_at`; structured logs; optional `sap_sync_runs` / `sap_posting_logs` tables.

---

## 8. Recommendations (review before action plan)

These recommendations should be **accepted, rejected, or deferred** before writing the detailed action plan.

### 8.1 Architecture & stack

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R1 | **Laravel + Inertia + React + AntD** as the only frontend path (no parallel Blade admin + React SPA). | One UX model, one auth story, reuse of existing Laravel SAP patterns. | ☐ Approve |
| R2 | Keep SAP integration in **`app/Services`** (`SapService`, sync services, posting services); Controllers stay thin; Inertia pages receive props / flash only. | Matches existing docs; testable without UI. | ☐ Approve |
| R3 | Use **Spatie Permission** (or equivalent) from day one for masters + posting actions. | Avoid retrofit of ad-hoc role checks. | ☐ Approve |
| R4 | Prefer **TypeScript** for React/Inertia pages if the team can support it; otherwise JS with PropTypes/Zod form schemas. | Reduces Inertia prop mismatch bugs. | ☐ Decide |

### 8.2 Master data sync

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R5 | **Build Projects & Departments sync first (P0)**, then Business Partners (P1). | Smaller volume, unblocks loan/asset dimensions; BP sync is larger and already well-specified. | ☐ Approve |
| R6 | **Dual storage for BPs** (`sap_business_partners` + local customers/vendors); start with **manual link by code**, auto-create local rows later if needed. | Matches BP recommendation; avoids forcing every SAP lead into local tables. | ☐ Approve |
| R7 | **Preserve `is_selectable` on every sync update** for projects/departments. | Admins hide noise in dropdowns without fighting nightly sync. | ☐ Approve |
| R8 | **Manual sync button + scheduled daily sync** (e.g. 02:00), both permission-gated / `withoutOverlapping()`. | Ops can recover without waiting for night job. | ☐ Approve |
| R9 | Store **full SAP payload in `metadata` JSON** on mirror tables. | Forward-compatible when new SAP fields are needed. | ☐ Approve |
| R10 | Prefer **full sync first**; defer incremental/`UpdateDate` sync until volumes or runtime demand it. | Simpler correctness; BP sync of ~2k rows already proven feasible in reference app. | ☐ Approve |

### 8.3 SAP session & posting safety

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R11 | **Singleton `SapService`** + `ensureSession()`; never create a new client per loop iteration in a job. | Avoids SAP concurrent session exhaustion. | ☐ Approve |
| R12 | **Dedicated SAP technical user per environment** (DEV / UAT / PROD), never share PROD credentials with local. | Isolation and audit. | ☐ Approve |
| R13 | All SAP **writes** (AP Invoice, Outgoing Payment, Journal) go through a **PostingService** that: validates local state → posts → persists DocEntry/DocNum → marks local status; on failure stores error and leaves retryable state. | Prevents silent drift and double-posts. | ☐ Approve |
| R14 | Use **idempotency keys** (e.g. `loan_installment_id` + document type) stored before/after post; refuse re-post if success already recorded. | Critical for payments and journals. | ☐ Approve |
| R15 | Run sync and posting on **queues** with retries and dead-letter visibility in admin UI. | HTTP timeouts must not leave unclear SAP state. | ☐ Approve |

### 8.4 Loan module (product design)

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R16 | Model loans as **header + installments**; installments hold SAP AP Invoice link; payments hold Outgoing Payment link. | Clear 1:1 audit trail to SAP docs. | ☐ Approve |
| R17 | **Lock schedule** after first successful AP post (or after “confirm schedule”); only allow residual adjustments via explicit “amendment” flow. | Prevents schedule edits that invalidate posted docs. | ☐ Approve |
| R18 | Require **vendor-type BP** (`CardType = S`) for loan AP path unless finance confirms otherwise. | Aligns AP Invoice with SAP vendor. | ☐ Decide |
| R19 | Map dimensions: installment lines should carry **project** and/or **department/cost center** codes expected by the SAP chart. | Matches how other accounting apps post to B1. | ☐ Decide (GL mapping workshop) |
| R20 | Support **draft → posted** local status before SAP call so accountants can review the generated schedule. | Reduces bad posts. | ☐ Approve |

### 8.5 Assets & depreciation

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R21 | Keep **calculation in-app** first; SAP receives journals, not full FA submodule sync (unless FA is already live in B1 and must stay master). | Faster MVP; clarify with finance if SAP Fixed Assets is in use. | ☐ Decide |
| R22 | Depreciation run = **period batch** with preview → confirm → queue journal posts; one journal per asset or consolidated per period (finance choice). | Preview reduces wrong period posts. | ☐ Decide |
| R23 | Never post a second journal for the same asset+period without an explicit reversal path. | Same idempotency principle as loans. | ☐ Approve |

### 8.6 UI / UX (AntD + Inertia)

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R24 | Shared **AppLayout**: sidebar modules gated by `@can` / Inertia shared `auth.permissions`; AntD theme tokens for dark/light. | Consistent shell from day one. | ☐ Approve |
| R25 | Master data pages: Table + Sync button + filters; visibility as Switch with optimistic UI + server confirm. | Matches integration plan UX. | ☐ Approve |
| R26 | SAP-facing actions use **confirm Modal** + disable button while job pending; show last error from flash or polling job status. | Finance needs clear feedback. | ☐ Approve |
| R27 | Prefer AntD **ProComponents** only if needed; start with core AntD to avoid bundle bloat. | Keep stack lean. | ☐ Approve |

### 8.7 Delivery & quality

| # | Recommendation | Why | Decision |
|---|----------------|-----|----------|
| R28 | **Feature-flag or permission-gate** SAP posting in production until UAT sign-off. | Safe rollout. | ☐ Approve |
| R29 | Staging must use a **non-production SAP company DB** for write tests. | Avoid polluting live B1. | ☐ Approve |
| R30 | Before action plan: run a short **SAP field workshop** covering AP Invoice, Outgoing Payment, and Journal Entry required fields (series, tax, BP, dimensions). | Blocks guesswork in P2/P3. | ☐ Approve |
| R31 | Document decisions in `docs/decisions.md` as each R# is closed. | Keeps PRD → plan → build traceable. | ☐ Approve |

---

## 9. Constraints & assumptions

- SAP B1 remains system of record for posted financial documents and canonical master data for synced entities.
- Service Layer availability, credentials, and session limits are owned by SAP infrastructure; the app must degrade gracefully (queue retries, admin-visible errors).
- Reference docs written against Laravel patterns are **directly applicable** to this stack (not merely behavioral analogues).
- MySQL is the primary application database; SAP HANA/SQL Server remains inside SAP only.

---

## 10. Dependencies & risks

| Item | Risk / dependency | Mitigation |
|------|-------------------|------------|
| SAP session limits | Too many clients exhaust concurrent sessions | Singleton `SapService`; queue workers bounded |
| Large BP sync | Timeouts / memory | Pagination, chunk upserts, queue job |
| Legacy project **codes** | Breaking historical string refs | Preserve `code`; match SAP via `sap_code` |
| Concurrent SAP writers | Last-write-wins | Optimistic checks; coordinate posting ownership |
| Double-post of payments/journals | Financial duplicate documents | Idempotency keys + status machine |
| Unclear SAP document mapping | Blocked loan/asset posting | Workshop (R30) before P2/P3 build |
| Inertia + AntD theme | Dark mode edge cases | Shared ConfigProvider theme; design tokens early |

---

## 11. Success metrics (KPIs)

- Master sync: **>99%** row success on routine runs; failures logged with remediable detail.
- Measurable drop in SAP rejection rates from invalid `CardCode` / project / department after rollout.
- 100% of successful loan and depreciation posts reconcilable via stored SAP DocEntry/DocNum.
- Sync job duration within agreed SLA (e.g. BP full sync &lt; 5 minutes for ~2k records).
- Zero silent double-posts in UAT (idempotency tests pass).

---

## 12. Phasing (high level — not yet an action plan)

| Phase | Focus | Depends on |
|-------|--------|------------|
| **P0** | Auth, RBAC, AppLayout (dark/light), `SapService` singleton + session handling, Projects & Departments sync + admin visibility | R1–R3, R5, R7–R8, R11–R12 |
| **P1** | Business Partners mirror + sync + selectors in forms | R6, R9–R10 |
| **P2** | Loan administration + AP Invoice / Outgoing Payment | R13–R20, R30 |
| **P3** | Assets, depreciation lists, depreciation journals | R21–R23, R30 |

Do **not** start detailed task breakdown until Section 8 decisions are marked Approve / Decide / Defer.

---

## 13. Open questions (must close before or during action plan)

1. Is SAP **Fixed Assets** already the master for company assets, or is AccountingOne Support the calculation master with journals only?
2. Exact SAP document **series**, tax groups, and dimension rules for AP Invoice, Outgoing Payment, and Journal Entry.
3. Loan counterpart always **vendor (S)**, or can customers/employees appear?
4. One consolidated depreciation journal per period vs one per asset?
5. Separate SAP users per environment — confirmed?
6. Incremental sync needed in year 1, or full daily sync sufficient?
7. TypeScript for React pages — yes/no?
8. Any existing Laravel codebase to extend vs greenfield AccountingOne Support app?

---

## 14. Next step (process)

1. Stakeholders review **Section 8 Recommendations** and tick Approve / Decide / Defer.  
2. Close **Section 13** open questions (especially SAP write mapping).  
3. Log outcomes in `docs/decisions.md`.  
4. Produce **Action Plan** (migrations, services, Inertia pages, permissions, jobs, UAT checklist) only after the above.

---

## 15. References

| Document | Use in PRD |
|----------|------------|
| [brain-storming.md](./brain-storming.md) | Product name, modules, UI, RBAC |
| [SAP_B1_BUSINESS_PARTNERS_SYNC_RECOMMENDATION.md](./SAP_B1_BUSINESS_PARTNERS_SYNC_RECOMMENDATION.md) | BP dual storage, mapping, sync strategy |
| [SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md](./SAP-B1-PROJECTS-DEPARTMENTS-INTEGRATION-PLAN.md) | Project/department schema, preservation, permissions |
| [SAP-B1-SESSION-MANAGEMENT.md](./SAP-B1-SESSION-MANAGEMENT.md) | Cookie sessions, 401/retry, singleton guidance |

---

**Version:** 1.1  
**Last updated:** 2026-07-15  
**Changelog:** Stack updated to Laravel + Inertia.js + React.js + AntD; added Section 8 recommendations and decision checklist before action plan.
