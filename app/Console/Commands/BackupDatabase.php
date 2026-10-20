<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database';
    protected $description = 'Create a private, non-destructive database snapshot';
    public function handle(): int
    {
        $directory=storage_path('app/private/backups');
        if (! is_dir($directory) && ! mkdir($directory,0700,true)) { $this->error('Cannot create private backup directory.'); return self::FAILURE; }
        $driver=DB::connection()->getDriverName();
        $path=$directory.DIRECTORY_SEPARATOR.'database-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).($driver==='sqlite'?'.sqlite':'.sql');
        $partial=$path.'.partial';
        try {
            if ($driver==='sqlite') {
                // Consistent SQLite snapshot, including WAL content; never copy an active DB file.
                DB::connection()->getPdo()->exec("VACUUM INTO '".str_replace("'","''",$partial)."'");
            } elseif ($driver==='mysql' || $driver==='mariadb') {
                $c=DB::connection()->getConfig();
                $args=[config('operations.backup_binary'),'--single-transaction','--quick','--hex-blob','--routines','--triggers',
                    '--host='.($c['host']??'127.0.0.1'),'--port='.($c['port']??3306),'--user='.$c['username'],'--result-file='.$partial];
                if (! empty($c['unix_socket'])) $args[]='--socket='.$c['unix_socket'];
                $args[]=$c['database'];
                // Password travels only in the child process environment; never arguments or command output.
                $p=new Process($args,null,['MYSQL_PWD'=>(string)($c['password']??'')]);
                $p->setTimeout(900); $p->run();
                if (! $p->isSuccessful()) throw new \RuntimeException('Database dump failed; check backup binary, database permissions and connectivity.');
            } else throw new \RuntimeException('This database driver has no configured backup adapter.');
            if (! is_file($partial) || filesize($partial)===0 || ! rename($partial,$path)) throw new \RuntimeException('Backup snapshot could not be finalised.');
            if (PHP_OS_FAMILY!=='Windows') chmod($path,0600);
            $this->info('Private backup created: '.$path);
            $this->line('SHA256: '.hash_file('sha256',$path));
            $this->warn('Restore acceptance and an encrypted off-server copy are still required.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (is_file($partial)) unlink($partial);
            $this->error('Backup failed. Verify dump binary, database permissions and private directory access.');
            app(\App\Services\NotificationService::class)->notifyAdmin('backup_failed','Database backup failed','The scheduled database snapshot did not complete.');
            return self::FAILURE;
        }
    }
}
