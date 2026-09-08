# Module 26 — SaaS & Billing: Part 2 (Paystack Integration)

| Field | Value |
|---|---|
| **Module** | 26 |
| **Name** | SaaS & Billing — Paystack Integration |
| **Dependencies** | Module 25 |
| **Status** | ⏭️ Skipped — Open-source project, no billing |

---

## Objective

Integrate Paystack for actual payment processing — subscription creation, upgrades, downgrades, cancellations, webhook handling, invoice generation, and payment verification. This completes the billing system.

---

## PRD Requirements Covered

| ID | Requirement | Priority |
|---|---|---|
| SA-05 | Subscription management (upgrade, downgrade, cancel) | P0 |
| SA-08 | Billing history and invoice download | P1 |

---

## Tasks

### 26.1 PaystackService

- [ ] Create `app/Services/PaystackService.php`:

```php
class PaystackService
{
    public function __construct(
        private string $secretKey,
        private string $publicKey,
    ) {}

    public function createCustomer(string $email, string $name): array
    public function initializeTransaction(string $email, int $amountKobo, string $reference, ?string $planCode = null, string $callbackUrl = null): array
    public function verifyTransaction(string $reference): array
    public function createPlan(string $name, int $amountKobo, string $interval, string $currency = 'NGN'): array
    public function subscribeToPlan(string $customerCode, string $planCode, string $authorizationCode): array
    public function disableSubscription(string $subscriptionCode): array
    public function enableSubscription(string $subscriptionCode): array
    public function getSubscription(string $subscriptionCode): array
    public function listInvoices(string $customerCode): array
    public function verifyWebhookSignature(string $signature, string $payload): bool
}
```

- [ ] Use Laravel HTTP client to call Paystack REST API
- [ ] Base URL: `https://api.paystack.co`
- [ ] Authorization: `Bearer {secret_key}`
- [ ] Register as singleton in `AppServiceProvider`

### 26.2 Environment Configuration

- [ ] Add to `.env`:

```
PAYSTACK_SECRET_KEY=sk_test_xxxxx
PAYSTACK_PUBLIC_KEY=pk_test_xxxxx
PAYSTACK_WEBHOOK_URL=https://api.zekura.app/api/v1/billing/webhook
PAYSTACK_CALLBACK_URL=https://app.zekura.app/billing/callback
```

- [ ] Add to `config/services.php`:

```php
'paystack' => [
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'base_url' => 'https://api.paystack.co',
    'webhook_url' => env('PAYSTACK_WEBHOOK_URL'),
    'callback_url' => env('PAYSTACK_CALLBACK_URL'),
],
```

### 26.3 Plan Codes

- [ ] Create Paystack plans for each Zekura plan (one-time setup):

```php
// Artisan command: php artisan paystack:create-plans
$paystack->createPlan('Zekura Team', 49900, 'monthly', 'USD');    // $4.99 = 49900 kobo
$paystack->createPlan('Zekura Business', 199900, 'monthly', 'USD'); // $19.99
$paystack->createPlan('Zekura Enterprise', 499900, 'monthly', 'USD'); // $49.99
```

- [ ] Store Paystack plan codes in config or database:

```php
// config/paystack.php
'plans' => [
    'team' => env('PAYSTACK_PLAN_TEAM', 'PLN_xxxxx'),
    'business' => env('PAYSTACK_PLAN_BUSINESS', 'PLN_xxxxx'),
    'enterprise' => env('PAYSTACK_PLAN_ENTERPRISE', 'PLN_xxxxx'),
],
```

### 26.4 Billing Invoices Table

- [ ] Create `billing_invoices` migration:

```
billing_invoices
  id                  -- bigIncrements
  tenant_id           -- foreignId (constrained, cascadeOnDelete)
  subscription_id     -- foreignId, nullable (constrained, nullOnDelete)
  paystack_invoice_id -- string, nullable
  amount              -- decimal(10,2)
  currency            -- string, default 'USD'
  status              -- enum: 'paid', 'pending', 'failed'
  paid_at             -- timestamp, nullable
  period_start        -- timestamp, nullable
  period_end          -- timestamp, nullable
  pdf_url             -- string, nullable (Paystack receipt URL)
  created_at
  updated_at

  index(tenant_id)
  index(subscription_id)
  index(status)
```

- [ ] Create `BillingInvoice` model with relationships

### 26.5 Subscription Flow

```
1. POST /api/v1/billing/subscription { plan: "team" }
   → PaystackService::createCustomer() (if not exists)
   → PaystackService::initializeTransaction() with plan_code
   → Return: { authorization_url } — user redirected to Paystack

2. User completes payment on Paystack

3. Paystack redirects to callback URL with reference
   → GET /api/v1/billing/callback?reference=xxx
   → PaystackService::verifyTransaction(reference)
   → If successful: update subscription status, create invoice
   → Redirect to frontend

4. Paystack sends webhook
   → POST /api/v1/billing/webhook
   → Verify signature
   → Process event (see 26.7)
```

### 26.6 Update BillingController

- [ ] `subscribe()`:
  - Validate plan
  - Create/verify Paystack customer
  - Initialize transaction with Paystack
  - Return authorization URL for frontend redirect

- [ ] `cancel()`:
  - Get Paystack subscription code from DB
  - Call `PaystackService::disableSubscription()`
  - Update subscription status to `canceled`
  - Set `canceled_at`

- [ ] `callback()`:
  - Verify transaction reference
  - Update subscription and create invoice

- [ ] `invoices()`:
  - List `billing_invoices` for tenant
  - Return paginated results

### 26.7 Webhook Handler

- [ ] `POST /api/v1/billing/webhook` (unauthenticated, signature-verified):

