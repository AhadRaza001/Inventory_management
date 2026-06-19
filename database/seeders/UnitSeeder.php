<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $units = [
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
            ],
            [
                'name' => 'Gram',
                'symbol' => 'g',
            ],
            [
                'name' => 'Litre',
                'symbol' => 'L',
            ],
            [
                'name' => 'Millilitre',
                'symbol' => 'ml',
            ],
            [
                'name' => 'Piece',
                'symbol' => 'pcs',
            ],
            [
                'name' => 'Dozen',
                'symbol' => 'dz',
            ],
            [
                'name' => 'Box',
                'symbol' => 'box',
            ],
        ];

        DB::table('units')->insert($units);
    
    }
}
