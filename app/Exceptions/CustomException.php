<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function report() {
        return false;
    }

    public function render()
    {
        return false;
    }
}
