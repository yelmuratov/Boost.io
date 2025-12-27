<?php

namespace Database\Seeders;

use App\Models\SmmOrder;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SmmOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have at least 1 user
        if (User::count() == 0) {
            User::factory(5)->create();
        }

        // Ensure we have at least 1 provider
        if (SmmProvider::count() == 0) {
            SmmProvider::factory(3)->create();
        }

        // Ensure we have at least 1 service
        if (SmmService::count() == 0) {
            // Create services for existing providers
            SmmProvider::all()->each(function ($provider) {
                SmmService::factory(5)->create(['provider_id' => $provider->id]);
            });
        }

        // Create Orders
        SmmOrder::factory(50)->create();
    }
}
