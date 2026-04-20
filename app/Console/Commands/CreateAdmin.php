<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Create admin user';

    public function handle(): void
    {
        User::create([
            'name'              => 'Admin',
            'phone'             => '01000000000',
            'password'          => bcrypt('12345678N'),
            'role'              => 'admin',
            'phone_verified_at' => now(),
        ]);

        $this->info('Admin created successfully.');
    }
}