<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;

Route::middleware('throttle:api')->group(function () {
    Route::apiResource('notes', NoteController::class);
});

// Stricter rate limit for AI operations
Route::middleware('throttle:ai-endpoints')->group(function () {
    Route::post('notes/{note}/summary', [NoteController::class, 'summary']);
});
