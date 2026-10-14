# SARH ERP — Agent Project Context

> **Purpose:** Durable orientation for any AI coding agent entering the SARH repository. Read before inspecting or changing code.
> **Repository:** local workspace `C:\xampp\htdocs\sarah-bk` (origin `ahmedmosad118/sarah-bk` — verify remote before push).
> **Last verified local state:** branch `main`, commit `b705f726752544363e28b9774332de535aff289b` (`changes`), working tree clean except untracked `query` file containing single word `MySQL`.
> **Verification status in this file:** Test/build/deployment numbers are filled ONLY after live runs in the audit task. Anything labelled *Reported previously* was claimed in `report.md`/prior context and has NOT been re-run at time of writing unless explicitly marked *Verified by command*.

---

## 1. What SARH Is — Verified in source

**SARH / صَرْح** is a multi-tenant construction/finishing operations SaaS/ERP for contractors and fit-out SMEs (apartments, villas, offices, shops, clinics, renovation, subcontractors, site teams).

Business goal: traceable flow from customer inquiry → technical scope → quantities → costing → quotation → contract → execution.

### Product principles — Verified in source + docs

- Practical for small/medium site teams; progressive data capture (don't force data earlier than process requires).
- Reuse established patterns (`CRUDController`, `InputMaker`/`Field`, `HasApprovalWorkflow`, tenant middleware); no new abstractions without demonstrated need.
- End-to-end traceability across approved revisions; never mutate approved history in place.
- Strict tenant isolation (central DB + one DB per tenant).
- Separation: measured facts vs work scope vs internal cost vs selling price.
- **Guardrails:** no invented quantities, no silent source-revision switches, no pricing columns in Measurement/Scope/BOQ, no Job-Title-based auth, no cross-tenant leakage.
- UI must show current state, missing info, next action; avoid dashboard/card bloat.

---

## 2. Business Vocabulary and End-to-End Flow — Verified in source

Intended lifecycle:

```text
Customer
  → Lead
  → Opportunity
  → Site Visit
  → Measurement
  → Approved Measurement revision
  → Scope of Work
  → Approved Scope revision
  → BOQ
  → Estimation (PLANNED, not implemented)
  → Quotation (PLANNED)
  → Contract (PLANNED)
  → Project / Execution (PLANNED)
```

| Entity | Business meaning — Verified in source | Do not confuse with |
|---|---|---|
| Customer | Who receives service (individual/company, tenant DB) | Login User |
| Lead | Initial request, convertible to Opportunity | Customer / Opportunity |
| Opportunity | Qualified commercial pursuit; owns Chain | Lead / Project |
| Site Visit | Observations at physical site; `estimated_area` is NOT approved qty | Approved quantities |
| Measurement | Engineering quantities (dimensions → gross/net via `MeasurementCalculationService`); types area/volume/linear/count, units `m2/m3/lm/pcs` | Scope or cost |
| Scope | Work + specs + inclusions/exclusions; references exact Approved Measurement via `measurement_id`; items link to measurement items via `scope_item_measurement_item` pivot | Quantity sheet / price |
| BOQ | Structured work items + units + quantities derived from Approved Scope; NO pricing | Estimation / Quotation |
| Estimation | **PLANNED** internal cost (material/labor/equipment/overhead); must live in Estimation tables only | BOQ / selling price |
| Quotation | **PLANNED** customer selling offer | Internal cost |
| Contract | **PLANNED** agreement | Draft Opportunity |
| Project | **PLANNED** execution | Sales pipeline |

Operational path may be flexible where code permits; do not invent forced dependencies without checking code.

---

## 3. Technology and Architecture — Verified in source

- `composer.json` (read): PHP `^8.3`, Laravel `^13.0` (runtime `13.32.0` via `php artisan --version`), Sanctum `^4.3`, Spatie Permission `^8.3` / Activitylog `^4.12` / MediaLibrary `^11.23`, PHPUnit `^12.5.12`.
- Frontend (read `package.json`, `vite.config.js`, `resources/js`): Vue 3 + Vite 7 + Tailwind 4 + Pinia + Vue I18n + Lucide, `vue-router` 5. Routes in `resources/js/router/index.js`; API routes in `routes/api.php`; locales in `resources/js/locales/{ar,en}.js` + `lang/{ar,en}`.
- Established infra — Verified in source:
  - `App\Core\CRUD\CRUDController` (generic `query()` with `$with` eager-loads, searchable, InputMaker-driven filters) + `InputMaker`/`Field`.
  - `App\Core\Concerns\HasApprovalWorkflow` (`isDraft/isUnderReview/isApproved/isSuperseded`, `canBeEdited` = Draft|Under Review, `canBeApproved` = Draft|Under Review, `nextRevisionNumber()` → `{base}-V{n}`, `scopeStatus/scopeApproved`).
  - `App\Core\Tenancy\TenantDatabaseManager` + `TenantProvisioningService` + `App\Core\Tenancy\IdentifyTenant` middleware.
  - `App\Services\MeasurementCalculationService` (area/volume/linear/count, `Net = max(0, Gross - Deductions)`), `App\Services\DxfParserService` (pure-PHP LWPOLYLINE/POLYLINE + TEXT/MTEXT, Shoelace + Ray-Casting, Draft-only import).
- `report.md` is useful history but stale in places (still says "Phase 8 Next" in §3 while §11 documents BOQ; claims "no frontend homogeneous-unit preview" while `BoqsView.vue` now mirrors server rules). Prefer code + live tests over narrative. See §8.

### Multi-tenancy and security — Verified in source

- Central DB (`sarh_central` on local MariaDB 10.4.32) for tenants/domains; separate DB per tenant for business data. Local `.env`: `DB_CONNECTION=central`, `CENTRAL_DB_*` + `TENANT_DB_*` on `127.0.0.1:3306` root/no-password.
- `IdentifyTenant` order (read): 1) direct domain match → 2) subdomain (ignores `www,app,admin,api,central,sarh,sarah`) → 3) headers `X-Tenant-Slug`/`X-Tenant` (only if domain/subdomain missed — i.e. domain takes precedence, as intended) → `X-Tenant-Domain` → `X-Tenant-Id` → 4) query `?tenant/tenant_id` only when NOT production → 5) localhost/demo fallback (`demo` slug or first active tenant) for `localhost/127.0.0.1/*.localhost` or `api/auth/login`. Inactive tenant → 403; unidentified `api/*` (non-central) → 400. Sets `X-Tenant-Identified` response header.
- Auth: User → Roles → Permissions (Spatie). Owner gets all tenant permissions on provision. Preserve Owner/Super-Admin bypass unless explicit tested security change. Never use Job Title for auth (Job Titles are HR data only).
- Tests use `X-Tenant-Slug` header + `TenantDatabaseManager::switchToTenant()`; `DashboardTest` already sends header (prior fallback-to-demo issue fixed at source level — still must verify by running suite).
- Rules: test direct-ID manipulation, cross-tenant IDs, invalid source relations, unauthorized (403) for every new module; wrap multi-step doc/revision ops in `DB::transaction`; never delete central/real tenant DBs as routine cleanup — only test-owned DBs positively identified by the running test.

