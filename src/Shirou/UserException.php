<?php
namespace Shirou;

class UserException extends \Exception
{
    public function __construct($code = 0, $args = [], $message = "", \Exception $previous = null)
    {
        parent::__construct(vsprintf($message, $args), $code, $previous);
    }
}