| Event | Action |
|---|---|
| `subscription.create` | Record new subscription, update status to `active` |
| `subscription.disable` | Mark subscription `canceled`, update tenant plan to `free` |
| `subscription.enable` | Mark subscription `active` |
| `invoice.create` | Store invoice record in `billing_invoices` |
| `invoice.payment_failed` | Mark invoice `failed`, alert tenant owner |
| `charge.success` | Record payment, update invoice to `paid`, update subscription period |
| `charge.failed` | Log failure, alert tenant owner |

- [ ] Create `app/Http/Controllers/Api/V1/PaystackWebhookController.php`:
  - Verify signature: `PaystackService::verifyWebhookSignature()`
  - Parse event type
  - Dispatch `ProcessPaystackWebhook` job for async processing
  - Return `200 OK` immediately (don't block)

### 26.8 ProcessPaystackWebhook Job

- [ ] `app/Jobs/ProcessPaystackWebhook.php` (implements `ShouldQueue`):
  - Queue: `billing`
  - Process event based on type
  - Update subscription, create invoices, send notifications
  - Log activity

### 26.9 Plan Upgrade/Downgrade

- [ ] `subscribe()` handles both new subscriptions and plan changes:
  - If active subscription exists:
    - Disable old Paystack subscription
    - Initialize new transaction with new plan code
    - On success: update subscription plan
  - Proration: Paystack handles proration automatically

### 26.10 Notifications

- [ ] `app/Notifications/PaymentSuccessful.php` — sent on `charge.success`
- [ ] `app/Notifications/PaymentFailed.php` — sent on `charge.failed`
- [ ] `app/Notifications/SubscriptionCanceled.php` — sent on subscription cancel
- [ ] `app/Notifications/SubscriptionActivated.php` — sent on subscription create/enable

### 26.11 API Resources

- [ ] `BillingInvoiceResource.php`:
  - `id`, `amount`, `currency`, `status`, `paid_at`, `period_start`, `period_end`, `pdf_url`, `created_at`

### 26.12 Routes

```php
// Webhook (unauthenticated)
Route::post('v1/billing/webhook', [PaystackWebhookController::class, 'handle']);

// Callback (unauthenticated, verifies reference)
Route::get('v1/billing/callback', [BillingController::class, 'callback']);

// Authenticated
Route::middleware(['auth:sanctum', 'tenant.resolve'])->prefix('v1/billing')->group(function () {
    Route::get('subscription', [BillingController::class, 'subscription']);
    Route::post('subscription', [BillingController::class, 'subscribe']);
    Route::delete('subscription', [BillingController::class, 'cancel']);
    Route::get('plans', [BillingController::class, 'plans']);
    Route::get('usage', [BillingController::class, 'usage']);
    Route::get('invoices', [BillingController::class, 'invoices']);
    Route::get('invoices/{invoice}', [BillingController::class, 'showInvoice']);
});
```

### 26.13 Artisan Commands

- [ ] `php artisan paystack:create-plans` — create Paystack plans for each tier
- [ ] `php artisan paystack:sync-subscriptions` — sync subscription status from Paystack

---

## Acceptance Criteria

- [ ] `PaystackService` correctly calls Paystack API endpoints
- [ ] `POST /billing/subscription` returns Paystack authorization URL
- [ ] User can complete payment on Paystack and is redirected back
- [ ] Callback verifies transaction and updates subscription
- [ ] Webhook endpoint verifies Paystack signature
- [ ] `subscription.create` webhook activates subscription
- [ ] `subscription.disable` webhook cancels subscription and downgrades to free
- [ ] `charge.success` webhook creates invoice and marks as paid
- [ ] `charge.failed` webhook marks invoice as failed and notifies owner
- [ ] `invoice.create` webhook stores invoice record
- [ ] Webhook processing is asynchronous (via `ProcessPaystackWebhook` job)
- [ ] User can list billing invoices
- [ ] User can view individual invoice with PDF URL
- [ ] Canceling subscription calls Paystack API to disable
- [ ] Plan upgrade initializes new Paystack transaction
- [ ] Payment success notifications sent to tenant owner
- [ ] Payment failure notifications sent to tenant owner
- [ ] Paystack secret key is in `.env` (never hardcoded)

---

## Tests to Write

| Test | What it verifies |
|---|---|
| `test_subscribe_returns_authorization_url` | POST returns Paystack URL |
| `test_callback_verifies_transaction` | GET with reference verifies and updates |
| `test_webhook_verifies_signature` | Invalid signature → 401 |
| `test_webhook_subscription_create` | Event activates subscription |
| `test_webhook_subscription_disable` | Event cancels subscription |
| `test_webhook_charge_success` | Event creates paid invoice |
| `test_webhook_charge_failed` | Event creates failed invoice + notification |
| `test_webhook_invoice_create` | Event stores invoice |
| `test_can_list_invoices` | GET returns invoices |
| `test_can_view_invoice` | GET returns invoice with PDF URL |
| `test_cancel_calls_paystack` | DELETE calls disableSubscription |
| `test_plan_upgrade_works` | POST with new plan initializes new transaction |
| `test_payment_success_notification_sent` | Notification sent on charge.success |
| `test_payment_failed_notification_sent` | Notification sent on charge.failed |
| `test_webhook_processing_is_async` | Job dispatched, 200 returned immediately |
| `test_secret_key_not_exposed` | Key never in any API response |

---

## What This Module Does NOT Include

- Refund processing (manual via Paystack dashboard)
- Multi-currency support beyond what Paystack provides
- Tax calculation (Paystack handles where applicable)
- Custom billing cycles (only monthly via Paystack plans)
- Usage-based billing (fixed plan pricing only)
- Dunning management (Paystack handles retries)
