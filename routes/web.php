<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', function () {
    return view('welcome');
});

// Microsoft Graph Email Routes
Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
Route::get('/auth/microsoft', [EmailController::class, 'authenticate'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [EmailController::class, 'callback'])->name('auth.microsoft.callback');
Route::post('/auth/logout', [EmailController::class, 'logout'])->name('auth.logout');

// Email API Routes
Route::get('/api/emails/refresh', [EmailController::class, 'refresh'])->name('emails.refresh');
Route::post('/api/emails/cache/clear', [EmailController::class, 'clearCache'])->name('emails.cache.clear');
Route::get('/api/emails/cache/stats', [EmailController::class, 'cacheStats'])->name('emails.cache.stats');

// AI Test Route (for development)
Route::get('/api/ai/test', function() {
    try {
        $aiService = app(\App\Services\AIService::class);
        $testResult = $aiService->testConnection();
        
        return response()->json([
            'ai_available' => $aiService->isAvailable(),
            'connection_test' => $testResult,
            'config_check' => [
                'api_key_set' => !empty(config('openai.api_key')),
                'ai_enabled' => config('ai.enabled', true)
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'ai_available' => false
        ], 500);
    }
})->name('ai.test');
