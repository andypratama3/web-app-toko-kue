# CROSS-FEATURE AUDIT — web-app-toko-kue (verified via direct code inspection + .env + composer.lock + PHP reflection)

Run date: 2026-10-05. Tests: 93 passed / 7 skipped (baseline OK — bugs are NOT caught by suite).

## VERIFIED CRITICAL (proof given in tool output — do not skip)

### C1 — Webhook signature disabled: anyone can POST to /whatsapp/webhook
Evidence: .env line 64 `META_WEBHOOK_SECRET=` (empty → falsy). WhatsAppWebhookController:38 skips verification when empty. Attacker can forge messages / orders / images.

### C2 — Every WhatsApp image kills the webhook job permanently (FAILS on every image)
Evidence: IncomingMediaHandler:43 uses `\Intervention\Image\Laravel\Facades\Image::read()` — class does NOT exist (verified: `class_exists` = NO, no `src/Laravel` dir in vendor/intervention/image 3.11.6). Also `encodeJpeg()` does NOT exist (verified via ReflectionMethod: NO, v3 uses `encode(new JpegEncoder(...))`). `catch (\Exception)` at line 53 does NOT catch `Error` — it propagates to ProcessWhatsAppWebhookJob and retries 3×, then fails permanently. Customer sending a proof image → never gets reply; order stuck at AWAITING_PAYMENT_PROOF.

### H1 (fix.txt) — Escalation leaves state = ESCALATED_TO_HUMAN; first PESAN consumed
Evidence: OrderBotService:79-88 sets state to ESCALATED_TO_HUMAN, sends correct error, returns (no reset, context keeps old `form_step`/`order_form`). handleMessage line 63 groups ESCALATED_TO_HUMAN with ORDER_CONFIRMED → `handleClosedConversation` sends greeting + advances to WELCOME_SENT. User's "PESAN" at first message gets a generic greeting, not a form restart (second PESAN works). Stale draft leaks to next order.

### M2 / H1 — Escalation message crashes if $conversation->region is null
Evidence: OrderBotService:84 `{$conversation->region->name}` — bare property access, not `?->`. `region_id` nullable; if null → `Error`. Same at :433 for address-based escalation. `handleLocationMessage:87` sets state BEFORE sending; if send throws, state is already escalated with no message delivered.

### M1 — Admin escalation alert never fires for the message that caused escalation
Evidence: ProcessWhatsAppWebhookJob:160 dispatches WhatsAppMessageReceived BEFORE the bot (line 82). Listener (NotifyAdminOfEscalatedIncomingMessage:21) checks current DB state — still AWAITING_... at dispatch time → returns early. Escalation message never notifies admin.

### CROSS-BRANCH (from agent audit file CROSS_BRANCH_IDOR_AUDIT.md — review individually; highest risk: admin/chat close/open, admin/orders/{id} uses manual `{id}` with region checks only partial)

## VERIFIED HIGH

- H2: handleLocationMessage silent for any state other than AWAITING_LOCATION_OR_ADDRESS or AWAITING_DELIVERY_METHOD (line 67-99 — no else).
- H3 (line 472): "ya" / "oke" / "ok" in ORDER_SUMMARY matches `\bya\b` (len<=3) → ordinary questions confirm orders.
- H4 (line 150-162): `pesan` / `order` is a word-match (str_contains) inside orderFlowStates → answers like "pak order" / "bu order" restart form; form never completes.
- M3: sendText inside DB::transaction (line 829/841 inside closure at 816) holds locks + DB connection during Meta HTTP.
- H5 (line 1138-1153): invoice number = count() + 1, no lock; unique index → lost order on concurrent confirm (race between read count and insert).
- M4 (line 847-881): no active variant → $variant = null → sub-total = 0; order created at Rp 0.
- M5 (line 778): `variants->where('is_active',true)->first()` unordered, can disagree between quote and charge.
- M9: fetchTemplates discards paging.next (line 264-305); sync prints success even on API failure (WhatsAppBroadcastService:32-36).
- M10: downloadMedia writes to `public` disk (line 219-244), forwards Meta token to any URL (SSRF), no size cap; payment proofs public.
- M12: ChatMonitorController close/update/reply don't check conversation status; bot never checks status (no `status!==active` gate in ProcessWhatsAppWebhookJob).

## ROUTE DEFECTS (verified from routes/web.php)

- Duplicate route name: `Route::post('/{order}/request-return/edit', ...)->name('requestReturn')` clashes with real `request-return` (line ~146 / ~153 in route file).
- `Route::get('admin/orders/{id}/verify', ...)` uses `{id}` not `{order}`; some controllers scope by `region_id`, some may not fully (needs per-controller verification via CROSS_BRANCH_IDOR_AUDIT.md).
- `Route::get('kurir/pesanan/{id}/...')` uses `{id}`.
- `Route::post('broadcast/{broadcast}/cancel', ...)` has no authorization beyond `role:admin` (fine per design), but `cancel` should check the broadcast owner/branch.

## WHAT IS SAFE / CORRECT

- Webhook signature verification CODE is correct (`hash_hmac` + `hash_equals`) — just disabled by missing secret.
- Dedup index `whatsapp_message_id` unique (good), but fails open on duplicate (bad retry behavior).
- `handleWelcome` uses `region?->name ?? config(...)` correctly (line 571) — escalation uses bare access (line 84/433), inconsistent.
- All 93 existing tests pass — they just don't cover image processing, escalation-state transitions, concurrent orders, or cross-branch access.

## RECOMMENDED ORDER (highest impact, lowest risk first)
1. Fix IncomingMediaHandler (C2) — unblocks all image proof flows.
2. Set META_WEBHOOK_SECRET and make missing secret fatal (C1).
3. Add region null-safe + context-clear + notification fix to escalation branches (H1/M1/M2).
4. Fix route duplicate name + verify region scoping on remaining admin/controllers.
5. Then fix state-machine edge cases (H2/H3/H4/M3/M4/M5/M6/M10/M12) incrementally.
