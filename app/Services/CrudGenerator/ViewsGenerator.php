<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ViewsGenerator
{
    public function generateViews($modelName, $fields, $command)
    {
        $parser = new FieldParser();
        $folderPath = resource_path("views/" . Str::snake(Str::plural($modelName)));
        File::ensureDirectoryExists($folderPath);

        $variableName = Str::snake($modelName);
        $collectionVariable = Str::snake(Str::plural($modelName));

        $fieldHeaders = "";
        $fieldRows = "";
        $formFields = "";
        $showFields = "";

        foreach ($parser->parse($fields) as $field) {
            $fieldParts = explode(':', $field);
            $fieldName = trim($fieldParts[0]);
            $fieldType = isset($fieldParts[1]) ? trim($fieldParts[1]) : 'string';

            if (!$fieldName || str_contains($fieldName, ')')) {
                continue;
            }

            $fieldHeaders .= "                    <th>" . ucfirst($fieldName) . "</th>\n";
            $fieldRows .= "                        <td>{{ \${$variableName}->$fieldName }}</td>\n";


            $formFields .= "            <div class='mb-3'>\n";
            $formFields .= "                <label class='form-label'>" . ucfirst($fieldName) . "</label>\n";

            if (str_starts_with($fieldType, 'enum(')) {

                $enumValues = substr($fieldType, 5, -1);
                $values = array_map(fn($value) => trim($value, " '\""), explode(',', $enumValues));
                $formFields .= "                <select class='form-control' name='$fieldName'>\n";
                $formFields .= "                    @foreach(['" . implode("','", $values) . "'] as \$val)\n";
                $formFields .= "                        <option value='{{ \$val }}' {{ old('$fieldName', \${$variableName}->$fieldName ?? '') == \$val ? 'selected' : '' }}>{{ ucfirst(\$val) }}</option>\n";
                $formFields .= "                    @endforeach\n";
                $formFields .= "                </select>\n";
            } elseif ($fieldType === 'text') {
                $formFields .= "                <textarea class='form-control' name='$fieldName'>{{ old('$fieldName', \${$variableName}->$fieldName ?? '') }}</textarea>\n";
            } else {
                $formFields .= "                <input class='form-control' type='text' name='$fieldName' value='{{ old('$fieldName', \${$variableName}->$fieldName ?? '') }}'>\n";
            }

            $formFields .= "            </div>\n";
            $showFields .= "        <p><strong>" . ucfirst($fieldName) . ":</strong> {{ \${$variableName}->$fieldName }}</p>\n";
        }

        // Index Blade File
        $indexTemplate = "@extends('layouts.app')

@section('content')
    <div class='container mt-4'>
        <h2 class='mb-4'>" . Str::plural($modelName) . " List</h2>
        <table class='table table-bordered table-striped'>
            <thead class='thead-dark'>
                <tr>
$fieldHeaders
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\$$collectionVariable as \$$variableName)
                    <tr>
$fieldRows
                        <td>
                            <a href='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".show\", \$$variableName) }}' class='btn btn-sm btn-info'>View</a>
                            <a href='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".edit\", \$$variableName) }}' class='btn btn-sm btn-primary'>Edit</a>
                            <form action='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".destroy\", \$$variableName) }}' method='POST' class='d-inline'>
                                @csrf
                                @method('DELETE')
                                <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'>Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection";
        File::put("$folderPath/index.blade.php", $indexTemplate);

        // Create Blade File
        $createTemplate = "@extends('layouts.app')

@section('content')
    <div class='container'>
        <form class='form-control' method='POST' action='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".store\") }}'>
            @csrf
$formFields
            <button class='btn btn-primary mt-4' type='submit'>Save</button>
        </form>
    </div>
@endsection";
        File::put("$folderPath/create.blade.php", $createTemplate);

        // Edit Blade File
        $editTemplate = "@extends('layouts.app')

@section('content')
    <div class='container'>
        <form class='form-control' method='POST' action='{{ route(\"" . Str::snake(Str::plural($modelName)) . ".update\", \$$variableName) }}'>
            @csrf
            @method('PUT')
$formFields
            <button class='btn btn-primary mt-4' type='submit'>Update</button>
        </form>
    </div>
@endsection";
        File::put("$folderPath/edit.blade.php", $editTemplate);

        // Show Blade File
        $showTemplate = "@extends('layouts.app')

@section('content')
    <div class='container'>
$showFields
    </div>
@endsection";
        File::put("$folderPath/show.blade.php", $showTemplate);

        $command->info("Views for $modelName created successfully.");
    }
}
