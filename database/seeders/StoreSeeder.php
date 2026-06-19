<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('stores')->insert([
            [
                'name' => 'Main Warehouse',
                'description' => 'Primary storage warehouse',
                'address' => 'Gujranwala Industrial Area',
                'phone' => '03001234567',
            ],
            [
                'name' => 'City Outlet',
                'description' => 'Retail store in city',
                'address' => 'GT Road Gujranwala',
                'phone' => '03111222333',
            ],
        ]);
    
    }
}
