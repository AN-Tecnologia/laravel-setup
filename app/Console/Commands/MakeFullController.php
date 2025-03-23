<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFullController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:full-controller {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full REST controller with requests, resources, and services.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $modelNameInPascalCase = Str::studly($name);
        $modelNameInCamelCase = Str::camel($name);

        // Define the path where the controller will be created
        $controllerPath = app_path("Http/Controllers/{$modelNameInPascalCase}Controller.php");

        if (File::exists($controllerPath)) {
            $this->error("Controller {$modelNameInPascalCase}Controller already exists!");
            return;
        }

        // Generate the controller content
        $controllerContent = $this->getControllerContent($modelNameInPascalCase, $modelNameInCamelCase);

        // Write the controller content to the file
        File::put($controllerPath, $controllerContent);

        $this->info("Controller {$modelNameInPascalCase}Controller created successfully.");
    }

    /**
     * Get the content for the controller file.
     *
     * @param string $modelNameInPascalCase
     * @param string $modelNameInCamelCase
     * @return string
     */
    protected function getControllerContent(string $modelNameInPascalCase, string $modelNameInCamelCase): string
    {
        return <<<EOT
<?php

namespace App\Http\Controllers;

use App\Http\Requests\\{$modelNameInPascalCase}s\\Get{$modelNameInPascalCase}sRequest;
use App\Http\Requests\\{$modelNameInPascalCase}s\\Store{$modelNameInPascalCase}Request;
use App\Http\Requests\\{$modelNameInPascalCase}s\\Update{$modelNameInPascalCase}Request;
use App\Http\Resources\\{$modelNameInPascalCase}Resource;
use App\Models\\{$modelNameInPascalCase};
use App\Services\\{$modelNameInPascalCase}Service;
use Illuminate\Http\JsonResponse;

class {$modelNameInPascalCase}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Get{$modelNameInPascalCase}sRequest \$request, {$modelNameInPascalCase}Service \${$modelNameInCamelCase}Service): JsonResponse
    {
        \$query = \${$modelNameInCamelCase}Service->filter(\$request);

        \${$modelNameInCamelCase}s = \$query->get();

        return response()->json(['data' => {$modelNameInPascalCase}Resource::collection(\${$modelNameInCamelCase}s)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store{$modelNameInPascalCase}Request \$request, {$modelNameInPascalCase}Service \${$modelNameInCamelCase}Service): JsonResponse
    {
        \${$modelNameInCamelCase} = \${$modelNameInCamelCase}Service->create(\$request);

        return response()->json(['data' => {$modelNameInPascalCase}Resource::make(\${$modelNameInCamelCase}), 'message' => 'Cadastrado com sucesso'], JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show({$modelNameInPascalCase} \${$modelNameInCamelCase}): JsonResponse
    {
        return response()->json({$modelNameInPascalCase}Resource::make(\${$modelNameInCamelCase}));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Update{$modelNameInPascalCase}Request \$request, {$modelNameInPascalCase} \${$modelNameInCamelCase}, {$modelNameInPascalCase}Service \${$modelNameInCamelCase}Service): JsonResponse
    {
        \${$modelNameInCamelCase} = \${$modelNameInCamelCase}Service->update(\${$modelNameInCamelCase}, \$request);

        return response()->json(['data' => {$modelNameInPascalCase}Resource::make(\${$modelNameInCamelCase}), 'message' => 'Atualizado com sucesso']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$modelNameInPascalCase} \${$modelNameInCamelCase}): JsonResponse
    {
        \${$modelNameInCamelCase}->delete();

        return response()->json(['message' => 'Deletado com sucesso']);
    }
}
EOT;
    }
}
