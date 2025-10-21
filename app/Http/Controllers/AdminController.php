<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function products()
    {
        $products = Product::with('category')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => number_format($product->price, 2),
                'currency' => $product->currency ?? 'MDL',
                'stock_quantity' => $product->stock_quantity,
                'is_available' => $product->is_available,
                'category' => $product->category->name,
                'image_url' => $product->image_url,
            ];
        });

        return Inertia::render('admin/products', [
            'products' => $products,
        ]);
    }

    public function users()
    {
        $users = User::all();

        return Inertia::render('admin/users', [
            'users' => $users,
        ]);
    }

    public function userDashboard(User $user)
    {
        return Inertia::render('admin/user-dashboard', [
            'user' => $user,
        ]);
    }
}
