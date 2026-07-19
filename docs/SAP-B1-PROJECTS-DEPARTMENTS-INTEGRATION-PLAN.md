# SAP B1 Projects & Departments Integration Plan

**Date:** 2025-11-26  
**Status:** Planning Phase - Awaiting Review

---

## Executive Summary

This document outlines the plan to integrate Projects and Departments features with SAP B1 in the accounting application, based on the reference implementation guide from another application. The integration will enhance the existing minimal Projects and Departments tables with SAP synchronization capabilities while preserving all existing data and maintaining backward compatibility.

---

## Current State Analysis

### Projects Table

**Current Schema:**

- `id` (bigint)
- `code` (string) - **9 existing records** (000H, 001H, 017C, 021C, 022C, etc.)
- `is_active` (boolean, default true)
- `timestamps`

**Missing Fields:**

- ❌ `name` (VARCHAR 255) - Project name from SAP
- ❌ `description` (TEXT) - Project description
- ❌ `sap_code` (VARCHAR 20) - SAP B1 project code for matching
- ❌ `is_selectable` (BOOLEAN) - Visibility control for UI dropdowns
- ❌ `synced_at` (TIMESTAMP) - Last SAP sync timestamp
- ❌ Soft deletes (`deleted_at`)

**Usage in Application:**

- ✅ Heavily used in `payreqs` table (5,825 records with "000H", 2,054 with "001H")
- ✅ Used in `realizations`, `verification_journals`, `approval_stages`, and many other tables
- ✅ Stored as **string codes** (not foreign keys) - this pattern must be preserved
- ✅ Referenced in `users.project` field (string)

### Departments Table

**Current Schema:**

- `id` (bigint)
- `department_name` (VARCHAR 100, nullable)
- `akronim` (VARCHAR 10, nullable)
- `sap_code` (VARCHAR 10, nullable) - **✅ Already exists!** (17 departments with SAP codes)
- `timestamps`

**Missing Fields:**

- ❌ `description` (TEXT) - Department description
- ❌ `is_active` (BOOLEAN) - Enable/disable department
- ❌ `is_selectable` (BOOLEAN) - Visibility control for UI dropdowns
- ❌ `parent_id` (BIGINT UNSIGNED, nullable) - Hierarchical structure support
- ❌ `synced_at` (TIMESTAMP) - Last SAP sync timestamp
- ❌ Soft deletes (`deleted_at`)

**Usage in Application:**

- ✅ Used as **foreign key** (`department_id`) in `payreqs`, `realizations`, `users`, etc.
- ✅ Has relationship with `User` model
- ✅ Used in `approval_stages` (but stored as string - **needs fixing**)

### SAP Service Status

**Existing Implementation:**

- ✅ `SapService` class exists at `app/Services/SapService.php`
- ✅ Has `getProjects()` method (calls `ProjectsService_GetProjectList`)
- ✅ Has `getCostCenters()` method (calls `ProfitCenters` endpoint)
- ✅ Cookie-based session management implemented
- ✅ Auto re-login on 401 errors
- ✅ Config in `config/services.php` under `sap` key

**Missing:**

- ❌ Dedicated sync services (`SapProjectSyncService`, `SapDepartmentSyncService`)
- ❌ Admin UI for syncing
- ❌ Admin UI for managing visibility (`is_selectable`)

### Data Preservation Requirements

**Critical:**

1. **Projects**: All 9 existing project codes must be preserved
  - Migration must map existing `code` values to both `code` and `sap_code` initially
  - After first SAP sync, `sap_code` will be updated from SAP if match found
  - Projects without SAP match will keep their existing code as `sap_code`
2. **Departments**: All 17 existing departments with SAP codes must be preserved
  - Existing `sap_code` values are already populated
  - Migration must preserve all existing data
  - Sync will update names/descriptions but preserve IDs and relationships
3. **Referential Integrity:**
  - `payreqs.project` (string) references must continue working
  - `users.project` (string) references must continue working
  - `approval_stages.project` (string) references must continue working
  - `payreqs.department_id` (FK) references must continue working
  - All other tables using projects/departments must continue working

---

