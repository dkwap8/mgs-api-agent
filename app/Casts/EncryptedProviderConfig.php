<?php
namespace App\Casts;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class EncryptedProviderConfig implements CastsAttributes
{
    public function get(Model $model,string $key,mixed $value,array $attributes): array {
        if($value===null || $value==='')return [];
        $data=json_decode($value,true,512,JSON_THROW_ON_ERROR);
        if(isset($data['_encrypted']))return json_decode(Crypt::decryptString($data['_encrypted']),true,512,JSON_THROW_ON_ERROR);
        // Read legacy configuration without silently rewriting existing rows.
        return is_array($data)?$data:[];
    }
    public function set(Model $model,string $key,mixed $value,array $attributes): string {
        return json_encode(['_encrypted'=>Crypt::encryptString(json_encode($value??[],JSON_THROW_ON_ERROR))],JSON_THROW_ON_ERROR);
    }
}
