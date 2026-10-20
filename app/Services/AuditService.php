<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Throwable;

class AuditService
{
    public function log(?int $userId, string $event, ?Model $auditable = null, ?array $oldValues = null, ?array $newValues = null): ?AuditLog
    {
        try {
            return AuditLog::query()->create([
                'user_id' => request()->user() instanceof \App\Models\Admin ? null : $userId,
                'admin_id' => request()->user() instanceof \App\Models\Admin ? request()->user()->id : null,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'event' => $event,
                'old_values' => $this->redact($oldValues),
                'new_values' => $this->redact($newValues),
                'ip_address' => app()->runningInConsole() ? null : Request::ip(),
                'user_agent' => app()->runningInConsole() ? 'console' : Request::userAgent(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    public function logAction(?int $userId, string $action, ?string $description = null): ?AuditLog
    {
        return $this->log($userId, $action, null, null, ['description' => $description]);
    }

    public function getAuditTrail(Model $auditable)
    {
        return AuditLog::query()->where('auditable_type', $auditable::class)
            ->where('auditable_id', $auditable->getKey())->with('user')->latest()->get();
    }

    public function getUserAuditLogs(int $userId, int $limit = 50)
    {
        return AuditLog::query()->where('user_id', $userId)->latest()->limit(max(1, min(500, $limit)))->get();
    }

    public function getFilteredAuditLogs(array $filters = [])
    {
        $query = AuditLog::query()->with('user');
        if (isset($filters['user_id'])) $query->where('user_id', $filters['user_id']);
        if (! empty($filters['event'])) $query->where('event', $filters['event']);
        if (! empty($filters['auditable_type'])) $query->where('auditable_type', $filters['auditable_type']);
        if (! empty($filters['date_from'])) $query->where('created_at', '>=', $filters['date_from']);
        if (! empty($filters['date_to'])) $query->where('created_at', '<=', $filters['date_to']);
        return $query->latest()->paginate(max(5, min(100, (int) ($filters['per_page'] ?? 50))));
    }

    public function logSecurityEvent(?int $userId, string $event, string $severity = 'info', mixed $details = null): ?AuditLog
    {
        return $this->log($userId, $event, null, null, ['severity' => $severity, 'details' => $details]);
    }

    private function redact(?array $values): ?array
    {
        if ($values === null) return null;
        $sensitive = ['password', 'password_confirmation', 'token', 'access_token', 'refresh_token', 'secret', 'secret_key', 'webhook_secret', 'withdrawal_pin', 'two_factor_secret', 'two_factor_pending_secret', 'two_factor_recovery_codes', 'recovery_code', 'recovery_codes', 'two_factor_code', 'authorization', 'nin', 'bvn', 'otp', 'pin', 'api_key'];
        $walk = function (array $data) use (&$walk, $sensitive): array {
            foreach ($data as $key => $value) {
                if (in_array(strtolower((string) $key), $sensitive, true)) {
                    $data[$key] = '[REDACTED]';
                } elseif (is_array($value)) {
                    $data[$key] = $walk($value);
                }
            }
            return $data;
        };
        return $walk($values);
    }
}
