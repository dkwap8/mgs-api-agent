<?php
namespace App\Events;
use App\Models\VirtualAccount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class VirtualAccountRegenerated { use Dispatchable, SerializesModels; public function __construct(public VirtualAccount $virtualAccount, public int $replacedAccountId) {} }