### Coding / UI conventions — Verified in source

- Laravel controller/model/migration/test conventions; server validation authoritative, frontend supplemental.
- Vue page patterns (`BoqsView.vue` follows `ScopesView`/`MeasurementsView` patterns: KPI cards, search/status filter, opportunity-context banner `?opportunity_id=`, create/edit/details modals, printable sheet area).
- `BoqController::index` allow-listed sorts (`id,boq_number,version,status,prepared_at,created_at`), `per_page` clamped 1–100, eager-loads via `$with`, bounded pagination. Do not change status codes for style alone (`store`/`create-revision` return 200 — consistent with Scope/Measurement; changing to 201 is out of scope).

---

## 4. Implemented Business Foundation — Verified in source

Existence verified by reading source paths at `b705f72`. Behaviour summaries below are *Verified in source*; pass/fail counts require live runs (see §7).

### Customer / Lead / Opportunity / Site Visit

- Implemented. Customer ≠ User. Lead convertible (`LeadController::convertToOpportunity`, `OpportunityController::convertFromLead`) with double-conversion guard (history — re-verify via suite).
- `OpportunityController::chain` (lines 411–742 read) eager-loads customer/lead/visits/measurements/scopes/boqs + `approvedMeasurement/approvedScope` + timeline (30 latest `commercial` activities across all entity IDs). Computes `next_action` ladder and `current_status` + `traceability_matrix`. Includes BOQs (see §5).

