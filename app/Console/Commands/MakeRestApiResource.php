<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRestApiResource extends Command
{
    protected $signature = 'make:rest-api-resource {name}';
    protected $description = 'Generate a model with controller, service, requests, migration, and resource';

    public function handle()
    {
        $name = $this->argument('name');

        if ($this->isPlural($name)) {
            $this->error("O nome do modelo deve estar no singular. Por favor, forneça um nome válido.");
            return;
        }

        $name = ucfirst($name);

        if (ctype_lower($this->argument('name')[0])) {
            $this->info("O nome do modelo foi ajustado para: $name");
        }

        Artisan::call('make:full-model ' . $name);
        Artisan::call('make:full-controller ' . $name);
        Artisan::call('make:service ' . $name . 'Service');
        Artisan::call('make:requests ' . $name);
        Artisan::call('make:full-resource ' . $name);

        $this->addRouteToApiFile($name);

        $this->info("Successfully generated $name with all components.");
    }

    /**
     * Check if the name is plural.
     *
     * @param string $name
     * @return bool
     */
    protected function isPlural(string $name): bool
    {
        return Str::endsWith($name, 's') && !Str::endsWith($name, ['ss', 'us', 'is']);
    }

    /**
     * Adiciona a rota para o controller no arquivo routes/api.php.
     */
    protected function addRouteToApiFile(string $modelNameInPascalCase): void
    {
        $apiRoutesPath = base_path('routes/api.php');

        $apiRoutesContent = File::get($apiRoutesPath);

        $modelNameInKebabCase = Str::kebab($modelNameInPascalCase);
        $routeLine = "    '{$modelNameInKebabCase}s' => {$modelNameInPascalCase}Controller::class,";

        $controllerImport = "use App\Http\Controllers\\{$modelNameInPascalCase}Controller;";
        if (strpos($apiRoutesContent, $controllerImport) === false) {
            $apiRoutesContent = preg_replace('/<\?php\s*\n/', "<?php\n\n$controllerImport\n", $apiRoutesContent);
        }

        if (strpos($apiRoutesContent, 'Route::apiResources([') !== false) {
            $apiRoutesContent = preg_replace(
                '/Route::apiResources\(\[\s*(.*?)\s*\]\);/s',
                "Route::apiResources([\n$1\n$routeLine\n]);",
                $apiRoutesContent
            );
        } else {
            $apiRoutesContent .= "\n\nRoute::apiResources([\n$routeLine\n]);\n";
        }

        File::put($apiRoutesPath, $apiRoutesContent);

        $this->info("Route added to api.php: Route::apiResource('{$modelNameInKebabCase}', {$modelNameInPascalCase}Controller::class);");
    }
}
