<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Facades\File;

class RequestGenerator
{
    public function generateRequest($modelName, $command)
    {
        $parser = new FieldParser();
        $requestClass = "{$modelName}Request";
        $fieldsOption = $command->option('fields');

        $fields = is_array($fieldsOption) ? implode(',', $fieldsOption) : $fieldsOption;

        $validationRules = '';
        foreach ($parser->parse($fields) as $field) {

            $field = trim($field);
            $fieldParts = explode(':', $field, 2); // Split only on first colon

            if (count($fieldParts) < 2) {
                $command->error("Invalid field definition: $field");
                continue;
            }

            [$name, $type] = $fieldParts;

            // Handle enum fields specially
            if (str_starts_with($type, 'enum(') && str_ends_with($type, ')')) {
                $enumValues = str_replace(['enum(', ')'], '', $type);
                $cleanValues = implode(',', array_map(
                    fn($val) => trim($val, " '\""),
                    explode(',', $enumValues)
                ));
                $validationRules .= "'$name' => 'required|in:$cleanValues',\n            ";
            } else {
                // Handle regular field types
                switch ($type) {
                    case 'string':
                        $validationRules .= "'$name' => 'required|string|max:255',\n            ";
                        break;
                    case 'text':
                        $validationRules .= "'$name' => 'nullable|string',\n            ";
                        break;
                    case 'integer':
                        $validationRules .= "'$name' => 'required|integer',\n            ";
                        break;
                    case 'boolean':
                        $validationRules .= "'$name' => 'required|boolean',\n            ";
                        break;
                    default:
                        $validationRules .= "'$name' => 'required|string',\n            ";
                }
            }
        }

        $requestTemplate = "<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class $requestClass extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            $validationRules
        ];
    }
}";

        $requestsPath = app_path('Http/Requests');
        File::ensureDirectoryExists($requestsPath);
        File::put("$requestsPath/{$requestClass}.php", $requestTemplate);
        $command->info("Form Request for $modelName created successfully.");
    }
}
