<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' && $password === '') {
            $this->command?->warn('ADMIN_EMAIL and ADMIN_PASSWORD are not set; administrator provisioning was skipped.');
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL must be a valid email address.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD must contain at least 12 characters.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => strtolower($email)],
            [
                'uuid' => User::query()->where('email', strtolower($email))->value('uuid') ?: (string) Str::uuid(),
                'name' => trim((string) env('ADMIN_NAME', 'Platform Administrator')),
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $role = Role::query()->where('name', 'super_admin')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
