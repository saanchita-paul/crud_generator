<?php

namespace App\Console\Commands;

use App\Services\CrudGenerator\ApiControllerGenerator;
use App\Services\CrudGenerator\FieldParser;
use App\Services\CrudGenerator\LayoutGenerator;
use App\Services\CrudGenerator\MigrationGenerator;
use App\Services\CrudGenerator\ModelGenerator;
use App\Services\CrudGenerator\RequestGenerator;
use App\Services\CrudGenerator\RoutesGenerator;
use App\Services\CrudGenerator\ViewsGenerator;
use App\Services\CrudGenerator\WebControllerGenerator;
use Illuminate\Console\Command;
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

        $fieldsInput = is_array($fields) ? implode(',', $fields) : $fields;


        (new FieldParser())->parse($fields);
        (new ModelGenerator)->generateModel($modelName, $fields, $relations, $this);
        (new MigrationGenerator)->generateMigration($modelName, $fields, $this);
        (new ApiControllerGenerator)->generateApiController($modelName, $this);
        (new WebControllerGenerator)->generateWebController($modelName, $this);
        (new RequestGenerator)->generateRequest($modelName, $this);
        (new RoutesGenerator)->generateRoutes($modelName, $relations, $this);
        (new ViewsGenerator)->generateViews($modelName, $fieldsInput, $this);
        (new LayoutGenerator)->generateLayout($this);

        $this->storeCrudModel($modelName);

        $this->info("CRUD generation for $modelName completed successfully!");
    }

    protected function storeCrudModel($modelName)
    {
        $file = storage_path('crud_models.json');
        $models = [];

        if (file_exists($file)) {
            $models = json_decode(file_get_contents($file), true) ?? [];
        }

        if (!in_array($modelName, $models)) {
            $models[] = $modelName;
            file_put_contents($file, json_encode($models, JSON_PRETTY_PRINT));
        }
    }


}
