<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('suppliers')->insert([
            [
                'sku' => 'SUP-001',
                'name' => 'ABC Traders',
                'phone' => '03001112222',
                'city' => 'Gujranwala',
                'address' => 'Model Town',
                'email' => 'abc@gmail.com',
                'credit_limit' => 500000,
            ],
            [
                'sku' => 'SUP-002',
                'name' => 'XYZ Suppliers',
                'phone' => '03115556666',
                'city' => 'Lahore',
                'address' => 'DHA Phase 3',
                'email' => 'xyz@gmail.com',
                'credit_limit' => 300000,
            ],
        ]);
    }
}
