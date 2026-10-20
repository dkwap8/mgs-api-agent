<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\ApiLog;
class CheckSystemHealth extends Command
{
    protected $signature='app:check-system-health';
    protected $description='Check database, storage headroom and recent API server errors';
    public function handle(): int {
        $db=true;
        try { DB::select('SELECT 1'); } catch (\Throwable) { $db=false; }
        $free=disk_free_space(storage_path()); $total=disk_total_space(storage_path());
        $requests=0; $errors=0;
        if ($db) { $q=ApiLog::query()->where('created_at','>=',now()->subMinutes(15)); $requests=(clone $q)->count(); $errors=(clone $q)->where('status_code','>=',500)->count(); }
        $rate=$requests ? round($errors/$requests*100,2) : null;
        $healthy=$db && $free!==false && $free>=config('operations.minimum_free_disk_bytes') && ($rate===null || $rate<config('operations.api_error_threshold_percent'));
        $result=['checked_at'=>now()->toIso8601String(),'healthy'=>$healthy,'database_available'=>$db,
            'storage_free_bytes'=>$free,'storage_total_bytes'=>$total,'php_process_peak_memory_bytes'=>memory_get_peak_usage(true),
            'api_requests_15m'=>$requests,'api_server_errors_15m'=>$errors,'api_error_percent'=>$rate,
            'host_cpu'=>'requires_host_monitoring','host_ram'=>'requires_host_monitoring'];
        $this->line(json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
        try {
            Cache::put('platform:health',$result,now()->addMinutes(15));
            if (! $healthy && $db && Cache::add('platform:health-alert',true,now()->addMinutes(15))) app(\App\Services\NotificationService::class)->notifyAdmin('system_alert','Platform health degraded','Review database availability, storage headroom and recent API errors.',$result);
        } catch (\Throwable) { $this->warn('Health results could not be cached; check the shared cache service.'); return self::FAILURE; }
        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
