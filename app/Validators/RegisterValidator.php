<?php

namespace App\Validators;

class RegisterValidator{

    private $data;
    private $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(): bool
    {
        $this->validateName();
        $this->validateEmail();
        $this->validatePassword();
        return empty($this->errors);
    }

    private function validateName(): void
    {
        $val = $this->data['name'] ?? '';
        if (empty($val) || strlen($val) < 3) {
            $this->errors['name']= 'Le nom doit contenir au moins 3 caractères';
        }
    }
    
    private function validateEmail(): void
    {
        $val = $this->data['email'] ?? '';
        if (empty($val) || !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'veuiilez entrer un email valide';
        }
    }

    private function validatePassword(): void
    {
        $val = $this->data['password'] ?? '';
        if (empty($val) || strlen($val) < 6) {
            $this->errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
        }
        
    }

    public function errors(): array
    {
        return $this->errors;
    }

}