<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-service {name : The name of the service class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a service class inside app/Services';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = $this->argument('name');
        $className = str($name)->studly()->finish('Service');
        $path = app_path("Services/{$className}.php");

        if (File::exists($path)) {
            $this->error("Service {$className} already exists!");
            return;
        }

        $stub = <<<EOT
<?php

namespace App\Services;

class {$className} extends BaseService::class
{
    //
}
EOT;

        File::ensureDirectoryExists(app_path('Services'));
        File::put($path, $stub);

        $this->info("Service created: app/Services/{$className}.php");
    }
}
