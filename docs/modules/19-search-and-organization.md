# Module 19 — Search & Organization

| Field | Value |
|---|---|
| **Module** | 19 |
| **Name** | Global Search & Organization |
| **Dependencies** | Module 05, Module 06, Module 11, Module 13 |
| **Status** | Not Started |

---

## Objective

Build a unified global search across all resource types (vault items, files, notes, people, teams) and shared organization features (filters, sorting, recently accessed, recently created, expiring soon).

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SO-01 | Global search across passwords, secrets, files, notes, people, and teams | P0 |
| SO-05 | Filters (by type, tag, folder, shared status, expiration) | P1 |
| SO-06 | Sorting (by name, date created, date modified, expiration) | P1 |
| SO-07 | Recently accessed and recently created views | P1 |
| SO-08 | Expiring soon view (items with access expiring within 48 hours) | P1 |

---

## Tasks

### 19.1 Global Search Service

- [ ] Create `app/Services/GlobalSearch.php`:

```php
class GlobalSearch
{
    public function search(User $user, string $query, ?string $type = null): array
    {
        $results = [];

        if (!$type || $type === 'vault_items') {
            $results['vault_items'] = $this->searchVaultItems($user, $query);
        }
        if (!$type || $type === 'files') {
            $results['files'] = $this->searchFiles($user, $query);
        }
        if (!$type || $type === 'notes') {
            $results['notes'] = $this->searchNotes($user, $query);
        }
        if (!$type || $type === 'people') {
            $results['people'] = $this->searchPeople($user, $query);
        }
        if (!$type || $type === 'teams') {
            $results['teams'] = $this->searchTeams($user, $query);
        }

        return $results;
    }
}
```

**Search rules:**
- Vault items: search `name` and `url` (plaintext columns only — never encrypted)
- Files: search `name` and `description`
- Notes: search `title` (never encrypted `content`)
- People: search `name` and `email` (tenant members only)
- Teams: search `name` and `description`
- All searches use `LIKE %query%`
- Results limited to 20 per type
- Only returns resources the user has access to

### 19.2 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/search?q={query}` | Global search across all types |
| `GET` | `/api/v1/search?q={query}&type={type}` | Search within a specific type |
| `GET` | `/api/v1/recent` | Recently accessed items (all types) |
| `GET` | `/api/v1/recent/created` | Recently created items (all types) |
| `GET` | `/api/v1/expiring` | Items with access expiring within 48 hours |

### 19.3 Controller

- [ ] `app/Http/Controllers/Api/V1/SearchController.php`:
  - `search()` — call `GlobalSearch::search()`, return grouped results
  - `recent()` — aggregate recently accessed items across types
  - `recentCreated()` — aggregate recently created items across types
  - `expiring()` — find all access grants for user expiring within 48 hours

### 19.4 Search Response Format

```json
{
  "data": {
    "vault_items": [
      { "id": 1, "name": "GitHub Deploy Key", "type": "api_key", "url": "..." }
    ],
    "files": [
      { "id": 5, "name": "config.pdf", "mime_type": "application/pdf" }
    ],
    "notes": [
      { "id": 3, "title": "Server Setup Notes" }
    ],
    "people": [
      { "id": 7, "name": "John Doe", "email": "john@example.com" }
    ],
    "teams": [
      { "id": 2, "name": "DevOps Team" }
    ]
  },
  "meta": {
    "query": "github",
    "total_results": 4
  }
}
```

### 19.5 Filters

- [ ] Support query parameters on list endpoints:
  - `type` — filter by resource type
  - `tag` — filter by tag name
  - `folder_id` — filter by folder
  - `shared` — `true` = only shared with me, `false` = only my items
  - `expiring` — `true` = only items expiring soon
  - `archived` — `true` = only archived, `false` = exclude archived

### 19.6 Sorting

- [ ] Support `sort` query parameter:
  - `name` — alphabetical by name
  - `-name` — reverse alphabetical
  - `created_at` — oldest first
  - `-created_at` — newest first (default)
  - `updated_at` — least recently modified
  - `-updated_at` — most recently modified
  - `expires_at` — soonest expiration first

### 19.7 Expiring Soon

- [ ] `GET /api/v1/expiring` returns:
  - Access grants where `expires_at` is within 48 hours and `revoked_at IS NULL`
  - Secure links where `expires_at` is within 48 hours and `revoked_at IS NULL`
  - Files where `expires_at` is within 48 hours
  - Grouped by resource type
  - Sorted by soonest expiration

### 19.8 Recently Accessed

- [ ] Aggregate from:
  - Personal vault items: `last_accessed_at` (from Module 06)
  - Team vault items: need to track per-user access (add `vault_item_views` table or use activity log)
  - Files: need to track per-user access
  - Notes: need to track per-user access
- [ ] Create `resource_views` table for tracking:

```
resource_views
  id              -- bigIncrements
  user_id         -- foreignId (constrained, cascadeOnDelete)
  resource_type   -- string (morphs)
  resource_id     -- unsignedBigInteger
  viewed_at       -- timestamp
  created_at

  index(user_id, viewed_at)
  index(resource_type, resource_id)
```

- [ ] Update `ViewTracker` (from Module 14) to also create `resource_views` records

### 19.9 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->group(function () {
    Route::get('v1/search', [SearchController::class, 'search']);
    Route::get('v1/recent', [SearchController::class, 'recent']);
    Route::get('v1/recent/created', [SearchController::class, 'recentCreated']);
    Route::get('v1/expiring', [SearchController::class, 'expiring']);
});
```

---

## Acceptance Criteria

- [ ] `GET /search?q=github` returns matching vault items, files, notes, people, and teams
- [ ] `GET /search?q=github&type=vault_items` returns only vault items
- [ ] Search only returns resources the user has access to
- [ ] Search does not search encrypted fields (content, password, etc.)
- [ ] Results are limited to 20 per type
- [ ] `GET /recent` returns recently accessed items across all types
- [ ] `GET /recent/created` returns recently created items across all types
- [ ] `GET /expiring` returns items with access expiring within 48 hours
- [ ] List endpoints support `type`, `tag`, `folder_id`, `shared`, `archived` filters
- [ ] List endpoints support `sort` parameter with `-` prefix for descending
- [ ] Default sort is `-created_at` (newest first)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_global_search_returns_all_types` | Search returns multiple types |
| `test_search_filtered_by_type` | type=vault_items returns only vault items |
| `test_search_only_returns_accessible_resources` | Non-accessible resources excluded |
| `test_search_does_not_search_encrypted_fields` | Searching for password value returns nothing |
| `test_search_results_limited_per_type` | Max 20 per type |
| `test_recently_accessed` | GET /recent returns recently viewed items |
| `test_recently_created` | GET /recent/created returns newest items |
| `test_expiring_soon` | GET /expiring returns items expiring within 48h |
| `test_filter_by_tag` | tag=ci-cd returns only tagged items |
| `test_filter_by_folder` | folder_id=5 returns only items in folder |
| `test_filter_shared_only` | shared=true returns only shared-with-me items |
| `test_sort_by_name_ascending` | sort=name returns alphabetical |
| `test_sort_by_name_descending` | sort=-name returns reverse |
| `test_default_sort_is_newest` | No sort param → newest first |
| `test_empty_query_returns_empty` | q="" returns empty results |

---

## What This Module Does NOT Include

- Full-text search engine (Meilisearch, Scout) — post-MVP
- Search relevance scoring — post-MVP
- Search suggestions/autocomplete — post-MVP
- Activity logging (Module 20)
