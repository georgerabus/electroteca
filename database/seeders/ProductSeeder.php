<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $pcbCategory = Category::where('slug', 'pcb')->first();
        $cabluriCategory = Category::where('slug', 'cabluri')->first();
        $lipireCategory = Category::where('slug', 'lipire')->first();
        $moduleCategory = Category::where('slug', 'module-microcontrolere')->first();
        $unelteCategory = Category::where('slug', 'unelte')->first();

        $products = [
            [
                'name' => '4-in-1 Breadboard',
                'slug' => '4-in-1-breadboard',
                'description' => 'Versatile breadboard for prototyping electronic circuits',
                'price' => 25.99,
                'currency' => 'MDL',
                'stock_quantity' => 5,
                'is_available' => true,
                'category_id' => $pcbCategory->id,
            ],
            [
                'name' => 'Electronic Cable',
                'slug' => 'electronic-cable',
                'description' => 'High-quality electronic cable for various applications',
                'price' => 15.50,
                'currency' => 'MDL',
                'stock_quantity' => 10,
                'is_available' => true,
                'category_id' => $cabluriCategory->id,
            ],
            [
                'name' => 'Jumper Wires',
                'slug' => 'jumper-wires',
                'description' => 'Pack of 40 jumper wires for breadboard connections',
                'price' => 12.00,
                'currency' => 'MDL',
                'stock_quantity' => 15,
                'is_available' => true,
                'category_id' => $cabluriCategory->id,
            ],
            [
                'name' => 'Solder Paste',
                'slug' => 'solder-paste',
                'description' => 'Professional grade solder paste for electronics',
                'price' => 35.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'category_id' => $lipireCategory->id,
            ],
            [
                'name' => 'ESP32 Development Board',
                'slug' => 'esp32-development-board',
                'description' => 'ESP32 microcontroller development board with WiFi and Bluetooth',
                'price' => 45.99,
                'currency' => 'MDL',
                'stock_quantity' => 8,
                'is_available' => true,
                'category_id' => $moduleCategory->id,
            ],
            [
                'name' => 'Precision Screwdriver Set',
                'slug' => 'precision-screwdriver-set',
                'description' => 'Set of precision screwdrivers for electronics work',
                'price' => 28.75,
                'currency' => 'MDL',
                'stock_quantity' => 0,
                'is_available' => false,
                'category_id' => $unelteCategory->id,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
