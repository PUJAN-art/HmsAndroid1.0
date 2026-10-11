<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            if ($this->command) {
                $this->command->warn('Demo data seeding is restricted to local and testing environments.');
            }

            return;
        }

        $this->call(DemoDataSeeder::class);
    }
}
