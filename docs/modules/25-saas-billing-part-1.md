# Module 25 — SaaS & Billing: Part 1 (Plans & Limits)

| Field | Value |
|---|---|
| **Module** | 25 |
| **Name** | SaaS & Billing — Plans & Limits |
| **Dependencies** | Module 03, Module 04 |
| **Status** | Not Started |

---

## Objective

Define subscription plans (Free, Team, Business, Enterprise), enforce plan limits (members, storage, vault items), and build the plan management endpoints. This module sets up the data model for billing — Paystack integration is in Module 26.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SA-01 | Free plan: 1 personal vault, limited storage, limited items | P0 |
| SA-02 | Team plan: company workspace, up to 10 members, increased storage | P0 |
| SA-03 | Business plan: up to 100 members, advanced permissions, API access | P1 |
| SA-04 | Enterprise plan: unlimited members, SSO, directory integration | P2 |
| SA-05 | Subscription management (upgrade, downgrade, cancel) | P0 |
| SA-06 | Storage, member, and vault limits enforced per plan | P0 |
| SA-07 | Usage dashboard | P1 → Module 24 |

---

## Tasks

### 25.1 Plan Definitions

- [ ] Create `app/Enums/Plan.php`:

```php
enum Plan: string
{
    case Free = 'free';
    case Team = 'team';
    case Business = 'business';
    case Enterprise = 'enterprise';

    public function limits(): array
    {
        return match($this) {
            self::Free => [
                'max_members' => 1,
                'max_storage_mb' => 50,
                'max_vault_items' => 50,
                'max_files' => 10,
                'api_access' => false,
                'advanced_permissions' => false,
            ],
            self::Team => [
                'max_members' => 10,
                'max_storage_mb' => 1024,
                'max_vault_items' => 500,
                'max_files' => 200,
                'api_access' => false,
                'advanced_permissions' => false,
            ],
            self::Business => [
                'max_members' => 100,
                'max_storage_mb' => 10240,
                'max_vault_items' => 5000,
                'max_files' => 1000,
                'api_access' => true,
                'advanced_permissions' => true,
            ],
            self::Enterprise => [
                'max_members' => null, // unlimited
                'max_storage_mb' => null,
                'max_vault_items' => null,
                'max_files' => null,
                'api_access' => true,
                'advanced_permissions' => true,
            ],
        };
    }

    public function price(): float
    {
        return match($this) {
            self::Free => 0,
            self::Team => 4.99,
            self::Business => 19.99,
            self::Enterprise => 49.99,
        };
    }

    public function currency(): string
    {
        return 'USD';
    }
}
```

### 25.2 Subscriptions Table & Model

- [ ] Create `subscriptions` migration:

```
subscriptions
  id                  -- bigIncrements
  tenant_id           -- foreignId (constrained, cascadeOnDelete)
  plan                -- enum: 'free', 'team', 'business', 'enterprise'
  status              -- enum: 'active', 'canceled', 'past_due', 'trialing'
  trial_ends_at       -- timestamp, nullable
  current_period_start -- timestamp, nullable
  current_period_end  -- timestamp, nullable
  canceled_at         -- timestamp, nullable
  paystack_subscription_code -- string, nullable (linked in Module 26)
  paystack_customer_code -- string, nullable
  created_at
  updated_at

  unique(tenant_id) -- one subscription per tenant
  index(status)
```

- [ ] Create `Subscription` model:
  - `$fillable`: all columns above
  - `$casts`: `trial_ends_at` → `datetime`, `current_period_start` → `datetime`, `current_period_end` → `datetime`, `canceled_at` → `datetime`
  - Relationship: `tenant()` → `belongsTo(Tenant::class)`
  - Helper: `isActive(): bool`
  - Helper: `isOnTrial(): bool`
  - Helper: `plan(): Plan`

### 25.3 Update Tenant Model

- [ ] Add relationship: `subscription()` → `hasOne(Subscription::class)`
- [ ] Add helper: `currentPlan(): Plan` — returns plan from subscription or `Free`
- [ ] Add helper: `planLimits(): array` — returns limits for current plan

### 25.4 PlanLimitService

- [ ] Create `app/Services/PlanLimitService.php`:

```php
class PlanLimitService
{
    public function canAddMember(Tenant $tenant): bool
    public function canAddVaultItem(Tenant $tenant): bool
    public function canUploadFile(Tenant $tenant, int $fileSizeBytes): bool
    public function canAddNote(Tenant $tenant): bool
    public function getUsage(Tenant $tenant): array
    public function getLimits(Tenant $tenant): array
    public function enforceLimit(Tenant $tenant, string $resourceType): void
}
```

- `canAddMember()` — check `members_count < max_members`
- `canAddVaultItem()` — check `vault_items_count < max_vault_items`
- `canUploadFile()` — check `total_storage + file_size < max_storage`
- `enforceLimit()` — throws `PlanLimitExceededException` if limit reached

