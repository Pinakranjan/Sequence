# Waiter: Serve/Reject KOT Items (with cascading statuses)

## Goal

Add waiter actions **Serve** and **Reject (with reason)** for KOT items. These
actions must update statuses across the related tables:

- `transaction_business_kot_items` (KOT item status + rejection reason + audit)
- `transaction_business_kots` (KOT status + served fields)
- `transaction_business_order_items` (item status)
- `transaction_business_orders` (order_status)

Updates are allowed **only until the order is partially/fully paid**.

## Confirmed decisions

1. **Payment lock (Option A)**: block waiter Serve/Reject when
   `transaction_business_orders.payment_status` is `partial` or `paid`.
2. **Quantity edge cases**: handle separately (Phase 2). Phase 1 ships with
   safe, consistent behavior without attempting perfect per-qty rollups across
   multiple KOT prints.
3. **Timestamps**: current fields are sufficient.
   - Use `transaction_business_kots.served_at`, `served_by` when a whole KOT
     becomes served.
   - Use `transaction_business_kot_items.status_updated_at`, `status_updated_by`
     for per-item audit.
   - Use `transaction_business_kot_items.rejection_remarks` for reject reason.

---

## Current state (already exists)

- Waiter UI already shows Serve/Reject buttons and a reject remarks modal in:
  - `resources/views/admin/waiter/index.blade.php`
- Waiter route/controller already exists for updating a KOT item status:
  - `POST /waiter/kot-item/status` →
    `app/Http/Controllers/Waiter/WaiterOrderController@updateKotItemStatus`

**Gap:** the backend currently updates only `transaction_business_kot_items`,
and does not cascade to `kots`, `order_items`, `orders`.

---

## Phase 1 (implement now): Cascading status updates (no quantity rollups)

### 1) Backend validations (server-side, authoritative)

In `WaiterOrderController@updateKotItemStatus`:

- Validate payload:
  - `kot_item_id` required
  - `status` must be `served` or `rejected`
  - If `rejected`, `rejection_remarks` required (non-empty)
- Load related entities from `kot_item_id` → `kot_id` → `order_id`.
- Enforce payment lock:
  - If order `payment_status` in (`partial`, `paid`) → return 403 with message.
- Enforce waiter authorization:
  - (existing pattern) ensure waiter can act on this order/KOT context.

### 2) Write updates in a DB transaction

Perform all writes in a single transaction:

**2.1 Update KOT item (pivot)**

- `transaction_business_kot_items.item_status = served|rejected`
- `rejection_remarks` only for rejected (clear it on served)
- `status_updated_at = now()`, `status_updated_by = auth()->id()`

**2.2 Recompute KOT status**

- Fetch all `transaction_business_kot_items` rows for this `kot_id`.
- If all are terminal (`served` or `rejected`):
  - set `transaction_business_kots.kot_status = served`
  - set `served_at = now()`, `served_by = auth()->id()`
- Else keep as `pending` (or leave as-is).

**2.3 Update order-item status (simple rule)** Because
quantity/multi-KOT-per-item is deferred, use a conservative rule:

- For the affected `order_item_id`:
  - If it has **any** associated KOT item still `pending` → keep
    `order_items.item_status = preparing` (or leave as-is).
  - Else if it has at least one associated KOT item with `served` → set
    `order_items.item_status = served`.
  - Else if it has only `rejected` → set `order_items.item_status = cancelled`.

This avoids claiming the entire quantity is served when it may be partially
printed/served.

**2.4 Update order header status**

- If all `transaction_business_order_items.item_status` are terminal (`served`
  or `cancelled`):
  - set `transaction_business_orders.order_status = served`
- Else set/keep `order_status = preparing`.

> Note: This keeps order workflow independent of payment. Payment completion to
> `completed` can remain in the payment flow.

### 3) Response payload

Return:

- `success`, message
- updated statuses:
  - `kot_item` status + remarks
  - `kot` status
  - `order` status
  - optionally `affected_order_item` status

---

## Phase 1 UI behavior

In `resources/views/admin/waiter/index.blade.php`:

- Keep confirmation dialogs:
  - Serve: confirm
  - Reject: require reason + confirm
- After success:
  - refresh current order (`loadExistingOrder(currentOrder.id)`)
  - refresh KOT history (`getKotHistory`)
  - refresh pending list (`refreshPendingKotTable()`)

Also ensure server response errors (403 payment lock) show a toast with a clear
message.

---

## Phase 2 (separate): Quantity edge cases

Goal: correct status rollups when an order item is printed in multiple KOTs /
partial quantities.

Plan:

- Treat KOT items as qty-bearing rows (if schema supports it), or derive printed
  vs served qty by comparing:
  - `order_items.quantity`, `kot_printed_qty`, and KOT items served/rejected.
- Define a rollup algorithm producing:
  - `order_items.item_status = preparing/ready/served/cancelled` based on
    per-qty completion.
- Update UI to display partial served (optional) and ensure action buttons
  respect remaining qty.

---

## Chef interface (later)

- Reuse the same transactional cascade logic but with a separate controller +
  guard (chef role).
- Chef may additionally update `prepared_at/prepared_by` and potentially a
  `ready` status if you introduce it for KOT items.

---

## Acceptance criteria (Phase 1)

- Waiter can Serve/Reject a KOT item when order payment_status is `pending`.
- When payment_status becomes `partial` or `paid`, waiter Serve/Reject is
  blocked (UI may hide; server must reject).
- Serve/Reject persists and reflects immediately without full page reload.
- KOT status updates to `served` once all its KOT items are terminal.
- Order item and order header statuses update according to Phase 1 conservative
  rules.
