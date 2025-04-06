<?php
namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WebControllerGenerator
{
    public function generateWebController($modelName, $command)
    {
        $pluralModelName = Str::plural(Str::snake($modelName));
        $variableName = Str::camel($modelName);

        $controllerTemplate = "<?php

namespace App\Http\Controllers;

use App\Models\\$modelName;
use App\Http\Requests\\{$modelName}Request;
use Illuminate\Http\Request;

class {$modelName}Controller extends Controller
{
    public function index()
    {
        return view('$pluralModelName.index', ['$pluralModelName' => $modelName::all()]);
    }

    public function create()
    {
        return view('$pluralModelName.create');
    }

    public function store({$modelName}Request \$request)
    {
        \${$variableName} = $modelName::create(\$request->validated());
        return redirect()->route('$pluralModelName.index');
    }

     public function show($modelName \${$variableName})
    {
        return view('$pluralModelName.show', compact('$variableName'));
    }

    public function edit($modelName \${$variableName})
    {
        return view('$pluralModelName.edit', compact('$variableName'));
    }

    public function update({$modelName}Request \$request, $modelName \${$variableName})
    {
        \${$variableName}->update(\$request->validated());
        return redirect()->route('$pluralModelName.index');
    }

    public function destroy($modelName \${$variableName})
    {
        \${$variableName}->delete();
        return redirect()->route('$pluralModelName.index');
    }
}";

        File::put(app_path("Http/Controllers/{$modelName}Controller.php"), $controllerTemplate);
        $command->info("Web Controller for $modelName created successfully.");
    }
}
