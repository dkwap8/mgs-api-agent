<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Customer extends Model
{
    public const KYC_PENDING='pending'; public const KYC_VERIFIED='verified'; public const KYC_FAILED='failed';
    public const STATUS_ACTIVE='active'; public const STATUS_INACTIVE='inactive'; public const STATUS_BLOCKED='blocked';
    protected $fillable=['merchant_id','business_id','kyc_identity_id','external_reference','first_name','last_name','email','phone','kyc_status','status','metadata'];
    protected $hidden=['metadata'];
    protected $casts=['metadata'=>'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function kycIdentity(): BelongsTo { return $this->belongsTo(KYCIdentity::class); }
    public function virtualAccounts(): HasMany { return $this->hasMany(VirtualAccount::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function isKYCVerified(): bool { return $this->kyc_status===self::KYC_VERIFIED; }
    public function fullName(): string { return trim($this->first_name.' '.$this->last_name); }
}