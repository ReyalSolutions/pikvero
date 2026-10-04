<?php
namespace App\Core\Validation;

class Validator {
    private array $errors = [];

    public function validate(array $data, array $rules): bool {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleArray as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $paramStr) = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || trim((string)$value) === '') {
                            $this->addError($field, "The {$field} field is required.");
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "The {$field} must be a valid email address.");
                        }
                        break;

                    case 'min':
                        $min = (int)($params[0] ?? 0);
                        if (!empty($value) && strlen((string)$value) < $min) {
                            $this->addError($field, "The {$field} must be at least {$min} characters.");
                        }
                        break;

                    case 'max':
                        $max = (int)($params[0] ?? 255);
                        if (!empty($value) && strlen((string)$value) > $max) {
                            $this->addError($field, "The {$field} must not exceed {$max} characters.");
                        }
                        break;

                    case 'digits':
                        $exact = (int)($params[0] ?? 11);
                        if (!empty($value) && (!ctype_digit((string)$value) || strlen((string)$value) !== $exact)) {
                            $this->addError($field, "The {$field} must be exactly {$exact} digits.");
                        }
                        break;

                    case 'in':

                        if (!empty($value) && !in_array($value, $params, true)) {
                            $this->addError($field, "The selected {$field} is invalid.");
                        }
                        break;

                    case 'date':
                        if (!empty($value) && !strtotime($value)) {
                            $this->addError($field, "The {$field} is not a valid date.");
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function errors(): array {
        return $this->errors;
    }
}
