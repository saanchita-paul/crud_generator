<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCrud extends Command
{
    protected $signature = 'make:crud {model}
                            {--fields= : Define fields (e.g., name:string, description:text)}
                            {--relations= : Define relationships (e.g., tasks:hasMany)}';

    protected $description = 'Generate a complete CRUD system including model, migration, controller, request, routes, and views';

    public function handle()
    {
        $modelName = Str::studly($this->argument('model'));
        $fields = $this->option('fields');
        $relations = $this->option('relations');

        $this->info("Generating CRUD for model: $modelName...");

        $this->generateModel($modelName, $fields, $relations);
        $this->generateMigration($modelName, $fields);
        $this->generateController($modelName);
        $this->generateRequest($modelName);
        $this->generateRoutes($modelName);
        $this->generateViews($modelName);

        $this->info("CRUD generation for $modelName completed successfully!");
    }

    protected function generateModel($modelName, $fields, $relations)
    {
        $fieldsArray = explode(',', $fields);
        $fillableFields = [];

        foreach ($fieldsArray as $field) {
            $fieldParts = explode(':', trim($field));
            if (count($fieldParts) < 2) {
                $this->error("Invalid field definition: $field");
                continue;
            }
            $fillableFields[] = "'" . $fieldParts[0] . "'";
        }

        $fillableString = implode(', ', $fillableFields);

        $relationsCode = '';
        if (!empty($relations)) {
            foreach (explode(',', $relations) as $relation) {
                $relationParts = explode(':', trim($relation));
                if (count($relationParts) < 2) {
                    $this->error("Invalid relation definition: $relation");
                    continue;
                }

                [$relatedModel, $type] = $relationParts;
                $relatedModelStudly = Str::studly(Str::singular($relatedModel));

                if ($type === 'hasMany') {
                    $relationsCode .= "\n    public function " . Str::camel(Str::plural($relatedModelStudly)) . "() {\n";
                    $relationsCode .= "        return \$this->hasMany($relatedModelStudly::class);\n";
                    $relationsCode .= "    }\n";

                    // Generate the related model automatically
                    $this->generateModel($relatedModelStudly, 'title:string,description:text,status:string', "$modelName:belongsTo");
                } elseif ($type === 'belongsTo') {
                    $relationsCode .= "\n    public function " . Str::camel($relatedModelStudly) . "() {\n";
                    $relationsCode .= "        return \$this->belongsTo($relatedModelStudly::class);\n";
                    $relationsCode .= "    }\n";
                } else {
                    $this->error("Invalid relation type: $type for $relatedModelStudly");
                }
            }
        }

        $modelTemplate = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class $modelName extends Model
{
    use HasFactory, SoftDeletes;

    protected \$fillable = [$fillableString];
    protected \$dates = ['deleted_at'];

    $relationsCode
}";

        File::put(app_path("Models/{$modelName}.php"), $modelTemplate);
        $this->info("Model $modelName created successfully.");
    }



    protected function generateMigration($modelName, $fields)
    {
        $tableNames = [Str::snake(Str::plural($modelName)), 'tasks'];
        foreach ($tableNames as $tableName) {
            $migrationName = 'create_' . $tableName . '_table';
            $timestamp = now()->format('Y_m_d_His');
            $migrationFile = database_path("migrations/{$timestamp}_{$migrationName}.php");

            // Ensure migrations directory exists
            if (!File::exists(database_path('migrations'))) {
                File::makeDirectory(database_path('migrations'), 0755, true);
            }

            $schemaFields = '';
            foreach (explode(',', $fields) as $field) {
                $field = trim($field);

                $firstColonPos = strpos($field, ':');
                if ($firstColonPos === false) {
                    $this->error("Invalid field definition: $field");
                    continue;
                }

                $name = substr($field, 0, $firstColonPos);
                $typeDefinition = substr($field, $firstColonPos + 1);

                // Handle status field explicitly
                if ($name === 'status') {
                    $schemaFields .= "\$table->enum('status', ['open', 'closed']);\n            ";
                    continue;
                }

                // Handle enum fields
                if (str_starts_with($typeDefinition, 'enum(')) {
                    $enumValues = substr($typeDefinition, 5, -1); // Remove 'enum(' and ')'
                    $values = array_map(fn($value) => "'" . trim($value, " '") . "'", explode(',', $enumValues));
                    $valuesString = implode(', ', $values);
                    $schemaFields .= "\$table->enum('$name', [$valuesString]);\n            ";
                } else {
                    // Handle regular field types
                    $schemaFields .= match ($typeDefinition) {
                        'string' => "\$table->string('$name');\n            ",
                        'text' => "\$table->text('$name');\n            ",
                        default => "\$table->$typeDefinition('$name');\n            ",
                    };
                }
            }

            $migrationTemplate = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->id();
            $schemaFields
            \$table->softDeletes();
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('$tableName');
    }
};";

            File::put($migrationFile, $migrationTemplate);
            $this->info("Migration for $tableName created successfully.");
        }
    }


    protected function generateRequest($modelName)
    {
        $requestClass = "{$modelName}Request";
        $fields = $this->option('fields');

        $validationRules = '';
        foreach (explode(',', $fields) as $field) {
            $field = trim($field);
            $fieldParts = explode(':', $field, 2); // Split only on first colon

            if (count($fieldParts) < 2) {
                $this->error("Invalid field definition: $field");
                continue;
            }

            [$name, $type] = $fieldParts;

            // Handle enum fields specially
            if (str_starts_with($type, 'enum(') && str_ends_with($type, ')')) {
                $enumValues = str_replace(['enum(', ')'], '', $type);
                $values = str_replace(',', ',', $enumValues); // Keep values as they are
                $validationRules .= "'$name' => 'required|in:$values',\n            ";
            } else {
                // Handle regular field types
                switch ($type) {
                    case 'string':
                        $validationRules .= "'$name' => 'required|string|max:255',\n            ";
                        break;
                    case 'text':
                        $validationRules .= "'$name' => 'nullable|string',\n            ";
                        break;
                    default:
                        $validationRules .= "'$name' => 'required|in:open,closed',\n            ";
                }
            }
        }

        $requestTemplate = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class $requestClass extends FormRequest
{
    public function rules()
    {
        return [
            $validationRules
        ];
    }
}";

        $requestsPath = app_path('Http/Requests');
        File::ensureDirectoryExists($requestsPath);
        File::put("$requestsPath/{$requestClass}.php", $requestTemplate);
        $this->info("Form Request for $modelName created successfully.");
    }


    protected function generateController($modelName)
    {
        $controllerTemplate = "<?php

namespace App\Http\Controllers;

use App\Models\\$modelName;
use App\Http\Requests\\{$modelName}Request;
use Illuminate\Http\Request;

class {$modelName}Controller extends Controller
{
    public function index()
    {
        return $modelName::all();
    }

    public function store({$modelName}Request \$request)
    {
        \$record = $modelName::create(\$request->validated());
        return response()->json(\$record, 201);
    }

    public function show($modelName \$record)
    {
        return response()->json(\$record);
    }

    public function update({$modelName}Request \$request, $modelName \$record)
    {
        \$record->update(\$request->validated());
        return response()->json(\$record);
    }

    public function destroy($modelName \$record)
    {
        \$record->delete();
        return response()->json(null, 204);
    }
}";

        File::put(app_path("Http/Controllers/{$modelName}Controller.php"), $controllerTemplate);
        $this->info("Controller for $modelName created successfully.");
    }


    protected function generateRoutes($modelName, $relations = [])
    {
        $controller = "{$modelName}Controller";
        $modelSnakePlural = Str::snake(Str::plural($modelName));
        $controllerNamespace = "App\Http\Controllers\\$controller";

        // Ensure the `web.php` and `api.php` files have `<?php` and proper imports
        $this->ensureRouteFile(base_path('routes/web.php'));
        $this->ensureRouteFile(base_path('routes/api.php'));

        // Append resource routes
        $this->appendRoute(base_path('routes/web.php'), "use $controllerNamespace;\nRoute::resource('$modelSnakePlural', $controller::class);");
        $this->appendRoute(base_path('routes/api.php'), "use $controllerNamespace;\nRoute::apiResource('$modelSnakePlural', $controller::class);");

        $this->info("Routes for $modelName added successfully.");

        // Handle nested resource routes for hasMany relationships
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                $relationParts = explode(':', trim($relation));

                if (count($relationParts) < 2) {
                    continue;
                }

                [$relatedModel, $type] = $relationParts;
                $relatedModel = Str::studly(Str::singular($relatedModel)); // Ensure singular model name
                $relatedModelPlural = Str::snake(Str::plural($relatedModel)); // Plural snake-case for route
                $relatedController = "{$relatedModel}Controller";
                $relatedControllerNamespace = "App\Http\Controllers\\$relatedController";

                if ($type === 'hasMany') {
                    $nestedWebRoute = "use $relatedControllerNamespace;\nRoute::resource('$modelSnakePlural/{".Str::snake($modelName)."}/$relatedModelPlural', {$relatedController}::class);";
                    $nestedApiRoute = "use $relatedControllerNamespace;\nRoute::apiResource('$modelSnakePlural/{".Str::snake($modelName)."}/$relatedModelPlural', {$relatedController}::class);";

                    $this->appendRoute(base_path('routes/web.php'), $nestedWebRoute);
                    $this->appendRoute(base_path('routes/api.php'), $nestedApiRoute);

                    $this->info("Nested routes for $relatedModel under $modelName added successfully.");
                }
            }
        }
    }

    /**
     * Ensure that the route file starts with `<?php`
     */
    protected function ensureRouteFile($filePath)
    {
        if (!file_exists($filePath)) {
            File::put($filePath, "<?php\n\n");
        } else {
            $contents = File::get($filePath);
            if (!str_starts_with(trim($contents), '<?php')) {
                File::put($filePath, "<?php\n\n" . $contents);
            }
        }
    }

    /**
     * Append a route while ensuring duplicate imports are not added
     */
    protected function appendRoute($filePath, $routeCode)
    {
        $contents = File::get($filePath);

        // Avoid duplicate imports or routes
        if (!str_contains($contents, $routeCode)) {
            File::append($filePath, "\n$routeCode\n");
        }
    }



    protected function generateViews($modelName)
    {
        $folderPath = resource_path("views/" . Str::snake(Str::plural($modelName)));
        File::ensureDirectoryExists($folderPath);

        $indexTemplate = "<x-layout>
    <div class='container'>
        <table>
            @foreach (\$" . Str::snake(Str::plural($modelName)) . " as \$record)
                <tr>
                    <td>{{ \$record->name }}</td>
                    <td>{{ \$record->status }}</td>
                    <td>
                        <a href='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".edit\", \$record) }}'>Edit</a>
                        <form action='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".destroy\", \$record) }}' method='POST'>
                            @csrf
                            @method('DELETE')
                            <button type='submit'>Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</x-layout>";

        File::put("$folderPath/index.blade.php", $indexTemplate);

        $createTemplate = "<x-layout>
    <form method='POST' action='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".store\") }}'>
        @csrf
        <input type='text' name='name' placeholder='Enter Name'>
        <button type='submit'>Save</button>
    </form>
</x-layout>";

        File::put("$folderPath/create.blade.php", $createTemplate);

        $editTemplate = "<x-layout>
    <form method='POST' action='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".update\", \$record) }}'>
        @csrf
        @method('PUT')
        <input type='text' name='name' value='{{ \$record->name }}'>
        <button type='submit'>Update</button>
    </form>
</x-layout>";

        File::put("$folderPath/edit.blade.php", $editTemplate);

        $showTemplate = "<x-layout>
    <div>
        <h2>{{ \$record->name }}</h2>
        <p>{{ \$record->status }}</p>
    </div>
</x-layout>";

        File::put("$folderPath/show.blade.php", $showTemplate);

        $this->info("Views for $modelName created successfully.");
    }

}
