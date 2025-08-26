<?php
namespace BVOTE\Core;

/**
 * BVOTE Validator Class
 * Validate input data với rules linh hoạt
 */
class Validator {
    private $errors = [];
    private $data = [];

    /**
     * Validate data with rules
     */
    public function validate(array $data, array $rules): bool {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply single rule
     */
    private function applyRule(string $field, string $rule): void {
        $value = $this->data[$field] ?? null;

        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        switch ($ruleName) {
            case 'required':
                if (!$this->isRequired($value)) {
                    $this->addError($field, "The {$field} field is required");
                }
                break;

            case 'email':
                if ($value && !$this->isEmail($value)) {
                    $this->addError($field, "The {$field} must be a valid email address");
                }
                break;

            case 'min':
                if ($value && !$this->isMin($value, $parameter)) {
                    $this->addError($field, "The {$field} must be at least {$parameter} characters");
                }
                break;

            case 'max':
                if ($value && !$this->isMax($value, $parameter)) {
                    $this->addError($field, "The {$field} may not be greater than {$parameter} characters");
                }
                break;

            case 'integer':
                if ($value && !$this->isInteger($value)) {
                    $this->addError($field, "The {$field} must be an integer");
                }
                break;

            case 'numeric':
                if ($value && !$this->isNumeric($value)) {
                    $this->addError($field, "The {$field} must be a number");
                }
                break;

            case 'alpha':
                if ($value && !$this->isAlpha($value)) {
                    $this->addError($field, "The {$field} may only contain letters");
                }
                break;

            case 'alphanumeric':
                if ($value && !$this->isAlphanumeric($value)) {
                    $this->addError($field, "The {$field} may only contain letters and numbers");
                }
                break;

            case 'url':
                if ($value && !$this->isUrl($value)) {
                    $this->addError($field, "The {$field} must be a valid URL");
                }
                break;

            case 'date':
                if ($value && !$this->isDate($value)) {
                    $this->addError($field, "The {$field} must be a valid date");
                }
                break;

            case 'in':
                if ($value && !$this->isIn($value, $parameter)) {
                    $this->addError($field, "The selected {$field} is invalid");
                }
                break;

            case 'unique':
                if ($value && !$this->isUnique($field, $value, $parameter)) {
                    $this->addError($field, "The {$field} has already been taken");
                }
                break;

            case 'exists':
                if ($value && !$this->exists($field, $value, $parameter)) {
                    $this->addError($field, "The selected {$field} is invalid");
                }
                break;

            case 'confirmed':
                if (!$this->isConfirmed($field)) {
                    $this->addError($field, "The {$field} confirmation does not match");
                }
                break;

            case 'different':
                if ($value && !$this->isDifferent($field, $parameter)) {
                    $this->addError($field, "The {$field} and {$parameter} must be different");
                }
                break;

            case 'same':
                if ($value && !$this->isSame($field, $parameter)) {
                    $this->addError($field, "The {$field} and {$parameter} must match");
                }
                break;
        }
    }

    /**
     * Validation rules
     */
    private function isRequired($value): bool {
        return $value !== null && $value !== '' && $value !== [];
    }

    private function isEmail($value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isMin($value, $min): bool {
        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    private function isMax($value, $max): bool {
        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    private function isInteger($value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function isNumeric($value): bool {
        return is_numeric($value);
    }

    private function isAlpha($value): bool {
        return preg_match('/^[a-zA-Z]+$/', $value);
    }

    private function isAlphanumeric($value): bool {
        return preg_match('/^[a-zA-Z0-9]+$/', $value);
    }

    private function isUrl($value): bool {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function isDate($value): bool {
        $date = date_parse($value);
        return $date['error_count'] === 0 && $date['warning_count'] === 0;
    }

    private function isIn($value, $allowed): bool {
        $allowedValues = explode(',', $allowed);
        return in_array($value, $allowedValues);
    }

    private function isUnique($field, $value, $table): bool {
        try {
            $db = new Database(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_DATABASE'] ?? 'bvote_system',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                (int)($_ENV['DB_PORT'] ?? 3306),
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );

            $existing = $db->selectOne($table, "{$field} = :value", ['value' => $value]);
            return $existing === null;

        } catch (\Exception $e) {
            Logger::error('Error checking uniqueness: ' . $e->getMessage());
            return false;
        }
    }

    private function exists($field, $value, $table): bool {
        try {
            $db = new Database(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_DATABASE'] ?? 'bvote_system',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                (int)($_ENV['DB_PORT'] ?? 3306),
                $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            );

            $existing = $db->selectOne($table, "{$field} = :value", ['value' => $value]);
            return $existing !== null;

        } catch (\Exception $e) {
            Logger::error('Error checking existence: ' . $e->getMessage());
            return false;
        }
    }

    private function isConfirmed($field): bool {
        $confirmationField = $field . '_confirmation';
        return isset($this->data[$confirmationField]) &&
               $this->data[$field] === $this->data[$confirmationField];
    }

    private function isDifferent($field, $otherField): bool {
        return isset($this->data[$otherField]) &&
               $this->data[$field] !== $this->data[$otherField];
    }

    private function isSame($field, $otherField): bool {
        return isset($this->data[$otherField]) &&
               $this->data[$field] === $this->data[$otherField];
    }

    /**
     * Add validation error
     */
    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors
     */
    public function getErrors(): array {
        $allErrors = [];
        foreach ($this->errors as $field => $errors) {
            $allErrors = array_merge($allErrors, $errors);
        }
        return $allErrors;
    }

    /**
     * Get errors for specific field
     */
    public function getFieldErrors(string $field): array {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if field has errors
     */
    public function hasErrors(string $field = null): bool {
        if ($field === null) {
            return !empty($this->errors);
        }

        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Get first error for field
     */
    public function getFirstError(string $field): ?string {
        $errors = $this->getFieldErrors($field);
        return $errors[0] ?? null;
    }

    /**
     * Get all errors as string
     */
    public function getErrorsAsString(): string {
        $allErrors = $this->getErrors();
        return implode(', ', $allErrors);
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void {
        $this->errors = [];
    }

    /**
     * Validate single field
     */
    public function validateField(string $field, $value, string $rules): bool {
        $data = [$field => $value];
        $fieldRules = [$field => $rules];

        return $this->validate($data, $fieldRules);
    }

    /**
     * Custom validation rule
     */
    public function addCustomRule(string $name, callable $callback): void {
        // This would be implemented in a more advanced version
        // For now, we'll use the built-in rules
    }

    /**
     * Sanitize input data
     */
    public function sanitize(array $data): array {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize string
     */
    private function sanitizeString(string $value): string {
        // Remove HTML tags
        $value = strip_tags($value);

        // Convert special characters
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        // Trim whitespace
        $value = trim($value);

        return $value;
    }
}
