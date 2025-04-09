<?php

namespace Tests\Feature\CrudGenerator;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    protected string $modelPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up before test
        $this->modelPath = app_path('Models/Project.php');
        if (File::exists($this->modelPath)) {
            File::delete($this->modelPath);
        }

        // Also remove the related Task model if exists
        $taskModelPath = app_path('Models/Task.php');
        if (File::exists($taskModelPath)) {
            File::delete($taskModelPath);
        }
    }

    /** @test */
    public function it_generates_model_file_with_fillable_fields_and_relations()
    {
        // Run the command
        $this->artisan('make:crud Project --fields="name:string,description:text,status:enum(open,closed)" --relations="tasks:hasMany"')
            ->expectsOutput('Generating CRUD for model: Project...')
            ->expectsOutput('Model Task created successfully.') // Task model comes first
            ->expectsOutput('Model Project created successfully.') // Project model comes next
            ->assertExitCode(0);


        // Check if Project.php model was created
        $this->assertTrue(File::exists($this->modelPath));

        // Check model content
        $content = File::get($this->modelPath);

        // Assert fillable fields
        $this->assertStringContainsString("protected \$fillable = ['name', 'description', 'status'];", $content);

        // Assert relation method exists
        $this->assertStringContainsString('public function tasks()', $content);
        $this->assertStringContainsString('return $this->hasMany(Task::class);', $content);
    }

    protected function tearDown(): void
    {
        // Clean up generated files
//        if (File::exists($this->modelPath)) {
//            File::delete($this->modelPath);
//        }

//        $taskModelPath = app_path('Models/Task.php');
//        if (File::exists($taskModelPath)) {
//            File::delete($taskModelPath);
//        }

        parent::tearDown();
    }
}
