<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'general' => [
                'app_name' => config('app.name', 'MGS API'),
                'support_email' => '',
                'support_phone' => '',
                'maintenance_mode' => false,
                'merchant_auto_approval' => false,
            ],
            'virtual_accounts' => [
                'virtual_accounts_enabled' => true,
                'enforce_virtual_account_policy' => false,
                'virtual_account_requirement' => 'none',
                'default_auto_generate_virtual_account' => true,
                'default_allow_multiple_virtual_accounts' => false,
                'maximum_virtual_accounts_per_customer' => '',
            ],
        ];

        foreach ($settings as $group => $values) {
            foreach ($values as $key => $value) {
                SystemSetting::setValue($key, $value, $group);
            }
        }
    }
}