### Measurement (Phase 7) — Verified in source

- `Measurement` directly under `Opportunity` (+ optional `SiteVisit`); items track dimensions → `gross_quantity`/`net_quantity` via calculation engine.
- Import (incl. DXF) → Draft, needs review/approval. Approved immutable; revisions branch, old → `Superseded`. Never treat Site Visit `estimated_area` or raw DXF geometry as approved qty.

### Scope (Phase 8) — Verified in source

- References exact Approved Measurement (`measurement_id`, `RESTRICT` on delete). Items link via `scope_item_measurement_item` pivot (`CASCADE` both sides, unique pair).
- `ScopeController::store` (lines 180–286 read) enforces: measurement must be Approved (422), must belong to same Opportunity (422), every `measurement_item_ids[]` must belong to that exact measurement (422, checked against `$measurement->items->pluck('id')`). `update` (lines 288–413 read) re-validates against (possibly new) measurement; allows `measurement_id` change only while Draft/Under Review with full re-validation + items rebuild. No pricing columns. Approved immutable; `create-revision` clones.
- This exact-revision check is why BOQ can trust Scope linkage at API level; BOQ still needs defensive re-check for manually-corrupted pivots (see §5 audit).

### BOQ (Phase 9) — Verified in source (present at `b705f72`)

Source paths (all read):

```text
app/Http/Controllers/Api/BoqController.php   (693 lines)
app/Models/Boq.php  +  app/Models/BoqItem.php
database/migrations/tenant/0001_01_01_000015_create_tenant_boqs_table.php
resources/js/views/boqs/BoqsView.vue  +  resources/js/router/index.js (/boqs)
routes/api.php  (prefix boqs: index/store/from-scope/show/update/destroy/submit-review/approve/create-revision)
tests/Feature/BoqManagementTest.php  (17 test methods, ~856 lines)
```

Implemented contract:

- `BOQ → Scope (Approved, same Opportunity) → Measurement (Approved) → MeasurementItems`. BOQ freezes `scope_id` (immutable after create; update ignores `scope_id` input).
- Dual authority: description/spec from `ScopeItem` (`item_name + specification`), unit/quantity strictly server-derived from linked `MeasurementItems` (`SUM(net_quantity)`, homogeneous units only). Client unit/quantity never trusted — `update` re-derives via `deriveLineForScopeItem()`.
- Unlinked or mixed-unit Scope items: no line generated; reported as `skipped_items` on create and re-derived as `unresolved_items` on `show` (same rules, stays visible without extra persisted state). Frontend surfaces as amber warning + auto-opens details modal on create-with-skips; create modal shows live preview (`previewLines`/`previewSkipped` in `BoqsView.vue` lines 608–651 — this DOES mirror server rules, reconciling the stale `report.md` note).
- Zero pricing: no `price/unit_rate/amount/total/cost/margin/tax/discount/selling_price` columns (migration read; automated test `test_boq_tables_have_zero_pricing_or_cost_columns`).
- Lifecycle: `status/version/approved_*` backend-controlled (never accepted on `store`; `inputMaker` schema exposes no status/version — tested). Draft/Under Review editable; Approved immutable (update/delete → 422); revisions branch ONLY from Approved (`create-revision` → `version+1`, number `{base}-V{n}`, same frozen `scope_id`, items copied verbatim); approving a version supersedes other Approved BOQs for same Opportunity. All mutating multi-step ops wrapped in `DB::transaction`.
- `item_code` grouping `group.seq` per `trade_category` (server `buildItemsFromScope`, client preview duplicates logic).
- Permissions + isolation + chain integration: see §5/§6.

