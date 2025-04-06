<?php
namespace App\Services\CrudGenerator;

class FieldParser
{
    public function parse(string $fields): array
    {
        $result = [];
        $buffer = '';
        $depth = 0;

        for ($i = 0; $i < strlen($fields); $i++) {
            $char = $fields[$i];

            if ($char === ',' && $depth === 0) {
                $result[] = trim($buffer);
                $buffer = '';
            } else {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                }
                $buffer .= $char;
            }
        }

        if (trim($buffer) !== '') {
            $result[] = trim($buffer);
        }

        return $result;
    }
}
