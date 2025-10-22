<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;

// Route::get('/', function () {
//     return Inertia::render('welcome');
// })->name('home');

// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     Route::get('products', [ProductsController::class, 'index'])->name('products');

//     Route::prefix('admin')->name('admin.')->group(function () {
//             Route::get('products', [AdminController::class, 'products'])->name('products');
//             Route::get('users', [AdminController::class, 'users'])->name('users');
//             Route::get('users/{user}/dashboard', [AdminController::class, 'userDashboard'])->name('user.dashboard');
//         });
// });

Route::get('/', [ProductsController::class, 'index'])->name('products');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminController::class, 'products'])->name('products');
            Route::get('users', [AdminController::class, 'users'])->name('users');
            Route::get('users/{user}/dashboard', [AdminController::class, 'userDashboard'])->name('user.dashboard');
        });
});




require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
