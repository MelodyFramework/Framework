<?php
namespace Melody\Framework;

use PDO;
use Melody\Framework\Routing\FrontController;

class Model
{
    protected $dtb;

    function __construct()
    {
        $this->dtb = Database::getInstance();
    }

    static function __callStatic($method, $args)
    {
        return self::invoke('\app'.(is_null(FrontController::$apps) || empty(array_filter(FrontController::$apps)) ? '' : '\\'.join('\\', FrontController::$apps)).'\models\\'.$method, $args);
    }

    static function invoke($class, $args=array())
    {
        return new $class($args);
    }
}