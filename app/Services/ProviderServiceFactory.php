<?php

namespace App\Services;

use App\Contracts\ProviderInterface;
use App\Models\Provider;
use App\Services\ProviderServices\BaseProviderService;
use App\Services\ProviderServices\LocalVirtualAccountService;
use App\Services\ProviderServices\MonnifyService;
use App\Services\ProviderServices\NinePsbService;
use App\Services\ProviderServices\OpayService;
use App\Services\ProviderServices\PalmPayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ProviderServiceFactory
{
    private const DRIVER_MAP = [
        'local' => LocalVirtualAccountService::class,
        'local-virtual-account' => LocalVirtualAccountService::class,
        'local_virtual_account' => LocalVirtualAccountService::class,
        'palmpay' => PalmPayService::class,
        'palm-pay' => PalmPayService::class,
        'opay' => OpayService::class,
        'o-pay' => OpayService::class,
        '9psb' => NinePsbService::class,
        'nine-psb' => NinePsbService::class,
        'nine_psb' => NinePsbService::class,
        'monnify' => MonnifyService::class,
    ];

    public function make(Provider $provider): ProviderInterface
    {
        $serviceClass = $this->resolveServiceClass($provider);

        if (! class_exists($serviceClass)) {
            Log::warning('Provider service class was not found; using the base provider service.', [
                'provider_id' => $provider->id,
                'provider_slug' => $provider->slug,
                'service_class' => $serviceClass,
            ]);

            $serviceClass = BaseProviderService::class;
        }

        if ($serviceClass !== BaseProviderService::class && ! is_subclass_of($serviceClass, ProviderInterface::class)) {
            throw new RuntimeException(sprintf('%s must implement %s.', $serviceClass, ProviderInterface::class));
        }

        $service = app()->makeWith($serviceClass, ['provider' => $provider]);

        if (! $service instanceof ProviderInterface) {
            throw new RuntimeException('The resolved provider service is invalid.');
        }

        return $service;
    }

    private function resolveServiceClass(Provider $provider): string
    {
        $metadataClass = trim((string) ($provider->metadata['service_class'] ?? ''));

        if ($metadataClass !== '') {
            $allowedNamespace = 'App\\Services\\ProviderServices\\';

            if (! str_starts_with($metadataClass, $allowedNamespace)) {
                throw new RuntimeException('Provider service classes must be inside ' . $allowedNamespace);
            }

            return $metadataClass;
        }

        $driver = Str::lower(trim((string) ($provider->metadata['driver'] ?? $provider->slug)));

        if (isset(self::DRIVER_MAP[$driver])) {
            return self::DRIVER_MAP[$driver];
        }

        return 'App\\Services\\ProviderServices\\'
            . Str::studly(str_replace(['-', '_'], ' ', $driver))
            . 'Service';
    }
}
