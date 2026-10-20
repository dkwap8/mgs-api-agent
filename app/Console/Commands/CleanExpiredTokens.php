<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredTokens extends Command
{
    protected $signature = 'auth:prune-expired';
    protected $description = 'Delete expired access tokens, password reset tokens and registration OTPs';

    public function handle(): int
    {
        $access = DB::table('personal_access_tokens')->whereNotNull('expires_at')->where('expires_at', '<', now())->delete();
        $passwords = DB::table('password_reset_tokens')->where('created_at', '<', now()->subMinutes(30))->delete();
        $otps = DB::table('registration_otps')->where('expires_at', '<', now()->subDay())->delete();

        $this->info("Pruned {$access} access tokens, {$passwords} reset tokens and {$otps} registration OTPs.");
        return self::SUCCESS;
    }
}