## Integration Requirements

### Phase 1: Database Schema Enhancement

#### Projects Table Migrations

1. **Add missing fields** (new migration):
  - `name` VARCHAR(255) NOT NULL (default to `code` for existing records)
  - `description` TEXT NULLABLE
  - `sap_code` VARCHAR(20) NULLABLE, INDEXED
  - `is_selectable` BOOLEAN DEFAULT TRUE
  - `synced_at` TIMESTAMP NULLABLE
  - `deleted_at` TIMESTAMP NULLABLE (soft deletes)
2. **Data Migration Strategy:**
  ```sql
   -- For existing projects, set name = code initially
   UPDATE projects SET name = code WHERE name IS NULL;

   -- Set sap_code = code initially (will be updated by sync)
   UPDATE projects SET sap_code = code WHERE sap_code IS NULL;

   -- All existing projects should be selectable and active
   UPDATE projects SET is_selectable = true;
  ```

#### Departments Table Migrations

1. **Add missing fields** (new migration):
  - `description` TEXT NULLABLE
  - `is_active` BOOLEAN DEFAULT TRUE
  - `is_selectable` BOOLEAN DEFAULT TRUE
  - `parent_id` BIGINT UNSIGNED NULLABLE, FK to departments.id
  - `synced_at` TIMESTAMP NULLABLE
  - `deleted_at` TIMESTAMP NULLABLE (soft deletes)
2. **Data Migration Strategy:**
  ```sql
   -- All existing departments should be active and selectable
   UPDATE departments SET is_active = true, is_selectable = true;

   -- Preserve existing sap_code values (already populated)
  ```
3. **Fix Approval Stages Issue:**
  - `approval_stages.department_id` is currently VARCHAR (string)
  - Should be BIGINT UNSIGNED (FK)
  - **Decision needed**: Fix this migration or leave as-is for backward compatibility?

### Phase 2: Model Enhancements

#### Project Model

- Add `SoftDeletes` trait
- Add fillable fields: `code`, `sap_code`, `name`, `description`, `is_active`, `is_selectable`, `synced_at`
- Add casts: `is_active` (boolean), `is_selectable` (boolean), `synced_at` (datetime)
- Add scopes: `selectable()`, `active()`
- Add validation: `code` must be unique, max 10 chars

#### Department Model

- Add `SoftDeletes` trait
- Add fillable fields: all existing + new fields
- Add casts: `is_active` (boolean), `is_selectable` (boolean), `synced_at` (datetime)
- Add scopes: `selectable()`, `active()`
- Add relationships: `parent()`, `children()`, `payreqs()`, `realizations()`

### Phase 3: SAP Sync Services

#### SapProjectSyncService

**Location:** `app/Services/Sap/SapProjectSyncService.php`

**Responsibilities:**

- Call `SapService::getProjects()` to fetch from SAP
- Match by `sap_code` (upsert logic)
- Preserve `is_selectable` on updates (manual overrides persist)
- Set `synced_at` timestamp
- Return detailed stats (created, updated, errors)

**Key Logic:**

```php
// Match existing projects by sap_code
$project = Project::where('sap_code', $sapProjectCode)->first();

if ($project) {
    // Update: preserve is_selectable
    $project->update([
        'name' => $sapName,
        'description' => $sapDescription,
        'is_active' => $sapActive,
        'synced_at' => now(),
        // is_selectable NOT updated
    ]);
} else {
    // Create: default is_selectable = true
    Project::create([...]);
}
```

#### SapDepartmentSyncService

**Location:** `app/Services/Sap/SapDepartmentSyncService.php`

**Responsibilities:**

- Call `SapService::getCostCenters()` to fetch from SAP
- Match by `sap_code` (upsert logic)
- Map `CenterCode` → `sap_code`, `CenterName` → `department_name`
- Preserve `is_selectable` on updates
- Set `synced_at` timestamp
- Return detailed stats

**Key Logic:**

