<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes for company registration and login
// Route::post('/register-company', [AuthController::class, 'registerCompany']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin-only route for user registration
    Route::post('/register', [AuthController::class, 'register'])->middleware('role.admin');

    // User management routes - Admin only
    Route::prefix('users')->group(function () {
        // GET /api/users - List users (Admins only)
        Route::get('/', [UserController::class, 'index'])->middleware(['tenant.isolation', 'role.admin']);

        // POST /api/users - Add user (Admins only)
        Route::post('/', [UserController::class, 'store'])->middleware(['tenant.isolation', 'role.admin']);

        // GET /api/users/{id} - View user (Admins or self)
        // Route::get('/{user}', [UserController::class, 'show'])->middleware('tenant.isolation');

        // PUT /api/users/{id} - Update user role (Admins only)
        Route::put('/{user}', [UserController::class, 'update'])->middleware('tenant.isolation');

        // DELETE /api/users/{id} - Delete user (Admins only)
        // Route::delete('/{user}', [UserController::class, 'destroy'])->middleware(['tenant.isolation', 'role.admin']);
    });

    // Expense management routes
    Route::prefix('expenses')->group(function () {
        // GET /api/expenses - List expenses (All users, filtered by role)
        Route::get('/', [ExpenseController::class, 'index'])->middleware('tenant.isolation');

        // POST /api/expenses - Create expense (All users)
        Route::post('/', [ExpenseController::class, 'store'])->middleware('tenant.isolation');

        // GET /api/expenses/{id} - View expense (All users, filtered by role)
        // Route::get('/{expense}', [ExpenseController::class, 'show'])->middleware('tenant.isolation');

        // PUT /api/expenses/{id} - Update expense (Managers & Admins only)
        Route::put('/{expense}', [ExpenseController::class, 'update'])->middleware(['tenant.isolation', 'role.manager']);

        // DELETE /api/expenses/{id} - Delete expense (Admins only)
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->middleware(['tenant.isolation', 'role.admin']);
    });
});