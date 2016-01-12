<?php
namespace Melody\Http;

use Melody\Config;

class Session
{
    public function &__get($name)
    {
        return $_SESSION[Config::Core_get('session_content_varname')][$name];
    }

    public function __set($name, $value)
    {
        return $_SESSION[Config::Core_get('session_content_varname')][$name] = $value;
    }

    public function __isset($name)
    {
        return isset($_SESSION[Config::Core_get('session_content_varname')][$name]);
    }

    public function __unset($name)
    {
        unset($_SESSION[Config::Core_get('session_content_varname')][$name]);
    }
}