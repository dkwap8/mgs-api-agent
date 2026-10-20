<?php

namespace App\Services;

use RuntimeException;

class WebhookUrlGuard
{
    /** Validate again at delivery time and pin DNS to the validated public address. */
    public function options(string $url): array
    {
        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || ($parts['port'] ?? 443) !== 443) {
            throw new RuntimeException('Webhook URLs must use HTTPS on port 443 without credentials or fragments.');
        }
        if (! extension_loaded('curl')) {
            throw new RuntimeException('The cURL extension is required for safe webhook delivery.');
        }
        $host = strtolower(trim($parts['host'], '[]'));
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);
        if ($addresses === []) {
            throw new RuntimeException('Webhook hostname has no public address.');
        }
        foreach ($addresses as $address) {
            if (! $this->isPublic($address)) {
                throw new RuntimeException('Webhook destinations must resolve exclusively to public Internet addresses.');
            }
        }
        $address = $addresses[0];
        $pin = str_contains($address, ':') ? '['.$address.']' : $address;
        return [
            'allow_redirects' => false,
            'proxy' => '',
            'curl' => [CURLOPT_RESOLVE => [$parts['host'].':443:'.$pin], CURLOPT_PROXY => ''],
        ];
    }

    protected function resolve(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        return array_values(array_filter(array_map(
            static fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null,
            $records ?: []
        )));
    }

    private function isPublic(string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        $packed = inet_pton($address);
        if (strlen($packed) === 16) {
            // Only global unicast IPv6; reject mapped IPv4, NAT64 and tunnelling ranges.
            return (ord($packed[0]) & 0xe0) === 0x20
                && ! str_starts_with(strtolower($address), '2002:')
                && ! str_starts_with(strtolower($address), '2001:');
        }
        $first = ord($packed[0]);
        $second = ord($packed[1]);
        return $first < 224 && ! ($first === 100 && $second >= 64 && $second <= 127);
    }
}
