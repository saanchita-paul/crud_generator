<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CrudGeneratorTest extends TestCase
{
    protected string $modelName = 'TestItem';

    protected function tearDown(): void
    {
        // Clean up created files
        $files = [
            app_path("Models/{$this->modelName}.php"),
            app_path("Http/Controllers/{$this->modelName}Controller.php"),
            app_path("Http/Controllers/Api/{$this->modelName}Controller.php"),
            app_path("Http/Requests/{$this->modelName}Request.php"),
            resource_path("views/test_items/index.blade.php"),
            base_path("routes/web.php"),
            base_path("routes/api.php"),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Remove migration file
        $migrationFiles = File::files(database_path('migrations'));
        foreach ($migrationFiles as $file) {
            if (str_contains($file->getFilename(), 'create_test_items_table')) {
                File::delete($file->getPathname());
            }
        }

        parent::tearDown();
    }

    public function test_crud_generator_creates_files_successfully()
    {
        Artisan::call('make:crud', [
            'model' => $this->modelName,
            '--fields' => 'name:string, description:text, status:enum(open,closed)',
        ]);

        $this->assertTrue(File::exists(app_path("Models/{$this->modelName}.php")));
        $this->assertTrue(File::exists(app_path("Http/Controllers/{$this->modelName}Controller.php")));
        $this->assertTrue(File::exists(app_path("Http/Controllers/Api/{$this->modelName}Controller.php")));
        $this->assertTrue(File::exists(app_path("Http/Requests/{$this->modelName}Request.php")));

        $this->assertDirectoryExists(resource_path('views/test_items'));
        $this->assertFileExists(resource_path('views/layouts/layout.blade.php'));

        $migrationFiles = File::files(database_path('migrations'));
        $migrationExists = collect($migrationFiles)
            ->contains(fn($file) => str_contains($file->getFilename(), 'create_test_items_table'));
        $this->assertTrue($migrationExists);
    }
}
