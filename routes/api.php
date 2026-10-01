<?php

use App\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/license')->middleware('throttle:license-api')->group(function () {
    Route::post('activate', [LicenseController::class, 'activate']);
    Route::post('check', [LicenseController::class, 'check']);
});
