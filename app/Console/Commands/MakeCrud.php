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

            // Ensure valid field definition
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

                // Ensure relation definition is valid
                if (count($relationParts) < 2) {
                    $this->error("Invalid relation definition: $relation");
                    continue;
                }

                [$relatedModel, $type] = $relationParts;
                $relatedModel = Str::studly($relatedModel);

                if ($type === 'hasMany') {
                    $relationsCode .= "\n    public function " . Str::camel(Str::plural($relatedModel)) . "() {\n";
                    $relationsCode .= "        return \$this->hasMany($relatedModel::class);\n";
                    $relationsCode .= "    }\n";
                } elseif ($type === 'belongsTo') {
                    $relationsCode .= "\n    public function " . Str::camel($relatedModel) . "() {\n";
                    $relationsCode .= "        return \$this->belongsTo($relatedModel::class);\n";
                    $relationsCode .= "    }\n";
                } else {
                    $this->error("Invalid relation type: $type for $relatedModel");
                }
            }
        }

        $modelTemplate = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class $modelName extends Model
{
    use HasFactory;

    protected \$fillable = [$fillableString];

    $relationsCode
}";

        File::put(app_path("Models/{$modelName}.php"), $modelTemplate);
        $this->info("Model $modelName created successfully.");
    }



    protected function generateMigration($modelName, $fields)
    {
        $tableName = Str::snake(Str::plural($modelName));
        $migrationName = 'create_' . $tableName . '_table';
        $timestamp = now()->format('Y_m_d_His');
        $migrationFile = database_path("migrations/{$timestamp}_{$migrationName}.php");

        $schemaFields = '';
        foreach (explode(',', $fields) as $field) {
            $field = trim($field);

            // Handle the case where field type contains colons (like enum values)
            $firstColonPos = strpos($field, ':');
            if ($firstColonPos === false) {
                $this->error("Invalid field definition: $field");
                continue;
            }

            $name = substr($field, 0, $firstColonPos);
            $typeDefinition = substr($field, $firstColonPos + 1);

            // Handle enum fields
            if (str_starts_with($typeDefinition, 'enum(')) {
                $enumValues = substr($typeDefinition, 5, -1); // Remove 'enum(' and ')'
                $values = array_map(function($value) {
                    return "'" . trim($value, " '\"") . "'"; // Trim spaces and quotes
                }, explode(',', $enumValues));

                $valuesString = implode(', ', $values);
                $schemaFields .= "\$table->enum('$name', [$valuesString]);\n            ";
            } else {
                // Handle regular field types
                switch ($typeDefinition) {
                    case 'string':
                        $schemaFields .= "\$table->string('$name');\n            ";
                        break;
                    case 'text':
                        $schemaFields .= "\$table->text('$name');\n            ";
                        break;
                    default:
                        $schemaFields .= "\$table->$typeDefinition('$name');\n            ";
                }
            }
        }

        $migrationTemplate = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->id();
            $schemaFields
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('$tableName');
    }
};";

        File::put($migrationFile, $migrationTemplate);
        $this->info("Migration for $modelName created successfully.");
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
                        $validationRules .= "'$name' => 'required',\n            ";
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
        return response()->json($modelName::all());
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


    protected function generateRoutes($modelName)
    {
        $controller = "{$modelName}Controller";

        $apiRoute = "Route::apiResource('" . Str::snake(Str::plural($modelName)) . "', $controller::class);";
        File::append(base_path('routes/api.php'), "\n$apiRoute\n");

        $webRoute = "Route::resource('" . Str::snake(Str::plural($modelName)) . "', $controller::class);";
        File::append(base_path('routes/web.php'), "\n$webRoute\n");

        $this->info("Routes for $modelName added successfully.");
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
