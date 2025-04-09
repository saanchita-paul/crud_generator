<?php

namespace Tests\Feature\CrudGenerator;

use App\Services\CrudGenerator\ApiControllerGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiControllerGeneratorTest extends TestCase
{
    protected string $controllersPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Define the path where the controller files should be stored
        $this->controllersPath = app_path('Http/Controllers/Api');

        // Clean up before each test (delete the controller file if it exists)
        $this->modelName = 'Project'; // Set the model name here
        $this->controllerClass = "{$this->modelName}Controller";
        $this->controllerFile = "$this->controllersPath/{$this->controllerClass}.php";

        if (File::exists($this->controllerFile)) {
            File::delete($this->controllerFile);
        }
    }

    /** @test */
    public function it_generates_api_controller_class()
    {
        $commandMock = $this->getMockBuilder(\Illuminate\Console\Command::class)
            ->disableOriginalConstructor()
            ->getMock();
        $commandMock->expects($this->once())
            ->method('info')
            ->with("API Controller for Project created successfully.");

        // Create an instance of ApiControllerGenerator and run the generateApiController method
        $apiControllerGenerator = new ApiControllerGenerator();
        $apiControllerGenerator->generateApiController($this->modelName, $commandMock);

        // Check if the controller file is created
        $this->assertTrue(File::exists($this->controllerFile), 'Controller file was not created.');

        // Get the generated controller file content
        $controllerContent = File::get($this->controllerFile);

        // Assert the controller methods are generated
        $this->assertStringContainsString('public function index()', $controllerContent);
        $this->assertStringContainsString('public function store(ProjectRequest $request)', $controllerContent);
        $this->assertStringContainsString('public function show(Project $project)', $controllerContent);
        $this->assertStringContainsString('public function update(ProjectRequest $request, Project $project)', $controllerContent);
        $this->assertStringContainsString('public function destroy(Project $project)', $controllerContent);
    }

    /** @test */
    public function it_generates_controller_with_correct_variable_name()
    {
        $commandMock = $this->getMockBuilder(\Illuminate\Console\Command::class)
            ->disableOriginalConstructor()
            ->getMock();
        $commandMock->expects($this->once())
            ->method('info')
            ->with("API Controller for Project created successfully.");

        // Create an instance of ApiControllerGenerator and run the generateApiController method
        $apiControllerGenerator = new ApiControllerGenerator();
        $apiControllerGenerator->generateApiController($this->modelName, $commandMock);

        // Get the generated controller file content
        $controllerContent = File::get($this->controllerFile);

        // Assert the controller uses the correct variable name (camelCase)
        $this->assertStringContainsString('$project', $controllerContent);
    }

    protected function tearDown(): void
    {
        // Clean up the generated controller file
//        if (File::exists($this->controllerFile)) {
//            File::delete($this->controllerFile);
//        }

        parent::tearDown();
    }
}