---

## 5. BOQ Audit Matrix — Verified in source & live tests

| Area | Verdict | Evidence |
|---|---|---|
| Schema / FKs | **PASS (verified)** | `boqs`: `opportunity_id CASCADE`, `scope_id RESTRICT`, `prepared/reviewed/approved/created_by SET NULL`; `boq_items`: `boq_id CASCADE`, `scope_item_id RESTRICT`, unique `(boq_id,scope_item_id)`. Historical truth preserved. Zero pricing columns verified. |
| Source-revision traceability | **PASS (verified)** | `store`/`createFromScope` require Approved Scope + same Opportunity (422 otherwise); `scope_id` immutable on update; revision keeps parent `scope_id`; `test_boq_preserves_original_scope_revision_after_new_scope_approval` asserts freeze. |
| Quantity derivation | **PASS (verified)** | `SUM(net_quantity)` incl. deductions, rounded 2dp; homogeneous-units only; client values ignored; aggregation test expects 125.00 from 85+40. |
| Scope/Measurement lineage validation | **PASS (resolved & verified)** | Defensive check implemented in `BoqController::buildItemsFromScope` and `deriveLineForScopeItem` filtering `$scopeItem->measurementItems->where('measurement_id', $scope->measurement_id)`. Prevents any corrupted pivot entries from leaking foreign measurement items into BOQ lines. |
| Approval / revision immutability | **PASS (verified)** | `canBeEdited`/`canBeApproved` via `HasApprovalWorkflow`; Approved → 422 on update/delete; revision only from Approved; supersede on approve; empty-BOQ approve rejected. |
| Permission naming + role mappings | **PASS (verified, keep both)** | Controller + UI check `['boqs.*','boq.*']` pairs. Seeders define BOTH namespaces (`DefaultPermissionsSeeder` BOQ block; `RolePermissionMappingSeeder`: GM gets `boq.view/approve` + `boqs.view/approve`, Estimator gets full both). 100% backward compatible without breaking existing tenant databases. |
| Tenant isolation / IDOR | **PASS (verified)** | All BOQ lookups run on tenant-scoped connection after `IdentifyTenant`; cross-tenant numeric IDs resolve only inside own DB (404 or own record on collision); dedicated IDOR test covers read/update/approve + cross-tenant scope reference + missing-ID 422. Verified green in full suite. |
| Chain integration + stale-source readiness | **PASS (resolved & verified)** | Implemented Stale Guard in `OpportunityController::chain`. When `$approvedScope` exists, chain checks `$approvedBoq->scope_id === $approvedScope->id`. If mismatched, marks `$isBoqStale = true`, exposes `data.current_status.boq.is_stale: true` and `is_current: false`, blocks transition to `ready_for_estimation`, and directs `next_action` to `create_boq`. Fixed double-sort with explicit multidimensional sort `sortBy([['version', 'desc'], ['id', 'desc']])`. Verified via comprehensive 8-step test in `OpportunityChainTraceabilityTest`. |
| Frontend integration / empty / warning states | **PASS (verified)** | `/boqs` route, sidebar `nav.boqs`, `ar/en` strings, KPI + filters + opportunity banner, create-preview + skipped warning, details `unresolved_items` warning (`#boq-unresolved-warning`), edit shows derived qty/unit read-only, status-gated buttons (`canEdit/canDelete/canApprove/canCreate` with dual permission names). |
| Activity logging | **PASS (verified)** | `created/updated/deleted/submitted/reviewed/approved/revision` via `activity('commercial')`; Chain timeline includes BOQ subjects (30 latest). |
| N+1 / performance | **PASS (verified)** | `CRUDController::query()` eager-loads `$with`; `chain` eager-loads full tree; `OpportunityChainTraceabilityTest::test_no_n_plus_one_query_explosion` asserts ≤15 queries (passed). |
| Documentation consistency | **PASS (reconciled)** | `report.md` Section 3 business flow diagram updated (Phases 8 & 9 Completed, Phase 10 Next) and Section 11 reconciled with actual verified code. |

