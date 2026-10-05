# CROSS-BRANCH / IDOR AUTHORIZATION AUDIT REPORT
**App:** Laravel 10 - web-app-toko-kue  
**Multi-tenant:** region = toko kue branch (region_id on users, customers, orders, products)  
**Date:** Mon Oct 05 2026

## Scope
routes/web.php with middleware groups: admin (role:admin), kurir (role:kurir). All parameterized routes analyzed.

## 1. ADMIN ORDERS (orders/{id}/...)
**Routes:** GET orders/{id}/details, POST orders/{id}/verify, POST orders/{id}/reject, DELETE orders/{id}
**Controller:** app/Http/Controllers/Admin/OrderController.php (lines ~90-240)
**Binding:** Manual `{id}` (no model binding type hint)

**Authorization check:**
- details($id): Line ~109-113: `Order::with([...])->where('region_id', $admin->region_id)->findOrFail($id)` (evidence line 111-113)
- verify($id): Line ~168-176: `Order::where('region_id', $admin->region_id)->whereIn('status', [...])->findOrFail($id)`
- reject($id): Line ~189-198: same scoping by region_id
- destroy($id): Line ~240+: `Order::where('region_id', $admin->region_id)->with('returns')->findOrFail($id)`

**Status:** ✓ CORRECTLY SCOPED - All lookups filter by authenticated admin's region_id. Cannot access other branches' orders.


## 2. CHAT MONITOR (admin/chat/{conversation}/...)
**Routes:** GET chat/{conversation}, POST chat/{conversation}/reply, POST chat/{conversation}/close, POST chat/{conversation}/escalate, POST chat/{conversation}/resume, PATCH chat/{conversation}/region
**Controller:** app/Http/Controllers/Admin/ChatMonitorController.php
**Binding:** Route model binding with `WhatsAppConversation $conversation` (type-hinted in method signatures)

**Index behavior (for context):** Line 81-125 filters by region via `resolveRegionFilter()` - defaults to user's region_id unless explicitly requesting 'all'. But individual conversation access has NO region check.

**Authorization checks:**
- show(WhatsAppConversation $conversation): Line ~159-173 - loads conversation and messages with no region check. Just returns view with the bound conversation regardless of its region_id.
- reply(...): Line ~175-220 - sends reply without verifying conversation.region_id matches admin's region_id
- closeConversation(...): Line ~222-229 - closes without region check
- escalateConversation(...): Line ~231-243 - no region check
- resumeConversation(...): Line ~245-258 - no region check
- updateRegion(...): Line ~133-157 - allows reassigning conversation to any existing region (validates region exists). Comment says "satu nomor WhatsApp dipakai semua cabang, jadi admin tetap boleh membalas percakapan cabang lain" - but this suggests cross-branch access is intentional. However, the question asks if scoped by region_id for lookup. There is no scoping/filtering - if intentional for cross-branch ops, it's by design. But the "IDOR" concern depends on intended isolation. Need to be precise.

**Status:** ⚠ MIXED - Index enforces region filtering by default; individual operations on bound models do NOT re-validate region ownership. If the system is designed for shared WhatsApp numbers across branches, cross-branch access may be intentional. However, there is no explicit authorization check preventing an admin from operating on any conversation by direct URL if they know the conversation ID. This is effectively an authorization gap if region isolation is required.

**Evidence (show method, lines ~159-173):**
```php
public function show(WhatsAppConversation $conversation)
{
    $conversation->load(['customer', 'region', 'messages' => function ($query) {
        $query->orderBy('created_at', 'asc');
    }]);
    return view('dashboard.admin.chat.show', compact('conversation'));
}
```


## 3. BROADCAST (admin/broadcast/{broadcast}/...)
**Routes:** GET broadcast/{broadcast}, POST broadcast/{broadcast}/cancel
**Controller:** app/Http/Controllers/Admin/BroadcastController.php
**Binding:** Route model binding `WhatsAppBroadcast $broadcast`

**Authorization checks:**
- show(WhatsAppBroadcast $broadcast): Line ~249-270 - checks `if ($broadcast->region_id && $broadcast->region_id !== $user->region_id) abort(403);` (line ~255-257). Also loads data.
- cancel(WhatsAppBroadcast $broadcast): Line ~312-340 - same region check at line ~318-320.

