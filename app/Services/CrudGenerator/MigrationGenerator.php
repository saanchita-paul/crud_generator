<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrationGenerator
{
    public function generateMigration($modelName, $fields, $command) {

        $parser = new FieldParser();

        $tableNames = [Str::snake(Str::plural($modelName))];

        // Check if relations are provided
        $relationsOption = $command->option('relations');
        if ($relationsOption) {
            $relations = is_array($relationsOption)
                ? $relationsOption
                : explode(',', $relationsOption);

            foreach ($relations as $relation) {
                [$relationName, $type] = array_map('trim', explode(':', $relation));
                if ($relationName) {
                    $tableNames[] = Str::snake(Str::plural($relationName));
                }
            }
        }

        foreach ($tableNames as $tableName) {

            if (Schema::hasTable($tableName)) {
                $command->warn("Table '$tableName' already exists in the database. Skipping migration.");
                continue;
            }
            $migrationName = 'create_' . $tableName . '_table';
            // Check if migration already exists
            $migrationPath = database_path('migrations');
            $existingMigration = collect(File::files($migrationPath))
                ->first(fn($file) => str_contains($file->getFilename(), $migrationName));

            if ($existingMigration) {
                $command->warn("Migration for table '$tableName' already exists: {$existingMigration->getFilename()}");
                continue;
            }

            $timestamp = now()->format('Y_m_d_His');
            $migrationFile = database_path("migrations/{$timestamp}_{$migrationName}.php");

            // Ensure migrations directory exists
            if (!File::exists(database_path('migrations'))) {
                File::makeDirectory(database_path('migrations'), 0755, true);
            }

            $schemaFields = '';
            foreach ($parser->parse($fields) as $field) {
                $field = trim($field);

                $firstColonPos = strpos($field, ':');
                if ($firstColonPos === false) {
                    $command->error("Invalid field definition: $field");
                    continue;
                }

                $name = substr($field, 0, $firstColonPos);
                $typeDefinition = substr($field, $firstColonPos + 1);

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
                        'text' => "\$table->text('$name')->nullable();\n            ",
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
            $command->info("Migration for $tableName created successfully.");
        }
    }
}
