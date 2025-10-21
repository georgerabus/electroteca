<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'PCB',
                'slug' => 'pcb',
                'description' => 'Printed Circuit Boards and related components',
            ],
            [
                'name' => 'Cabluri',
                'slug' => 'cabluri',
                'description' => 'Electronic cables and wiring',
            ],
            [
                'name' => 'Lipire',
                'slug' => 'lipire',
                'description' => 'Soldering materials and tools',
            ],
            [
                'name' => 'Module microcontrolere',
                'slug' => 'module-microcontrolere',
                'description' => 'Microcontroller development boards',
            ],
            [
                'name' => 'Unelte',
                'slug' => 'unelte',
                'description' => 'Tools and equipment',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
