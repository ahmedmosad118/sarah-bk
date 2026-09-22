<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\JobTitleController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Central\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central / Platform Management API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('central')->group(function () {
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/check-slug', [TenantController::class, 'checkAvailability']);
});

/*
|--------------------------------------------------------------------------
| Tenant Public Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});

/*
|--------------------------------------------------------------------------
| Tenant Protected API Routes (Requires Authenticated Tenant User)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth Profile
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar'])->middleware('throttle:uploads');
    });

    // Users Management
    Route::prefix('users')->group(function () {
        Route::get('/schema', [UserController::class, 'schema']);
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{ids}', [UserController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    });

    // Customers Management (Commercial Foundation)
    Route::prefix('customers')->group(function () {
        Route::get('/schema', [CustomerController::class, 'schema']);
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{ids}', [CustomerController::class, 'destroy']);
    });

    // Roles & Permissions Management
    Route::prefix('roles')->group(function () {
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    // Job Titles Management
    Route::prefix('job-titles')->group(function () {
        Route::get('/schema', [JobTitleController::class, 'schema']);
        Route::get('/all', [JobTitleController::class, 'all']);
        Route::get('/', [JobTitleController::class, 'index']);
        Route::post('/', [JobTitleController::class, 'store']);
        Route::get('/{id}', [JobTitleController::class, 'show']);
        Route::put('/{id}', [JobTitleController::class, 'update']);
        Route::delete('/{ids}', [JobTitleController::class, 'destroy']);
    });

    // Activity Log
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::post('/', [SettingController::class, 'update']);
    });
});
