<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('invoices')->insert([
            [
                'invoice_number' => 'INV-0001',
                'sale_order_id' => 1,
                'customer_id' => 1,
                'store_id' => 1,
                'user_id' => 1,
                'invoice_date' => Carbon::today(),
                'due_date' => Carbon::today()->addDays(7),

                'subtotal' => 50000,
                'discount_amount' => 2000,
                'tax_amount' => 5000,
                'total_amount' => 53000,

                'paid_amount' => 20000,
                'balance_due' => 33000,

                'status' => 'partial',
                'notes' => 'First invoice generated from sale order',
            ],
        ]);
    }
}
