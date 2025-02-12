<?php
namespace App\Core;

class DataBaseException extends \Exception {
    protected $message = 'Une erreur de base de données est survenue';
    protected $code = 500;
} 