<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view' => 'View dashboard',
            'merchants.view' => 'View merchants',
            'merchants.manage' => 'Manage merchants',
            'businesses.view' => 'View businesses',
            'businesses.manage' => 'Manage businesses',
            'wallets.view' => 'View wallets',
            'wallets.adjust' => 'Adjust wallets',
            'transactions.view' => 'View transactions',
            'payouts.view' => 'View payouts',
            'payouts.review' => 'Review payouts',
            'providers.view' => 'View providers',
            'providers.manage' => 'Manage providers',
            'reports.view' => 'View financial reports',
            'pricing.view' => 'View pricing',
            'pricing.manage' => 'Manage pricing',
            'settings.view' => 'View settings',
            'settings.manage' => 'Manage settings',
            'audit.view' => 'View audit logs',
            'support.view' => 'View support tickets',
            'support.manage' => 'Manage support tickets',
            'users.manage' => 'Manage administrator access',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName]
            );
        }

        $roles = [
            'super_admin' => ['Super Administrator', 'Full platform access'],
            'admin' => ['Administrator', 'Operational administration access'],
            'administrator' => ['Administrator (Legacy)', 'Legacy administrator compatibility role'],
            'support_admin' => ['Support Administrator', 'Merchant support and ticket access'],
            'finance_admin' => ['Finance Administrator', 'Wallet, transaction, and payout access'],
            'compliance_admin' => ['Compliance Administrator', 'Merchant and business verification access'],
        ];

        foreach ($roles as $name => [$displayName, $description]) {
            Role::query()->updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'description' => $description]
            );
        }

        $all = Permission::query()->pluck('id');
        Role::query()->whereIn('name', ['super_admin', 'admin', 'administrator'])
            ->get()->each(fn (Role $role) => $role->permissions()->sync($all));

        $this->syncRole('support_admin', ['dashboard.view', 'merchants.view', 'businesses.view', 'transactions.view', 'support.view', 'support.manage']);
        $this->syncRole('finance_admin', ['dashboard.view', 'wallets.view', 'wallets.adjust', 'transactions.view', 'payouts.view', 'payouts.review', 'pricing.view', 'reports.view']);
        $this->syncRole('compliance_admin', ['dashboard.view', 'merchants.view', 'merchants.manage', 'businesses.view', 'businesses.manage', 'audit.view']);
    }

    private function syncRole(string $role, array $permissions): void
    {
        $model = Role::query()->where('name', $role)->firstOrFail();
        $ids = Permission::query()->whereIn('name', $permissions)->pluck('id');
        $model->permissions()->sync($ids);
    }
}
