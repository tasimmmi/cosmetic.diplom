<?php
namespace App\Utils;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required($fields)
    {
        $fields = is_array($fields) ? $fields : func_get_args();
        
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || $this->data[$field] === '') {
                $this->errors[$field][] = "Поле '$field' обязательно для заполнения";
            }
        }
        
        return $this;
    }

    public function email($field)
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "Поле '$field' должно быть корректным email адресом";
            }
        }
        
        return $this;
    }

    public function minLength($field, $length)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = "Поле '$field' должно быть не менее $length символов";
        }
        
        return $this;
    }

    public function maxLength($field, $length)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = "Поле '$field' не должно превышать $length символов";
        }
        
        return $this;
    }

    public function numeric($field)
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = "Поле '$field' должно быть числом";
            }
        }
        
        return $this;
    }

    public function in($field, array $values)
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!in_array($this->data[$field], $values)) {
                $this->errors[$field][] = "Поле '$field' должно быть одним из: " . implode(', ', $values);
            }
        }
        
        return $this;
    }

    public function phone($field)
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $pattern = '/^\+?[1-9]\d{1,14}$/';
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = "Поле '$field' должно быть корректным номером телефона";
            }
        }
        
        return $this;
    }

    public function matches($field, $matchField)
    {
        if (isset($this->data[$field]) && isset($this->data[$matchField])) {
            if ($this->data[$field] !== $this->data[$matchField]) {
                $this->errors[$field][] = "Поле '$field' должно совпадать с полем '$matchField'";
            }
        }
        
        return $this;
    }

    public function isValid()
    {
        return empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getFirstError()
    {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstError = reset($this->errors);
        return reset($firstError);
    }
}