```php
// Match existing departments by sap_code
$department = Department::where('sap_code', $centerCode)->first();

if ($department) {
    // Update: preserve is_selectable, parent_id, akronim
    $department->update([
        'department_name' => $centerName,
        'is_active' => true,
        'synced_at' => now(),
        // is_selectable, parent_id, akronim NOT updated
    ]);
} else {
    // Create: default is_selectable = true
    Department::create([...]);
}
```

### Phase 4: Admin Controllers & Routes

#### ProjectController

**Location:** `app/Http/Controllers/Admin/ProjectController.php`

**Methods:**

- `index()` - DataTables view
- `dataTable()` - DataTables AJAX response
- `syncFromSap()` - Trigger SAP sync (POST)
- `toggleVisibility()` - Toggle `is_selectable` (PATCH)

**Routes:**

```php
Route::middleware('permission:projects.view')->group(function () {
    Route::get('admin/projects', [ProjectController::class, 'index'])->name('admin.projects.index');
});

Route::middleware('permission:sap-sync-projects')->group(function () {
    Route::post('admin/projects/sync', [ProjectController::class, 'syncFromSap'])->name('admin.projects.sync');
});

Route::middleware('permission:projects.manage-visibility')->group(function () {
    Route::patch('admin/projects/{project}/visibility', [ProjectController::class, 'toggleVisibility'])->name('admin.projects.toggle-visibility');
});
```

#### DepartmentController

**Location:** `app/Http/Controllers/Admin/DepartmentController.php`

Similar structure to ProjectController, with additional:

- Parent department column in DataTable
- Hierarchical display support

### Phase 5: Admin UI Views

#### Projects Index View

**Location:** `resources/views/admin/projects/index.blade.php`

**Features:**

- DataTables with columns: Code, Name, SAP Code, Active, Visible, Last Synced, Actions
- "Sync from SAP" button (AJAX)
- Visibility toggle buttons per row
- Status badges (Active/Inactive, Visible/Hidden)
- Last sync timestamp display

#### Departments Index View

**Location:** `resources/views/admin/departments/index.blade.php`

Similar to Projects, with additional:

- Parent department column
- Hierarchical tree view option (future enhancement)

### Phase 6: Permissions

**Required Permissions:**

```php
// Projects
'projects.view',              // View projects list
'sap-sync-projects',          // Sync projects from SAP
'projects.manage-visibility', // Toggle is_selectable

// Departments
'departments.view',              // View departments list
'sap-sync-departments',          // Sync departments from SAP
'departments.manage-visibility', // Toggle is_selectable
```

**Permission Assignment:**

- Admin role: All permissions
- Accountant role: View + Sync only
- Manager role: View only

### Phase 7: Sidebar Menu Integration

Add menu items to sidebar:

```blade
@can('projects.view')
    <li class="nav-item">
        <a href="{{ route('admin.projects.index') }}" class="nav-link">
            <i class="nav-icon fas fa-project-diagram"></i>
            <p>Projects</p>
        </a>
    </li>
@endcan

@can('departments.view')
    <li class="nav-item">
        <a href="{{ route('admin.departments.index') }}" class="nav-link">
            <i class="nav-icon fas fa-building"></i>
            <p>Departments</p>
        </a>
    </li>
@endcan
```

---

## Implementation Plan

### Step 1: Database Migrations (Data-Safe)

**Priority:** Critical  
**Risk:** Low (additive changes, existing data preserved)

1. Create migration: `add_sap_integration_fields_to_projects_table.php`
  - Add all missing fields
  - Set defaults for existing records
  - Add indexes
2. Create migration: `add_sap_integration_fields_to_departments_table.php`
  - Add all missing fields
  - Add `parent_id` FK (nullable, self-referencing)
  - Set defaults for existing records
  - Add indexes
3. **Test migrations on staging first**
4. Run migrations on production during low-traffic window

### Step 2: Model Updates

**Priority:** High  
**Risk:** Low (additive changes)

1. Update `Project` model:
  - Add `SoftDeletes`
  - Add fillable, casts, scopes
  - Test with existing data
2. Update `Department` model:
  - Add `SoftDeletes`
  - Add fillable, casts, scopes, relationships
  - Test with existing data

### Step 3: SAP Sync Services

**Priority:** High  
**Risk:** Medium (SAP API integration)

