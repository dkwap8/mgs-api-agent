<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-daily-summary')]
#[Description('Command description')]
class SendDailySummary extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
