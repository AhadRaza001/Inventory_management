<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('items')->insert([
            [
                'category_id' => 1,
                'unit_id' => 5,
                'sku' => 'ITM-001',
                'name' => 'Rice',
                'description' => 'Premium basmati rice',
                'purchase_price' => 120,
                'sale_price' => 150,
                'status' => 'active',
                'barcode' => '12345678901',
                'reorder_level' => 20,
            ],
            [
                'category_id' => 1,
                'unit_id' => 3,
                'sku' => 'ITM-002',
                'name' => 'Milk',
                'description' => 'Fresh milk 1 liter',
                'purchase_price' => 180,
                'sale_price' => 200,
                'status' => 'active',
                'barcode' => '98765432101',
                'reorder_level' => 50,
            ],
        ]);
    }
}
