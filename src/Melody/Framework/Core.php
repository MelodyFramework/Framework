<?php
namespace Melody\Framework;

use Melody\Framework\Routing\FrontController;
use Melody\Framework\Utils\Tools;

class Core
{
    static public $root = '';

    static function init($dev=false)
    {
        session_start();

        self::$root = dirname(dirname(__DIR__));
        define('ROOT', self::$root);
        define('DEV_ENV', $dev);
        define('PROD_ENV', !$dev);

        if($dev)
        {
            @Tools::rrmdir(ROOT.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'cache');
        }

        //include(__DIR__.DIRECTORY_SEPARATOR.'alias.php');
        Config::Init();


        if(!empty(Config::Core_get('session_user_lifetime')))
        {
            ini_set('session.cookie_lifetime', Config::Core_get('session_client_lifetime'));
            ini_set('session.gc_maxlifetime', Config::Core_get('session_server_lifetime'));
        }
    }

    static function run($uri)
    {
        FrontController::routeTo($uri);
    }
}