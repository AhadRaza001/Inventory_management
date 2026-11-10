<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('companies')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Super Asia',
                'email' => 'info@superasia.pk',
                'phone' => '03001234567',
                'address' => 'Gujranwala, Pakistan',
                'tax_number' => null,
                'logo' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