1. Create `app/Services/Sap/` directory
2. Create `SapProjectSyncService.php`
3. Create `SapDepartmentSyncService.php`
4. Test sync with SAP B1 (staging environment first)
5. Verify data preservation (existing records not duplicated)

### Step 4: Admin Controllers

**Priority:** Medium  
**Risk:** Low

1. Create `ProjectController` in `app/Http/Controllers/Admin/`
2. Create `DepartmentController` in `app/Http/Controllers/Admin/`
3. Add routes to `routes/admin.php` (or appropriate route file)
4. Test CRUD operations

### Step 5: Admin UI

**Priority:** Medium  
**Risk:** Low

1. Create views directory: `resources/views/admin/projects/`
2. Create views directory: `resources/views/admin/departments/`
3. Create index views with DataTables
4. Create action partials (visibility toggle buttons)
5. Add JavaScript for AJAX sync
6. Test UI interactions

### Step 6: Permissions & Access Control

**Priority:** Medium  
**Risk:** Low

1. Add permissions to seeder
2. Assign permissions to roles
3. Test permission checks
4. Update sidebar menu

### Step 7: Business Logic Integration

**Priority:** Low (Future Enhancement)  
**Risk:** Medium (affects existing workflows)

**Optional Enhancements:**

1. Update form controllers to use `selectable()->active()` scopes
2. Add project/department validation in Payreq/Realization creation
3. Update approval workflow matching logic (if needed)

---

## Data Migration Strategy

### Projects Migration

```sql
-- Step 1: Add new columns (nullable initially)
ALTER TABLE projects ADD COLUMN name VARCHAR(255) NULL;
ALTER TABLE projects ADD COLUMN description TEXT NULL;
ALTER TABLE projects ADD COLUMN sap_code VARCHAR(20) NULL;
ALTER TABLE projects ADD COLUMN is_selectable BOOLEAN DEFAULT TRUE;
ALTER TABLE projects ADD COLUMN synced_at TIMESTAMP NULL;
ALTER TABLE projects ADD COLUMN deleted_at TIMESTAMP NULL;

-- Step 2: Populate existing data
UPDATE projects SET 
    name = code,
    sap_code = code,
    is_selectable = true
WHERE name IS NULL;

-- Step 3: Make name NOT NULL (after populating)
ALTER TABLE projects MODIFY COLUMN name VARCHAR(255) NOT NULL;

-- Step 4: Add indexes
CREATE INDEX idx_projects_sap_code ON projects(sap_code);
CREATE INDEX idx_projects_is_active ON projects(is_active);
CREATE INDEX idx_projects_code ON projects(code);
```

### Departments Migration

```sql
-- Step 1: Add new columns
ALTER TABLE departments ADD COLUMN description TEXT NULL;
ALTER TABLE departments ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE departments ADD COLUMN is_selectable BOOLEAN DEFAULT TRUE;
ALTER TABLE departments ADD COLUMN parent_id BIGINT UNSIGNED NULL;
ALTER TABLE departments ADD COLUMN synced_at TIMESTAMP NULL;
ALTER TABLE departments ADD COLUMN deleted_at TIMESTAMP NULL;

-- Step 2: Populate existing data
UPDATE departments SET 
    is_active = true,
    is_selectable = true
WHERE is_active IS NULL;

-- Step 3: Add foreign key for parent_id
ALTER TABLE departments 
    ADD CONSTRAINT fk_departments_parent 
    FOREIGN KEY (parent_id) REFERENCES departments(id) 
    ON DELETE SET NULL;

-- Step 4: Add indexes
CREATE INDEX idx_departments_parent_id ON departments(parent_id);
CREATE INDEX idx_departments_is_active ON departments(is_active);
CREATE INDEX idx_departments_sap_code ON departments(sap_code);
```

---

## Testing Strategy

### Unit Tests

1. Model scopes (`selectable()`, `active()`)
2. Model relationships (Department parent/children)
3. Sync service logic (upsert, preservation of `is_selectable`)

### Integration Tests

1. SAP sync service (mock SAP API responses)
2. Controller methods (sync, toggle visibility)
3. Data preservation (existing records not duplicated)

