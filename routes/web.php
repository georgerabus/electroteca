<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WalletController;

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

Route::get('/', function () {
    return Inertia::render('main');
})->name('main');

Route::get('/shop', [ProductsController::class, 'index'])->name('products');
Route::get('/shop/{slug}', [ProductsController::class, 'show'])->name('products.show');
Route::get('/cart', function () {
    return Inertia::render('cart');
})->name('cart');

Route::get('/blog', function () {
    return Inertia::render('blog');
})->name('blog');

Route::get('/internship-programs', function () {
    return Inertia::render('internship-programs');
})->name('internship-programs');

Route::get('/contact', function () {
    return Inertia::render('contact');
})->name('contact');

Route::middleware(['auth', 'require.2fa'])->group(function () {
    // Loan routes (accessible without email verification)
    Route::get('loans/my-loans', [LoanController::class, 'myLoans'])->name('loans.my-loans');
    Route::get('products/{product}/check-borrow', [LoanController::class, 'checkBorrowability'])->name('products.check-borrow');
    Route::post('products/{product}/borrow', [LoanController::class, 'borrow'])->name('products.borrow');
    Route::post('loans/{loanRequest}/request-return', [LoanController::class, 'requestReturn'])->name('loans.request-return');
    Route::post('loans/{loanRequest}/return', [LoanController::class, 'returnProduct'])->name('loans.return');

    // Checkout routes
    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    // Wallet routes
    Route::get('wallet', [WalletController::class, 'index'])->name('wallet');
});

Route::middleware(['auth', 'verified', 'require.2fa'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
            Route::get('/', [AdminController::class, 'products'])->name('products');
            Route::get('users', [AdminController::class, 'users'])->name('users');
            Route::get('users/{user}/dashboard', [AdminController::class, 'userDashboard'])->name('user.dashboard');
            Route::get('loans', [AdminController::class, 'loans'])->name('loans');
            
            // Product management
            Route::post('products', [AdminController::class, 'storeProduct'])->name('products.store');
            Route::put('products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
            Route::patch('products/{product}/stock', [AdminController::class, 'updateStock'])->name('products.update-stock');
            
            // Loan actions
            Route::post('loans/{loanRequest}/approve', [AdminController::class, 'approveLoan'])->name('loans.approve');
            Route::post('loans/{loanRequest}/reject', [AdminController::class, 'rejectLoan'])->name('loans.reject');
            Route::post('loans/{loanRequest}/picked-up', [AdminController::class, 'markAsPickedUp'])->name('loans.picked-up');
            Route::post('loans/{loanRequest}/approve-return', [AdminController::class, 'approveReturn'])->name('loans.approve-return');
        });
});




require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
