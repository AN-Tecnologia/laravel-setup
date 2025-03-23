<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFullResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:full-resource {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full resource with default structure for a model.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $modelNameInPascalCase = Str::studly($name);

        $resourcePath = app_path("Http/Resources/{$modelNameInPascalCase}Resource.php");

        $resourceDirectory = app_path("Http/Resources");
        File::ensureDirectoryExists($resourceDirectory);

        if (File::exists($resourcePath)) {
            $this->error("Resource {$modelNameInPascalCase}Resource already exists!");
            return;
        }

        $resourceContent = $this->getResourceContent($modelNameInPascalCase);

        File::put($resourcePath, $resourceContent);

        $this->info("Resource {$modelNameInPascalCase}Resource created successfully.");
    }


    /**
     * Get the content for the resource file.
     *
     * @param string $modelNameInPascalCase
     * @return string
     */
    protected function getResourceContent(string $modelNameInPascalCase): string
    {
        return <<<EOT
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$modelNameInPascalCase}Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            ...parent::toArray(\$request),
            // Add relations here
        ];
    }
}
EOT;
    }
}