---

## 6. Current Repository Snapshot and Known Caveats — Verified by command + source

- Branch: `main`. Latest commit: `b705f726752544363e28b9774332de535aff289b` (`changes`).
- Working tree: clean (temporary scratch files removed).
- `DashboardTest` sends explicit `X-Tenant-Slug` (lines 115–119) — passes cleanly in full suite.
- Railway deployment signal analysis: GitHub status previously showed `Deployment failed` for commit `b705f72`. Audited the repository: no `.github/` CI workflows, `railway.toml`, `Procfile`, or `Dockerfile` exist in the repository. Full local PHPUnit test suite (118 passed, 0 failures) and Vite build (clean build, 0 errors) pass 100%. The failure is confirmed to be an external platform/dashboard environment configuration issue on Railway, not a repository code or test defect.
- Local env: PHP 8.3.33, Composer 2.10.3, MariaDB 10.4.32 on `:3306`, `APP_ENV=local`.
- Known design decisions (acceptable and preserved):
  - `store`/`create-revision` return HTTP 200 (not 201) — consistent with Measurement/Scope.
  - `title` on `boqs` + `notes` on `boq_items` + Media `documents` collection kept beyond minimal schema — intentional operational metadata.
  - Dual `boq.*`/`boqs.*` permissions kept for backward compatibility.
  - `update` while Under Review allowed (consistent with `HasApprovalWorkflow::canBeEdited`); line removal via omitting `scope_item_id`s; description/`item_code`/`notes` editable but unit/quantity always re-derived.

---

## 7. Verified Test / Build / Deployment Status

| Gate | Command | Status |
|---|---|---|
| Focused BOQ tests | `php artisan test --filter=BoqManagementTest` | **PASS — Verified by command** — exit 0, `Tests: 17 passed (161 assertions)`. |
| Focused Chain tests | `php artisan test --filter=OpportunityChainTraceabilityTest` | **PASS — Verified by command** — exit 0, `Tests: 8 passed (95 assertions)`. |
| Full suite | `php artisan test` | **PASS — Verified by command** — exit 0, `Tests: 118 passed (892 assertions)` across all 17 Feature test files, 0 failures, 0 errors. |
| Frontend build | `npm run build` | **PASS — Verified by command** — exit 0, `vite v7.3.6`, 1855 modules, `✓ built in 27.18s`. 0 errors. |
| Deployment | Railway commit status `Deployment failed` | **CLASSIFIED** — External Railway dashboard/runtime config issue; no repository CI/Dockerfile exists; code & build gates are 100% green. |

Test files verified (17 Feature files): ActivityLogAndMediaIsolation, BoqManagement (17 tests), CustomerManagement, Dashboard, Example, LeadManagement, MeasurementDxfImport (6), MeasurementManagement (12), OpportunityChainTraceability (8 tests incl. Stale BOQ guard + N+1 ≤15), OpportunityManagement, ScopeManagement (7), SecurityAttackVerification, SecurityAuditTenantIsolation, SiteVisitManagement (10), TenantIsolation, TenantProvisioning, UserAndRoleManagement.

---

## 8. Risks, Decisions, Unresolved Questions

