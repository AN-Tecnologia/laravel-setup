<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:requests {name : The name of the base model class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create form request classes for a model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseName = $this->argument('name');
        $pluralBaseName = Str::plural($baseName);

        $this->createRequestsFolder($pluralBaseName);

        $this->createRequestFile($baseName, $pluralBaseName, 'Get');
        $this->createRequestFile($baseName, $pluralBaseName, 'Store');
        $this->createRequestFile($baseName, $pluralBaseName, 'Update');

        $this->info("Requests for {$baseName} created successfully!");

        return Command::SUCCESS;
    }

    private function createRequestsFolder(string $pluralBaseName): void
    {
        if (!File::isDirectory(app_path('Http/Requests'))) {
            File::makeDirectory(app_path('Http/Requests'), 0755, true);
        }

        if (!File::isDirectory(app_path("Http/Requests/{$pluralBaseName}"))) {
            File::makeDirectory(app_path("Http/Requests/{$pluralBaseName}"), 0755, true);
        }
    }

    private function createRequestFile(string $baseName, string $pluralBaseName, string $type): void
    {
        if ($type == 'Get') {
            $className = "{$type}{$pluralBaseName}Request";
        } else {
            $className = "{$type}{$baseName}Request";
        }

        $filePath = app_path("Http/Requests/{$pluralBaseName}/{$className}.php");

        if (!File::exists($filePath)) {
            $stub = $this->buildRequestTemplate($className, $pluralBaseName);
            File::put($filePath, $stub);
            $this->info("Created: {$className}");
        } else {
            $this->info("Skipped: {$className} already exists.");
        }
    }

    private function buildRequestTemplate(string $className, string $pluralBaseName): string
    {
        return <<<STRING
<?php

namespace App\Http\Requests\\{$pluralBaseName};

use Illuminate\Foundation\Http\FormRequest;

class {$className} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Add your validation rules here
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Add your custom error messages here
        ];
    }
}
STRING;
    }
}
