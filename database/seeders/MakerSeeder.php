<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Maker;

class MakerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $makers = [
            "HK",
            "CN",
            "US",
            "CA",
            "救恩之声",
            "Soooradio"
        ];
        foreach ($makers as $maker) {
            $tag = Maker::firstOrCreate(['name'=>$maker]);
        }
    }
}
