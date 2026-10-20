<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Default collection fee', 'type' => 'collection', 'fee_amount' => 0, 'fee_percentage' => 1.50],
            ['name' => 'Default payout fee', 'type' => 'payout', 'fee_amount' => 50, 'fee_percentage' => 0],
        ] as $rule) {
            PricingRule::query()->updateOrCreate(
                ['name' => $rule['name'], 'type' => $rule['type']],
                $rule + ['min_amount' => null, 'max_amount' => null, 'is_active' => false]
            );
        }
    }
}
