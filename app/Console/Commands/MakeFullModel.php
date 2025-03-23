<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeFullModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:full-model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a model, migration, factory, and seeder with UUIDs and fillable/casts properties';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');

        // Step 1: Create the Model with the migration, factory, and seeder
        Artisan::call('make:model', ['name' => $name, '-m' => true, '-f' => true, '-s' => true]);

        // Step 2: Add the HasUuids trait, fillable, and casts to the model file
        $this->updateModel($name);

        $this->info('Model, migration, factory, and seeder created successfully!');
    }

    /**
     * Update the model to include the HasUuids trait, fillable, and casts.
     *
     * @param string $name
     * @return void
     */
    protected function updateModel($name)
    {
        $modelPath = app_path("Models/{$name}.php");

        if (!file_exists($modelPath)) {
            $this->error("Model file {$name}.php not found.");
            return;
        }

        $modelContent = file_get_contents($modelPath);

        // Step 1: Ensure HasUuids is added to the use statements
        if (strpos($modelContent, 'use Illuminate\Database\Eloquent\Concerns\HasUuids;') === false) {
            $modelContent = str_replace(
                "use Illuminate\Database\Eloquent\Model;",
                "use Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Concerns\HasUuids;",
                $modelContent
            );
            $modelContent = str_replace('use HasFactory;', 'use HasFactory, HasUuids;', $modelContent);
        }

        // Step 2: Insert fillable property and casts function after the class definition
        $fillable = "\n    protected \$fillable = [/* Add your fillable attributes here */];\n";
        $castsFunction = "
    /**
    * Get the attributes that should be cast.
    *
    * @return array<string, string>
    */
    protected function casts(): array
    {
        return [
            // Add your casts here
        ];
    }
";

        // Use regex to find the "use" statement inside the class and insert the properties and function after it
        $modelContent = preg_replace(
            '/(use\s+[a-zA-Z,\s]+;\n)/',
            "$0\n$fillable$castsFunction",
            $modelContent
        );

        file_put_contents($modelPath, $modelContent);

        $this->info("Updated model {$name} with HasUuids, fillable, and casts.");
    }
}
