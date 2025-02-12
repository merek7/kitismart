<?php
namespace App\Exceptions;

class AppException extends \Exception
{
   
}

class UserAlreadyExistsException extends AppException
{
    public function __construct($message = "Cet utilisateur existe déjà", $code = 409, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class TokenInvalidOrExpiredException extends AppException
{
    public function __construct($message = "Token invalide ou expiré", $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class DataBaseException extends AppException
{
    public function __construct($message = "Erreur de base de données", $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class TooManyAttemptsException extends AppException
{
    public function __construct($message = "Trop de tentatives", $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}