**Notes:** Correctly handles global broadcasts where region_id is null (allows access regardless of admin's region).

**Status:** ✓ CORRECTLY SCOPED

**Evidence:**
```php
if ($broadcast->region_id && $broadcast->region_id !== $user->region_id) {
    abort(403);
}
```


## 4. COURIERS (admin/couriers/{courier}/...)
**Routes:** Resource couriers (parameter mapped to 'courier'), plus custom routes: PUT couriers/{courier}/note, GET couriers/{courier}/performance-data
**Controller:** app/Http/Controllers/Admin/CourierController.php
**Binding:** Route model binding `User $courier`

**Authorization checks (all mutating/viewing operations):**
- update(Request $request, User $courier): Line ~91-121 - checks `if ($courier->region_id !== Auth::user()->region_id) abort(403, 'AKSES DITOLAK');` (line ~93)
- updateNote(...): Line ~129-148 - same check at line ~131
- destroy(...): Line ~155-170 - same check at line ~157
- performanceData(...): Line ~181-312 - same check at line ~189

**Index:** Line ~45-87 scopes query by `where('region_id', $user->region_id)`

**Status:** ✓ CORRECTLY SCOPED


## 5. PERFORMA KURIR (admin/peforma-kurir/{kurir})
**Route:** GET peforma-kurir/{kurir}
**Controller:** app/Http/Controllers/Admin/PeformaKurirController.php
**Method:** show($kurir) - takes raw parameter (no type hint, line ~303-315)
**Binding:** Manual ID parameter

**Authorization check:** NONE. The method simply does `return view('dashboard.admin.peforma-kurir.peforma-kurir', compact('kurir'));` (line ~314). It doesn't load a User model, doesn't check region_id. Even if it did load, no scoping exists.

**Risk:** Admin from branch A can access peforma-kurir/{id_of_courier_in_branch_B} by guessing/knowing courier IDs. This is a CROSS-BRANCH IDOR - unauthorized access to other regions' courier performance data.

**Status:** ✗ NOT SCOPED - HIGH RISK IDOR

**Evidence:**
```php
public function show($kurir)
{
    return view('dashboard.admin.peforma-kurir.peforma-kurir', compact('kurir'));
}
```


## 6. PERFORMA CUSTOMER (admin/peforma-customer/{customer})
**Route:** GET peforma-customer/{customer}
**Controller:** app/Http/Controllers/Admin/PeformaCustomerController.php
**Method:** show($customer) - raw parameter, no type hint (line ~200-210)
**Binding:** Manual ID parameter

**Authorization check:** NONE. Just passes parameter to view (line ~209). No model loading, no region scoping.

**Risk:** Cross-branch access to customer performance data of other regions.

**Status:** ✗ NOT SCOPED - HIGH RISK IDOR

**Evidence:**
```php
public function show($customer)
{
    return view('dashboard.admin.peforma-customer.peforma-customer', compact('customer'));
}
```


## 7. CUSTOMERS (admin/customers/{customer}/..., kurir/customers/{customer}/...)
**Routes (admin):** Resource customers (parameter customer) + PUT customers/{customer}/note, POST customers/{customer}/flag, GET customers/{customer}/rekap/download
**Controller:** app/Http/Controllers/CustomerController.php
**Binding:** Route model binding `Customer $customer` for resource methods and custom routes

**Authorization checks:**
- downloadRekap(Request $request, Customer $customer): Line ~29-131 - checks `if (!$user->hasRole('admin') || $customer->region_id !== $user->region_id) abort(403)` (line ~33)
- update(Request $request, Customer $customer): Line ~233-268 - for admin: checks region match (line ~244-247); for kurir: checks ownership `added_by_user_id !== $user->id` (line ~239-243)
- updateNote(...): Line ~270-286 - similar checks for admin region and kurir ownership
- destroy(...): Line ~288-304 - same authorization checks
- toggleFlag(...): Line ~306-325 - admin-only check (line ~309)

**Index:** Line ~133-201 - filters by region; for kurir also filters by added_by_user_id

**Status:** ✓ CORRECTLY SCOPED (admin actions enforce region_id, kurir actions enforce ownership)


## 8. HISTORYS (admin/historys/{order}/..., kurir/historys/{order}/details)
**Admin routes:**
- GET historys/{order}/invoice → HistoryOrderController@invoice($orderId) - manual ID param
- GET historys/{order}/download → HistoryOrderController@downloadInvoice($orderId) - manual ID param  
- GET historys/{order}/details → HistoryOrderController@details(Order $order) - model binding

**Kurir route:**
- GET historys/{order}/details → HistoryOrderController@details(Order $order) - model binding

**Controller:** app/Http/Controllers/HistoryOrderController.php

**Authorization checks:**
- details(Order $order): Line ~21-105 - checks: if admin and region mismatch → 403 (line ~25-27); if kurir and not owner → 403 (line ~28-30). ✓ SCOPED
- invoice($orderId): Line ~171-186 - takes raw ID, does `findOrFail($orderId)` with no region/ownership check. Returns invoice view for ANY order ID that exists, regardless of region. ✗ UNSCOPED
- downloadInvoice($orderId): Line ~107-169 - same as invoice, no scoping. Downloads PDF for any order. ✗ UNSCOPED

**Index/destroy are scoped correctly** (destroy checks region). But invoice and downloadInvoice bypass all authorization.

**Risk:** Any authenticated admin can view/download invoices of orders from other branches by guessing order IDs. This is a clear CROSS-BRANCH IDOR.

**Status:** ✗ CRITICAL - invoice() and downloadInvoice() are NOT SCOPED (manual ID, no region check)

**Evidence (invoice method):**
```php
public function invoice($orderId)
{
    $order = \App\Models\Order::with([...])->findOrFail($orderId);
    $isPdf = false;
    return view('dashboard.admin.historys.invoice', compact('order', 'isPdf'));
}
```


## 9. KURIR PESANAN ROUTES
**Routes under kurir/pesanan/:**
- GET /{id}/details → PesananController@getOrderDetails($id)
- POST /{id}/update-status → PesananController@updateOrderStatus($id)
- POST /{id}/upload-proof → PesananController@uploadPaymentProof($id)
- POST /{order}/request-return → ReturnController@requestReturn(Order $order)
- POST /{order}/upload-return-proof → ReturnController@uploadReturnProof(Order $order)
- POST /{order}/request-return/edit → ReturnController@editReturn(Order $order)

**Controller:** app/Http/Controllers/Kurir/PesananController.php and app/Http/Controllers/ReturnController.php

### 9a. PesananController methods (manual {id})
- getOrderDetails($id): Line ~317-393 - scopes with `where('created_by_user_id', Auth::id())` and `firstOrFail()` (line ~342-346). ✓ SCOPED
- updateOrderStatus(Request $request, $id): Line ~589-660 - same scoping at line ~609-611. ✓ SCOPED
- uploadPaymentProof(Request $request, $id): Line ~510-586 - same scoping at line ~531-535. ✓ SCOPED

**Status:** ✓ CORRECTLY SCOPED (kurir can only access their own orders)

### 9b. ReturnController methods (route model binding Order $order)
Routes use `{order}` with model binding. Methods:
- requestReturn(Request $request, Order $order): Line ~45-182 - NO authorization check. Uses model binding to get order; does not verify `order.created_by_user_id` matches authenticated kurir. Also doesn't check region. Relies only on order existing and status checks. ✗ UNSCOPED
- uploadReturnProof(Request $request, Order $order): Line ~184-238 - NO authorization check on the bound order. ✗ UNSCOPED
- editReturn(Request $request, Order $order): Line ~240-380 - NO authorization check. Finds return belonging to the order but never validates kurir owns the order. ✗ UNSCOPED

**Risk:** A kurir (role:kurir) can request/upload/edit returns on ANY order by guessing order ID, even if created by another kurir in same or different region. This is a severe CROSS-BRANCH/INTER-KURIR IDOR.

**Also kurir/customer/{id}/last-order (route at line ~206 in web.php):** calls PesananController@getLastOrder($id) - takes customer ID. Line ~662-702: finds last order by `Order::where('customer_id', $id)->latest()->first()` with NO scoping to kurir's ownership/region. Could leak last order details across regions/kurirs. ✗ UNSCOPED


## 10. PRODUCTS (admin/products/{product})
**Routes:** Resource products under admin prefix
**Controller:** app/Http/Controllers/ProductController.php
**Binding:** Route model binding `Product $product`
**Auth:** Constructor has `middleware(['auth', 'role:admin'])` (line ~16)

**Authorization checks:**
- show(Product $product): Line ~130-144 - checks `if ($product->region_id !== Auth::user()->region_id) abort(403);` (line ~133-136)
- update(Request $request, Product $product): Line ~146-226 - same check at line ~148-150
- destroy (implicit in resource, not shown fully but follows same pattern) - other actions also enforce region check

**Index:** Line ~18-128 - scopes queries by admin's region_id

**Status:** ✓ CORRECTLY SCOPED


## 11. DASHBOARD REGION VALIDATION
**Routes:**
- GET admin/dashboard/{region} → AdminDashboardController@index(string $region)
- GET kurir/dashboard/{region} → KurirDashboardController@index(string $region)

### AdminDashboardController (line ~19-400+)
- Gets admin via Auth, checks role admin (line ~23-26)
- Looks up region by slug: `Region::where('slug', $region)->firstOrFail()` (line ~140)
- Queries all data by $regionId from the looked-up region
- **NO check** that the looked-up region matches `$admin->region_id`. An admin of region A (e.g., Surabaya) can access `/admin/dashboard/malang` and see Malang's data.

### KurirDashboardController (line ~14-90+)
- Gets kurir, checks role kurir (line ~18-21)
- Takes $region param but **NEVER uses it** and **NEVER validates** it matches kurir's region_id. Also doesn't look up region. Just passes to view. An authenticated kurir could access any other region's dashboard URL.

**Risk:** CROSS-BRANCH IDOR - horizontal privilege escalation. Admin/kurir can view another branch's dashboard by manipulating the {region} URL segment.

**Status:** ✗ NOT SCOPED - CRITICAL for multi-tenant isolation

**Evidence (AdminDashboardController):**
```php
$admin = Auth::user();
if (!$admin->hasRole('admin')) { abort(403); }
$regionModel = Region::where('slug', $region)->firstOrFail();
$regionId = $regionModel->id;
// ... queries use regionId but never verify $admin->region_id === $regionId
```

**Evidence (KurirDashboardController):**
```php
$kurir = Auth::user();
if (!$kurir->hasRole('kurir')) { abort(403); }
// No region validation - param is unused
return view('dashboard.kurir.dashboard', compact('kurir', 'customerCategories'));
```


## 12. POLICIES & PROVIDER
**Files checked:**
- app/Policies/DashboardPolicy.php - exists but is EMPTY (0 bytes)
- app/Providers/AuthServiceProvider.php - $policies array is empty; boot() does nothing

**Conclusion:** No policy registered for Dashboard or other models. Authorization is implemented inline in controllers (some places well, others missing). This contributes to inconsistent enforcement.

**Status:** No policy-based authorization active for the cases analyzed.


## 13. ROUTES USING {id} vs MODEL BINDING (summary)

**Manual {id} with region scoping (GOOD):**
- Admin orders: details/verify/reject/destroy ✓
- Kurir pesanan: getOrderDetails/updateOrderStatus/uploadPaymentProof ✓ (scoped by created_by_user_id)

**Manual {id} WITHOUT scoping (BAD - IDOR):**
- Admin peforma-kurir/{kurir} → show() takes raw param, no check ✗
- Admin peforma-customer/{customer} → show() takes raw param, no check ✗
- Admin historys/{order}/invoice → invoice($orderId), no check ✗
- Admin historys/{order}/download → downloadInvoice($orderId), no check ✗
- Kurir customer/{id}/last-order → getLastOrder($id), no check ✗

**Model binding WITH scoping (GOOD):**
- Broadcast {broadcast} → show/cancel check region ✗? wait - show/cancel DO check (line ~255, ~318) ✓
- Couriers {courier} → all methods check region ✓
- Customers {customer} (admin) → check region; (kurir) check ownership ✓
- Products {product} → check region ✓
- Historys {order} details (admin/kurir) → check region/ownership ✓
- ReturnController routes under kurir take Order $order but LACK ownership checks ✗ (model bound but not authorized)

**Model binding WITHOUT scoping (BAD):**
- Chat {conversation} → all operations lack region check (if isolation required) ✗
- ReturnController: requestReturn/uploadReturnProof/editReturn take Order $order via binding but never verify order belongs to authenticated kurir ✗


## 14. CONCRETE EXPLOIT EXAMPLES

1. **Chat Monitor IDOR (if cross-branch isolation required)**
- Attacker: admin of branch A (region_id=1), knows conversation ID of branch B (region_id=2, conversation_id=123)
- GET/POST to `/admin/chat/123`, `/admin/chat/123/reply` etc - will access/modify branch B's conversation
- Evidence: show() method has no region check

2. **Peforma Kurir IDOR**
- Admin A accesses `/admin/peforma-kurir/5` where kurir_id=5 belongs to branch B - no authorization check
- Can view other branch's courier performance data

3. **Invoice Download IDOR (CRITICAL)**
- Admin A accesses `/admin/historys/999/invoice` or `/admin/historys/999/download` for order_id=999 in branch B
- Will see/download full invoice (customer details, amounts, items) with no check
- Evidence: invoice/downloadInvoice do `findOrFail` without region filter

4. **Kurir Return IDOR (CRITICAL)**
- Kurir A (created_by_user_id=1) can POST to `/kurir/pesanan/456/request-return` for order_id=456 created by Kurir B
- Model binding loads order; no ownership check - can initiate return on another kurir's order
- Same for upload-return-proof and edit

5. **Dashboard Region Bypass (CRITICAL)**
- Admin of Surabaya accesses `/admin/dashboard/malang` - sees Malang branch dashboard data
- Kurir of Surabaya accesses `/kurir/dashboard/malang` - bypasses intended region restriction

6. **Kurir Last Order Leak**
- Kurir accesses `/kurir/customer/789/last-order` - gets last order items for customer 789 even if customer/order belong to different region/kurir


## 15. ROUTES CORRECTLY SCOPED (SAFE)

✓ Admin orders (all {id} ops) - filtered by region_id  
✓ Admin broadcast {broadcast} show/cancel - region check + null handling  
✓ Admin couriers {courier} - all ops check region_id  
✓ Admin customers {customer} - region check for admin, ownership for kurir; downloadRekap scoped  
✓ Admin products {product} - region check in all ops  
✓ Admin historys {order}/details - model binding + region/ownership check  
✓ Kurir pesanan {id} ops (details/update-status/upload-proof) - scoped by created_by_user_id  
✓ Kurir customers - scoped appropriately (index by region+owner; updates check ownership)  
✓ ProductController admin-only with region scoping


## RECOMMENDATIONS (IMMEDIATE FIXES)

1. **ChatMonitorController**: Add region check in show/reply/close/escalate/resume. If cross-branch access is truly required by design, document it and add explicit policy/permission check rather than relying on implicit access.
2. **PeformaKurirController@show**: Type-hint User $kurir (or load by id) and verify kurir.region_id === auth()->user()->region_id. Also consider filtering to admin's region.
3. **PeformaCustomerController@show**: Load Customer by id and verify region_id matches admin's region.
4. **HistoryOrderController**: Fix invoice() and downloadInvoice() - either use model binding with authorization check, or manually scope by region_id (and for kurir by ownership). Also check role.
5. **ReturnController (all methods)**: For kurir routes, verify order.created_by_user_id === Auth::id() (and optionally same region). Add authorization check.
6. **Dashboard controllers**: Add check that URL region matches logged-in user's region_id. For admin: compare $admin->region_id to $regionModel->id; for kurir: compare $kurir->region_id.
7. **Kurir PesananController@getLastOrder**: Scope to kurir's region/ownership - e.g., ensure customer belongs to same region and/or kurir has relationship; at minimum check region consistency.
8. **Consider route model binding + Form Requests/Policies** for consistent authorization across all parameterized routes.

