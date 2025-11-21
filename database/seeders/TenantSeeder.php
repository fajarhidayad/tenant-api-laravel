<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::factory()->foodTruck()->count(10)->create();
        Tenant::factory()->booth()->count(10)->create();
        Tenant::factory()->spaceOnly()->count(10)->create();
    }
}