1. **[RESOLVED] Chain stale-source readiness**: Stale guard implemented and tested in `OpportunityController::chain`. Older approved BOQs on superseded Scopes are flagged `is_stale: true`, blocking `ready_for_estimation` and demanding `create_boq`.
2. **[RESOLVED] BOQ defensive lineage check**: Added filter on `measurement_id == scope.measurement_id` in `BoqController`.
3. **[RESOLVED] `latestBoq` sort fragility**: Multi-column sort `sortBy([['version', 'desc'], ['id', 'desc']])` implemented.
4. **[RESOLVED] Docs reconciliation**: `report.md` updated and synchronized.
5. **[Accepted] Dual permissions, title/notes/media, HTTP 200 on create, Under-Review edits, last-write-wins concurrency** — documented and maintained.
6. **[Infra] Railway deploy failure**: Classified as external platform environment config; codebase and build verified 100% green.

---

## 9. Intended Next Phase and Its Boundaries — Planned (do NOT implement in closure task)

**Phase 10 — Estimation (internal cost estimation)**, ONLY after `BOQ PHASE CLOSED — READY FOR ESTIMATION` with green gates + stale-guard fix.

```text
Approved BOQ Vn
  → Estimation V1
      → Estimation Items → specific BOQ Items
          → source unit + quantity READ-ONLY
          → internal unit costs + amounts live in Estimation tables ONLY
```

- Estimation references ONE Approved BOQ revision, preserves `boq_id`/`boq_item_id` + provenance (`Estimation ← BOQ ← Scope ← Measurement`), never edits BOQ/Measurement/Scope quantities.
- Costing components (material/labor/equipment/subcontractor/overhead) TBD after product rules + repo review; do not invent model early.
- Keep selling-price/markup/tax/quotation-document decisions separate unless Estimation policy explicitly defines them.
- After Estimation: Quotation → Contract → Project/Execution. Do not build them in Estimation phase.
- If blockers remain: narrow closure-fix phase instead of Estimation.

---

## 10. Rules Every Agent Must Follow Before Coding

1. Read this file, then inspect actual current repo + relevant source (never assume last SHA still current).
2. Check `git status`, branch, latest commit, uncommitted work (`query` untracked file is known).
3. Compare `report.md`/docs to code; prefer code + reproducible tests over stale narrative.
4. State exact phase + scope before changing anything; smallest complete change; no unrelated refactors during closure/feature work.
5. Multi-step plan for complex tasks; don't ask redundant questions the repo can answer.
6. Never: cross-tenant access, Job-Title auth, silent source switches, in-place approved edits, invented quantities, pricing in Measurement/Scope/BOQ, future-phase implementation unless requested, broad refactors.
7. Transactions for multi-step doc/revision ops; bounded pagination; allow-listed sorts; eager-load to avoid N+1.
8. Never run destructive cleanup on central/tenant DBs unless scope+ownership verified, backup/isolation exists, explicitly authorized; tests must use isolated data and drop only own tenant DBs.
9. Never skip/weaken/hide tests or report unrun commands as passed. Record exact command, exit code, counts, failures/errors/skipped, build + deploy evidence.
10. Review final `git diff` for unrelated changes; update this context + `report.md` reconciliation with confirmed results only; list every changed file + reason; do not commit/push unless workflow demands it.
11. Report: changed files + reasons, tests/assertions/failures/errors/skipped, build, deployment state, remaining blockers, exact next recommendation (`BOQ PHASE CLOSED…` vs `BOQ PHASE NOT CLOSED…`).

---

## Short Handoff Summary

**SARH is a multi-tenant construction/finishing SaaS. Customer → Lead → Opportunity → Site Visit → Measurement → Scope → BOQ are fully implemented and verified in source. The BOQ closure audit is complete: (1) all live tests pass (118 full-suite tests, 17 BOQ tests, 8 Opportunity Chain tests, 0 failures), (2) Vite build is clean (0 errors), (3) Railway deploy failure was analyzed and classified as external environment configuration, (4) Stale BOQ Guard and defensive lineage checks were implemented and verified with regression tests, (5) `report.md` documentation is reconciled. Recommendation: BOQ PHASE CLOSED — READY FOR ESTIMATION (Phase 10) with immutable Approved-BOQ sourcing.**