### 25.5 Integrate Limit Checks

- [ ] Add limit checks to:
  - `InviteEmployeeAction` — check `canAddMember()` before inviting
  - `CreateVaultItemAction` — check `canAddVaultItem()` before creating
  - `UploadFileAction` — check `canUploadFile()` before uploading
  - `CreateNoteAction` — check `canAddNote()` (if applicable)

- [ ] Return `402 Payment Required` or `429 Too Many Requests` when limit exceeded:

```json
{
  "error": {
    "code": "PLAN_LIMIT_EXCEEDED",
    "message": "You have reached the maximum number of vault items for your plan.",
    "limit": 50,
    "current": 50,
    "plan": "free",
    "upgrade_url": "/api/v1/billing/subscription"
  }
}
```

### 25.6 API Endpoints — Subscription Management

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/billing/subscription` | Current subscription details |
| `POST` | `/api/v1/billing/subscription` | Create/upgrade subscription |
| `DELETE` | `/api/v1/billing/subscription` | Cancel subscription |
| `GET` | `/api/v1/billing/plans` | List available plans |
| `GET` | `/api/v1/billing/usage` | Usage stats (from Module 24) |
| `GET` | `/api/v1/billing/invoices` | List invoices (Module 26) |

### 25.7 Controller

- [ ] `app/Http/Controllers/Api/V1/BillingController.php`:
  - `subscription()` — return current subscription + plan + limits + usage
  - `plans()` — return all available plans with features and pricing
  - `subscribe()` — create/upgrade subscription (stub — Paystack integration in Module 26)
  - `cancel()` — cancel subscription (stub — Paystack integration in Module 26)

### 25.8 API Resources

- [ ] `SubscriptionResource.php`:
  - `id`, `plan`, `status`, `trial_ends_at`, `current_period_start`, `current_period_end`, `canceled_at`, `limits`, `usage`

- [ ] `PlanResource.php`:
  - `name`, `price`, `currency`, `features` (limits as features), `is_current`

### 25.9 Form Requests

- [ ] `SubscribeRequest`:
  - `plan`: required, in:team,business,enterprise (cannot subscribe to free)
  - `payment_method`: nullable (Paystack integration in Module 26)

### 25.10 Routes

```php
Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('v1/billing')->group(function () {
    Route::get('subscription', [BillingController::class, 'subscription']);
    Route::post('subscription', [BillingController::class, 'subscribe']);
    Route::delete('subscription', [BillingController::class, 'cancel']);
    Route::get('plans', [BillingController::class, 'plans']);
    Route::get('usage', [BillingController::class, 'usage']);
    Route::get('invoices', [BillingController::class, 'invoices']);
});
```

### 25.11 Create Free Subscription on Tenant Creation

- [ ] Update `Tenant` creation (Module 03) to auto-create a `Free` subscription:

```php
Subscription::create([
    'tenant_id' => $tenant->id,
    'plan' => Plan::Free->value,
    'status' => 'active',
]);
```

---

## Acceptance Criteria

- [ ] New tenants get a Free subscription automatically
- [ ] `GET /billing/plans` returns all 4 plans with features and pricing
- [ ] `GET /billing/subscription` returns current subscription with limits and usage
- [ ] Cannot invite members beyond plan limit → `402`
- [ ] Cannot create vault items beyond plan limit → `402`
- [ ] Cannot upload files beyond storage limit → `402`
- [ ] Limit exceeded response includes upgrade URL
- [ ] `POST /billing/subscription` creates a new subscription (stub for Paystack)
- [ ] `DELETE /billing/subscription` cancels subscription (stub for Paystack)
- [ ] Free plan has 1 member, 50 items, 50 MB storage limits
- [ ] Team plan has 10 members, 500 items, 1 GB storage limits

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_new_tenant_gets_free_subscription` | Auto-created on tenant creation |
| `test_can_list_plans` | GET returns 4 plans |
| `test_can_view_subscription` | GET returns current subscription |
| `test_free_plan_member_limit` | 2nd member invite → 402 |
| `test_team_plan_member_limit` | 11th member → 402 |
| `test_vault_item_limit_enforced` | Over limit → 402 |
| `test_storage_limit_enforced` | Over limit → 402 |
| `test_limit_response_includes_upgrade_url` | Error includes upgrade URL |
| `test_can_subscribe_to_plan` | POST creates subscription (stub) |
| `test_can_cancel_subscription` | DELETE cancels (stub) |
| `test_usage_calculated_correctly` | Usage counts match actual |

---

## What This Module Does NOT Include

- Paystack payment integration (Module 26)
- Invoice generation and download (Module 26)
- Webhook handling (Module 26)
- Payment method management (Module 26)
- Proration on plan change (Module 26)
