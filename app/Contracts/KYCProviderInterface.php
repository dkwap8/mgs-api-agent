<?php

namespace App\Contracts;

interface KYCProviderInterface
{
    /**
     * @return array{status:string,message?:string,name?:string,dob?:string,reference?:string,retryable?:bool,raw?:array}
     */
    public function verify(string $type, string $number, ?string $name = null): array;
}