### Manual Testing Checklist

1. ✅ Run migrations on staging
2. ✅ Verify existing projects/departments preserved
3. ✅ Test SAP sync (create new, update existing)
4. ✅ Verify `is_selectable` preserved on sync
5. ✅ Test visibility toggle
6. ✅ Test dropdowns use `selectable()->active()` scope
7. ✅ Verify payreqs/realizations still work
8. ✅ Test approval stages still work
9. ✅ Test permissions
10. ✅ Test sidebar menu

---

## Risk Assessment & Mitigation

### High Risk Areas

1. **Data Loss During Migration**
  - **Risk:** Migration fails, data corrupted
  - **Mitigation:** 
    - Test migrations on staging first
    - Backup database before migration
    - Use transactions in migrations
    - Run during low-traffic window
2. **SAP API Changes**
  - **Risk:** SAP B1 API response format changes
  - **Mitigation:**
    - Handle multiple response formats (fallback logic)
    - Log all SAP responses for debugging
    - Test with actual SAP B1 instance
3. **Existing Code Breaking**
  - **Risk:** Adding soft deletes breaks existing queries
  - **Mitigation:**
    - Review all queries using Projects/Departments
    - Add `withTrashed()` where needed
    - Test all affected features

### Medium Risk Areas

1. **Performance Impact**
  - **Risk:** Large sync operations slow down system
  - **Mitigation:**
    - Process in batches
    - Use queue jobs for large syncs
    - Add progress indicators
2. **Permission Conflicts**
  - **Risk:** New permissions conflict with existing roles
  - **Mitigation:**
    - Review existing permissions
    - Test permission checks thoroughly
    - Document permission requirements

---

## Open Questions & Decisions Needed

1. **Approval Stages `department_id` Type**
  - Current: VARCHAR (string)
  - Should be: BIGINT UNSIGNED (FK)
  - **Decision:** Fix migration or leave as-is?
  - **Recommendation:** Fix it - proper FK ensures data integrity
2. **SapProject vs Project**
  - There's a `SapProject` model and `sap_projects` table
  - **Decision:** Keep both or merge?
  - **Recommendation:** Keep `SapProject` for audit/history, use `Project` as master data
3. **Initial Sync Strategy**
  - **Decision:** Auto-sync on migration or manual trigger?
  - **Recommendation:** Manual trigger (admin button) - safer, allows review
4. **Soft Deletes Rollout**
  - **Decision:** Enable immediately or phase in?
  - **Recommendation:** Enable immediately - no breaking changes if queries use Eloquent

---

## Success Criteria

1. ✅ All existing projects/departments preserved
2. ✅ SAP sync creates/updates records correctly
3. ✅ `is_selectable` preserved on sync
4. ✅ Admin UI functional (list, sync, toggle visibility)
5. ✅ Permissions working correctly
6. ✅ All existing features still work (payreqs, realizations, approvals)
7. ✅ No data loss or corruption
8. ✅ Performance acceptable (sync completes in reasonable time)

---

## Timeline Estimate

- **Phase 1 (Migrations):** 2-3 hours
- **Phase 2 (Models):** 1-2 hours
- **Phase 3 (Sync Services):** 3-4 hours
- **Phase 4 (Controllers):** 2-3 hours
- **Phase 5 (UI):** 4-5 hours
- **Phase 6 (Permissions):** 1 hour
- **Phase 7 (Testing):** 3-4 hours

**Total:** 16-22 hours (2-3 days)

---

## Next Steps

1. **Review this plan** - Confirm approach, address open questions
2. **Approve data migration strategy** - Confirm preservation approach
3. **Set up staging environment** - Test migrations and sync
4. **Begin implementation** - Start with Phase 1 (migrations)
5. **Iterative testing** - Test each phase before moving to next

---

## References

- [Projects & Departments Implementation Guide](./PROJECTS-DEPARTMENTS-IMPLEMENTATION-GUIDE.md)
- [SAP B1 Session Management](./SAP-B1-SESSION-MANAGEMENT.md)
- [Architecture Documentation](./architecture.md)

