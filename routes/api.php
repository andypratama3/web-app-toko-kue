<?php

use App\Http\Controllers\Chatbot\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// WhatsApp Webhook - Existing endpoint (keep for backward compatibility with Meta dashboard)
Route::get('/webhook/meta', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/meta', [WhatsAppWebhookController::class, 'handle']);

// WhatsApp Webhook - Legacy callback URL registered in Meta dashboard (kuepandanasli.com/api/webhook/whatsapp/meta)
Route::get('/webhook/whatsapp/meta', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp/meta', [WhatsAppWebhookController::class, 'handle']);

// WhatsApp Webhook - Versioned endpoint
Route::get('/v1/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/v1/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);