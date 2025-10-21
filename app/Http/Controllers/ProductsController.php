<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $productCount = Product::count();
        $categoryCount = Category::count();

        // Always return proper filters object structure
        $filters = [
            'category' => $request->get('category'),
            'availability' => $request->get('availability'),
            'search' => $request->get('search'),
            'sort' => $request->get('sort'),
        ];

        if ($productCount === 0 || $categoryCount === 0) {
            return Inertia::render('products', [
                'products' => [],
                'categories' => ['All categories'],
                'filters' => $filters, // Return proper object, not empty array
            ]);
        }

        $query = Product::with('category');

        // Rest of your filtering logic remains the same...
        if ($request->filled('category') && $request->category !== 'All categories') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        if ($request->filled('availability')) {
            if ($request->availability === 'Available') {
                $query->where('is_available', true)->where('stock_quantity', '>', 0);
            } elseif ($request->availability === 'Out of stock') {
                $query->where(function ($q) {
                    $q->where('is_available', false)->orWhere('stock_quantity', 0);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('sort')) {
            if ($request->sort === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($request->sort === 'price_desc') {
                $query->orderBy('price', 'desc');
            } elseif ($request->sort === 'name') {
                $query->orderBy('name', 'asc');
            }
        } else {
            $query->orderBy('name', 'asc');
        }

        $products = $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => number_format($product->price, 2),
                'currency' => $product->currency ?? 'MDL',
                'stock_quantity' => $product->stock_quantity,
                'is_available' => $product->is_available && $product->stock_quantity > 0,
                'category' => $product->category->name,
                'image_url' => $product->image_url,
            ];
        });

        $categories = Category::pluck('name')->prepend('All categories');

        return Inertia::render('products', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }
}
