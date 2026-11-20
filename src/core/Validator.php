<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validador de formularios en el servidor.
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            $this->addError($field, "El campo '{$label}' es obligatorio.");
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $val = (string)($this->data[$field] ?? '');
        if ($val !== '' && mb_strlen($val) < $min) {
            $this->addError($field, "El campo '{$label}' debe tener al menos {$min} caracteres.");
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        $val = (string)($this->data[$field] ?? '');
        if ($val !== '' && mb_strlen($val) > $max) {
            $this->addError($field, "El campo '{$label}' no puede superar los {$max} caracteres.");
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $val = (string)($this->data[$field] ?? '');
        if ($val !== '' && !ctype_digit($val)) {
            $this->addError($field, "El campo '{$label}' debe contener únicamente números.");
        }
        return $this;
    }

    public function document(string $field, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '') {
            if (!ctype_digit($val) || strlen($val) !== 10) {
                $this->addError($field, "El campo '{$label}' debe ser un documento de identidad válido de 10 dígitos numéricos.");
            }
        }
        return $this;
    }

    public function custom(string $field, callable $callback, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $val = $this->data[$field] ?? null;
            if (!$callback($val, $this->data)) {
                $this->addError($field, $message);
            }
        }
        return $this;
    }

    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
