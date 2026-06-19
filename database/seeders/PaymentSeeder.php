<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('payments')->insert([
            [
                'payment_number' => 'PAY-0001',
                'invoice_id' => 1,
                'customer_id' => 1,
                'store_id' => 1,
                'user_id' => 1,

                'payment_date' => Carbon::today(),
                'amount' => 20000,

                'payment_method' => 'cash',
                'reference_number' => null,
                'bank_name' => null,
                'account_number' => null,

                'status' => 'verified',
                'notes' => 'Initial payment',

                'received_by' => 1,
            ],
        ]);
    }
}
