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
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/Sefacdb89f8f94ef7812c38f77921f440r.jpg',
                'category_id' => $pcbCategory->id,
            ],
            [
                'name' => 'Cablu electronic',
                'slug' => 'electronic-cable',
                'description' => 'High-quality electronic cable for various applications',
                'price' => 15.50,
                'currency' => 'MDL',
                'stock_quantity' => 10,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/Cablu_electronic_2.jpg',
                'category_id' => $cabluriCategory->id,
            ],
            [
                'name' => 'Cabluri de legatura',
                'slug' => 'jumper-wires',
                'description' => 'Pack of 40 jumper wires for breadboard connections',
                'price' => 12.00,
                'currency' => 'MDL',
                'stock_quantity' => 15,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/cabluri_de_legatura-300x300.png',
                'category_id' => $cabluriCategory->id,
            ],
            [
                'name' => 'Pasta de fludor',
                'slug' => 'solder-paste',
                'description' => 'Professional grade solder paste for electronics',
                'price' => 35.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/pasta-de-fludor.jpg',
                'category_id' => $lipireCategory->id,
            ],
            [
                'name' => 'Placă de circuit neîncărcată',
                'slug' => 'placa-circuit',
                'description' => 'Placă de circuit neîncărcată',
                'price' => 56.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/cablaje-de-test.jpg',
                'category_id' => $pcbCategory->id,
            ],
            [
                'name' => 'Placă de Expansiune ESP32',
                'slug' => 'placa-expansiuni',
                'description' => 'Placă de Expansiune ESP32',
                'price' => 29.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/plata-esp-32.jpg',
                'category_id' => $moduleCategory->id,
            ],
            [
                'name' => 'Placă de Testare PCB',
                'slug' => 'placa-testare',
                'description' => 'Placă de Testare PCB',
                'price' => 44.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/placa.png',
                'category_id' => $pcbCategory->id,
            ],
            [
                'name' => 'Plăci Protoboard PCB Dublu Față',
                'slug' => 'placa-protoboard',
                'description' => 'Plăci Protoboard PCB Dublu Față',
                'price' => 56.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/pcb.jpeg',
                'category_id' => $pcbCategory->id,
            ],
            [
                'name' => 'Set de șurubelnițe de precizie',
                'slug' => 'set-de-surubelnite',
                'description' => 'Set de șurubelnițe de precizie',
                'price' => 50.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/surubelnite.jpg',
                'category_id' => $unelteCategory->id,
            ],
            [
                'name' => 'Suport de clema pentru stația de lipit din nailon',
                'slug' => 'set-de-clema',
                'description' => 'Suport de clema pentru stația de lipit din nailon',
                'price' => 39.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/Suport-de-clema-e1740067722920.jpg',
                'category_id' => $unelteCategory->id,
            ],
            [
                'name' => 'Tuburi Termocontractabile',
                'slug' => 'tuburi-termocontractabile',
                'description' => 'Tuburi Termocontractabile',
                'price' => 56.00,
                'currency' => 'MDL',
                'stock_quantity' => 3,
                'is_available' => true,
                'image_url' => 'https://electroteca.md/wp-content/uploads/2025/02/set_tuburi.jpg',
                'category_id' => $cabluriCategory->id,
            ],

        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
