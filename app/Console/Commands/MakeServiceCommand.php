<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {name : The name of the service}';
    protected $description = 'Create a new service class';

    public function handle()
    {
        $name = $this->argument('name');
        $path = app_path("Services/{$name}.php");
        
        // Create Services directory if it doesn't exist
        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0777, true);
        }
        
        // Check if service already exists
        if (File::exists($path)) {
            $this->error("Service {$name} already exists!");
            return 1;
        }
        
        // Generate service file
        $stub = "<?php

namespace App\Services;

class {$name}
{
    public function __construct()
    {
        // Initialize service
    }

    public function handle()
    {
        // Service logic here
        return \$this;
    }
}
";
        
        File::put($path, $stub);
        $this->info("✅ Service {$name} created successfully!");
        $this->info("📁 Path: app/Services/{$name}.php");
        
        return 0;
    }
}