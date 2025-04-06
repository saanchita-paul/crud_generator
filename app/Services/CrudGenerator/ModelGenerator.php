<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelGenerator
{
    public function generateModel($modelName, $fields, $relations, $command)
    {
        $parser = new FieldParser();
        $fieldsArray = $parser->parse($fields);

        $fillableFields = [];

        foreach ($fieldsArray as $field) {
            $fieldParts = explode(':', trim($field));
            if (count($fieldParts) < 2) {
                $command->error("Invalid field definition: $field");
                continue;
            }
            $fillableFields[] = "'" . $fieldParts[0] . "'";
        }

        $fillableString = implode(', ', $fillableFields);

        $relationsCode = '';
        if (!empty($relations)) {
            foreach (explode(',', $relations) as $relation) {
                $relationParts = explode(':', trim($relation));
                if (count($relationParts) < 2) {
                    $command->error("Invalid relation definition: $relation");
                    continue;
                }

                [$relatedModel, $type] = $relationParts;
                $relatedModelStudly = Str::studly(Str::singular($relatedModel));

                if ($type === 'hasMany') {
                    $relationsCode .= "\n    public function " . Str::camel(Str::plural($relatedModelStudly)) . "() {\n";
                    $relationsCode .= "        return \$this->hasMany($relatedModelStudly::class);\n";
                    $relationsCode .= "    }\n";

                    // Generate the related model automatically
                    $this->generateModel($relatedModelStudly, 'title:string,description:text,status:string', "$modelName:belongsTo", $command);
                } elseif ($type === 'belongsTo') {
                    $relationsCode .= "\n    public function " . Str::camel($relatedModelStudly) . "() {\n";
                    $relationsCode .= "        return \$this->belongsTo($relatedModelStudly::class);\n";
                    $relationsCode .= "    }\n";
                } else {
                    $command->error("Invalid relation type: $type for $relatedModelStudly");
                }
            }
        }

        $modelTemplate = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class $modelName extends Model
{
    use HasFactory, SoftDeletes;

    protected \$fillable = [$fillableString];
    protected \$dates = ['deleted_at'];

    $relationsCode
}";

        File::put(app_path("Models/{$modelName}.php"), $modelTemplate);
        $command->info("Model $modelName created successfully.");
    }
}
