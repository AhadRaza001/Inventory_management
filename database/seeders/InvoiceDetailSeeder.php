<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('invoice_details')->insert([
            [
                'invoice_id' => 1,
                'item_id' => 1,
                'unit_id' => 5,

                'item_name' => 'Rice',
                'item_code' => 'ITM-001',

                'quantity' => 10,
                'unit_price' => 150,

                'discount_percent' => 5,
                'discount_amount' => 75,

                'tax_percent' => 10,
                'tax_amount' => 142.5,

                'subtotal' => 1500,
                'total' => 1567.5,
            ],
        ]);
    }
}
