<?php
namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ApiControllerGenerator
{
    public function generateApiController($modelName, $command)
    {
        $variableName = Str::camel($modelName);
        $controllerTemplate = "<?php

namespace App\Http\Controllers\Api;

use App\Models\\$modelName;
use App\Http\Requests\\{$modelName}Request;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class {$modelName}Controller extends Controller
{
    public function index()
    {
        return response()->json($modelName::all());
    }

    public function store({$modelName}Request \$request)
    {
        \${$variableName} = $modelName::create(\$request->validated());
        return response()->json(\${$variableName}, 201);

    }

    public function show($modelName \${$variableName})
    {
       return response()->json(\${$variableName});
    }

    public function update({$modelName}Request \$request, $modelName \${$variableName})
    {
        \${$variableName}->update(\$request->validated());
        return response()->json(\${$variableName});
    }

    public function destroy($modelName \${$variableName})
    {
         \${$variableName}->delete();
        return response()->json(null, 204);
    }
}";

        File::ensureDirectoryExists(app_path('Http/Controllers/Api'));
        File::put(app_path("Http/Controllers/Api/{$modelName}Controller.php"), $controllerTemplate);
        $command->info("API Controller for $modelName created successfully.");
    }
}
