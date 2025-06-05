<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ProjectSetupCommand extends Command
{
    protected $signature = 'project:setup {--force : Force run even if users exist}';
    protected $description = 'Initial project setup: reset DB, seed, and create superadmin';

    public function handle(): int
    {
        if (User::count() > 0 && ! $this->option('force')) {
            $this->error('❌ Users already exist. This setup command can only be run on a fresh installation unless --force is used.');
            return Command::FAILURE;
        }

        $this->warn('This will run `migrate:fresh --seed` and erase ALL data.');
        if (! $this->option('force') && ! $this->confirm('Are you sure you want to continue?')) {
            return Command::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true]);

        $this->info('Database seeded. Now let\'s create a SuperAdmin user.');

        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');
        $confirm = $this->secret('Confirm Password');

        while ($password !== $confirm) {
            $this->error('Passwords do not match. Try again.');
            $password = $this->secret('Password');
            $confirm = $this->secret('Confirm Password');
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('superadmin');

        $this->info("✅ SuperAdmin {$user->email} created successfully!");
        return Command::SUCCESS;
    }
}
