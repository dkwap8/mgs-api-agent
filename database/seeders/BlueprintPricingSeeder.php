<?php
namespace Database\Seeders;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;
class BlueprintPricingSeeder extends Seeder
{
    public function run(): void {
        foreach ([['100.00','200.00','5.00'],['201.00','1000.00','10.00'],['1001.00','5000.00','25.00'],['5001.00','9999.00','50.00'],['10000.00',null,'100.00']] as [$min,$max,$fee]) {
            // Preserve edits/activation on repeat execution. Do not overwrite existing commercial policy.
            PricingRule::query()->firstOrCreate(['name'=>'Blueprint V2 collection '.$min,'type'=>'collection'],[
                'min_amount'=>$min,'max_amount'=>$max,'fee_amount'=>$fee,'fee_percentage'=>'0.00','is_active'=>false]);
        }
    }
}
