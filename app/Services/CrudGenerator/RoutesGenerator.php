<?php
namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RoutesGenerator
{
    public function generateRoutes($modelName,$relations, $command)
    {
        $controller = "{$modelName}Controller";
        $modelSnakePlural = Str::snake(Str::plural($modelName));
        $webControllerNamespace = "App\Http\Controllers\\$controller";
        $apiControllerNamespace = "App\Http\Controllers\Api\\$controller";

        // Ensure the `web.php` and `api.php` files have `<?php` and proper imports
        $this->ensureRouteFile(base_path('routes/web.php'));
        $this->ensureRouteFile(base_path('routes/api.php'));

        // Append resource routes
        $this->appendRoute(base_path('routes/web.php'), "use $webControllerNamespace;\nRoute::resource('$modelSnakePlural', $controller::class);");
        $this->appendRoute(base_path('routes/api.php'), "use $apiControllerNamespace;\nRoute::apiResource('$modelSnakePlural', $controller::class);");

        $command->info("Routes for $modelName added successfully.");

        // Handle nested resource routes for hasMany relationships
        if (!empty($relations)) {
            if (is_string($relations)) {
                $relations = explode(',', $relations);
            }

            foreach ($relations as $relation) {
                $relationParts = explode(':', trim($relation));

                if (count($relationParts) < 2) {
                    continue;
                }

                [$relatedModel, $type] = $relationParts;
                $relatedModel = Str::studly(Str::singular($relatedModel)); // Ensure singular model name
                $relatedModelPlural = Str::snake(Str::plural($relatedModel)); // Plural snake-case for route
                $relatedController = "{$relatedModel}Controller";
                $webRelatedControllerNamespace = "App\Http\Controllers\\$relatedController";
                $apiRelatedControllerNamespace = "App\Http\Controllers\Api\\$relatedController";

                if ($type === 'hasMany') {
                    $nestedWebRoute = "use $webRelatedControllerNamespace;\nRoute::resource('$modelSnakePlural/{".Str::snake($modelName)."}/$relatedModelPlural', {$relatedController}::class);";
                    $nestedApiRoute = "use $apiRelatedControllerNamespace;\nRoute::apiResource('$modelSnakePlural/{".Str::snake($modelName)."}/$relatedModelPlural', {$relatedController}::class);";

                    $this->appendRoute(base_path('routes/web.php'), $nestedWebRoute);
                    $this->appendRoute(base_path('routes/api.php'), $nestedApiRoute);

                    $command->info("Nested routes for $relatedModel under $modelName added successfully.");
                }
            }
        }
    }

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

    protected function appendRoute($filePath, $routeCode)
    {
        $contents = File::get($filePath);

        // Avoid duplicate imports or routes
        if (!str_contains($contents, $routeCode)) {
            File::append($filePath, "\n$routeCode\n");
        }
    }
}
