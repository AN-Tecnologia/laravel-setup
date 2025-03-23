<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name : The name of the service class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');

        $path = app_path("Services/{$name}.php");

        if (File::exists($path)) {
            $this->error("Service {$name} já existe!");
            return Command::FAILURE;
        }

        if (!File::isDirectory(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        $stub = $this->buildTemplate($name);

        File::put($path, $stub);

        $this->info("Service {$name} criado com sucesso no caminho: {$path}");

        return Command::SUCCESS;
    }

    function removeLastService(string $string): string
    {
        $position = strrpos($string, 'Service');

        if ($position !== false) {
            return substr($string, 0, $position) . substr($string, $position + strlen('Service'));
        }

        return $string;
    }

    private function buildTemplate(string $name): string
    {
        $modelName = $this->removeLastService($name);
        $camelCaseName = Str::camel($modelName);

        return  <<<STRING
<?php

namespace App\Services;

use App\Models\\$modelName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request; 
        
class {$name}
{
    /**
     * Filter resources based on the provided criteria.
     */
    public function filter(Request \$request): Builder
    {
        \$query = $modelName::query();

        return \$query;
    }

    public function create(Request \$request): $modelName
    {
        $$camelCaseName = $modelName::create(\$request->all());

        return $$camelCaseName;
    }

    public function update($modelName \${$camelCaseName}, Request \$request): $modelName
    {
        \${$camelCaseName}->update(\$request->all());

        return \${$camelCaseName};
    }
}
STRING;
    }
}
