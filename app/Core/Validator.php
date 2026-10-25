<?php

declare(strict_types=1);

namespace App\Core;

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
        $value = $this->data[$field] ?? null;

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            $this->errors[$field] = "El campo {$label} es obligatorio.";
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo {$label} debe ser un correo válido.";
        }

        return $this;
    }

    public function max(string $field, int $max, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "El campo {$label} no debe superar los {$max} caracteres.";
        }

        return $this;
    }

    public function min(string $field, int $min, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = "El campo {$label} debe tener al menos {$min} caracteres.";
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            return $this;
        }

        $allowedValues = array_map(static fn ($item): string => (string) $item, $allowed);

        if (!in_array((string) $value, $allowedValues, true)) {
            $this->errors[$field] = "El valor seleccionado en {$label} no es válido.";
        }

        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->data[$field] ?? null;

        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "El campo {$label} debe ser numérico.";
        }

        return $this;
    }

    public function minValue(string $field, float $min, string $label): self
    {
        $value = $this->data[$field] ?? null;

        if ($value !== null && $value !== '' && is_numeric($value) && (float) $value < $min) {
            $this->errors[$field] = "El campo {$label} debe ser mayor o igual a {$min}.";
        }

        return $this;
    }

    public function date(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if ($value !== '') {
            $date = \DateTime::createFromFormat('Y-m-d', $value);

            if ($date === false || $date->format('Y-m-d') !== $value) {
                $this->errors[$field] = "El campo {$label} debe ser una fecha válida (YYYY-MM-DD).";
            }
        }

        return $this;
    }

    public function dateNotFuture(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if ($value !== '') {
            $date = \DateTime::createFromFormat('Y-m-d', $value);

            if ($date !== false && $date->format('Y-m-d') === $value && $date > new \DateTime('today')) {
                $this->errors[$field] = "El campo {$label} no puede ser una fecha futura.";
            }
        }

        return $this;
    }

    public function time(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if ($value !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value)) {
            $this->errors[$field] = "El campo {$label} debe ser una hora válida (HH:MM).";
        }

        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] = $message;

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
