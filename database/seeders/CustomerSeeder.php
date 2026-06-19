<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert([
            [
                'customer_no' => 'CUST-001',
                'name' => 'Ali Traders',
                'phone' => '03001112233',
                'email' => 'ali@gmail.com',
                'city' => 'Gujranwala',
                'address' => 'Model Town',
                'credit_limit' => 100000,
                'status' => 'active',
            ],
            [
                'customer_no' => 'CUST-002',
                'name' => 'Ahmed Store',
                'phone' => '03112223344',
                'email' => 'ahmed@gmail.com',
                'city' => 'Lahore',
                'address' => 'DHA Phase 2',
                'credit_limit' => 200000,
                'status' => 'active',
            ],
        ]);

    }
}
