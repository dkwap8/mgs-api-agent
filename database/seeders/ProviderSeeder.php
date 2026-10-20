<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            Provider::query()->updateOrCreate(
                ['slug' => 'local'],
                [
                    'name' => 'Local Development Provider',
                    'type' => Provider::TYPE_VIRTUAL_ACCOUNT,
                    'is_active' => true,
                    'priority' => 1,
                    'health_status' => Provider::HEALTH_HEALTHY,
                    'config' => [],
                    'metadata' => ['driver' => 'local'],
                ]
            );
        }

        foreach ($this->productionTemplates() as $template) {
            Provider::query()->firstOrCreate(['slug' => $template['slug']], $template);
        }
    }

    private function productionTemplates(): array
    {
        return [
            $this->template('PalmPay', 'palmpay', 'palmpay', 10),
            $this->template('OPay', 'opay', 'opay', 20),
            $this->template('9 Payment Service Bank', '9psb', '9psb', 30),
            $this->template('Monnify', 'monnify', 'monnify', 40),
        ];
    }

    private function template(string $name, string $slug, string $driver, int $priority): array
    {
        return [
            'name' => $name,
            'type' => Provider::TYPE_VIRTUAL_ACCOUNT,
            'is_active' => false,
            'priority' => $priority,
            'health_status' => Provider::HEALTH_UNHEALTHY,
            'config' => ['sandbox' => true],
            'metadata' => [
                'driver' => $driver,
                'capabilities' => [
                    'virtual_accounts' => true,
                    'virtual_account' => true,
                    'transaction_verification' => true,
                    'payout' => true,
                    'bank_verification' => true,
                ],
                'circuit' => [
                    'failure_threshold' => 3,
                    'cooldown_minutes' => 5,
                    'consecutive_failures' => 0,
                    'open_until' => null,
                ],
                'timeout' => 20,
            ],
        ];
    }
}
