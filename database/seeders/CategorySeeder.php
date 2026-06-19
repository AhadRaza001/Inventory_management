<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Devices, gadgets, and electronic items.',
            ],
            [
                'name' => 'Furniture',
                'description' => 'Home and office furniture items.',
            ],
            [
                'name' => 'Clothing',
                'description' => 'Men, women, and kids apparel.',
            ],
            [
                'name' => 'Groceries',
                'description' => 'Daily food and household consumables.',
            ],
            [
                'name' => 'Stationery',
                'description' => 'Office and school supplies.',
            ],
        ];

        DB::table('categories')->insert($categories);
    
    }
}
