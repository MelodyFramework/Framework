<?php
namespace Melody\Framework\Http;

use Melody\Framework\Config;
use Melody\Framework\Routing\FrontController;
use Melody\Framework\Utils\Tools;

class Request
{
    private static $instance 	= null;

    private $session 			= null;
    private $attributes 		= array();
    private $args 				= array();
    public $protocol 			= '';
    public $host 				= '';
    public $url 				= '';
    public $urn 				= '';
    public $uri					= '';
    public $method				= '';

    private function __construct()
    {}

    public static function &getInstance($args=array())
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Request();
            self::$instance->args = $args;

            if(!isset($_SESSION[Config::Core_get('session_flash_varname')]))
            {
                $_SESSION[Config::Core_get('session_flash_varname')] = array('new' => array(), 'old' => array());
            }
            $_SESSION[Config::Core_get('session_flash_varname')]['old'] = $_SESSION[Config::Core_get('session_flash_varname')]['new'];
            $_SESSION[Config::Core_get('session_flash_varname')]['new'] = array();



            self::$instance->protocol 	= strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]);
            self::$instance->host 		= $_SERVER['HTTP_HOST'];
            self::$instance->urn		= $_SERVER['REQUEST_URI'];
            self::$instance->uri 		= self::$instance->protocol.'://'.self::$instance->host.self::$instance->urn;
            self::$instance->url 		= self::$instance->protocol.'://'.self::$instance->host;
            self::$instance->method		= strtolower($_SERVER['REQUEST_METHOD']);;

            if(!isset($_SESSION[Config::Core_get('session_history_varname')]))
            {
                $_SESSION[Config::Core_get('session_history_varname')] = array_fill(0, 10 , self::$instance->url);
            }

            if(self::$instance->method = 'post')
            {
                foreach ($_FILES as $key => &$value)
                {
                    if(is_array($_FILES[$key]['name']))
                    {
                        $value = Tools::diverse_array($value);
                    }

                    $value = (object) $value;
                }
            }

            self::$instance->addReferer(self::$instance->uri);
        }

        return self::$instance;
    }

    public function getSession()
    {
        if(is_null(self::getInstance()->session))
            return self::getInstance()->session = new Session();
        else
            return self::getInstance()->session;
    }

    public function getArg($name)
    {
        return (isset(self::getInstance()->args[$name]) ? self::getInstance()->args[$name] : null);
    }

    public function getArgs()
    {
        return self::getInstance()->args;
    }

    public function getParameter($name)
    {
        return self::getInstance()->getVar($_REQUEST, $name);
    }

    public function getParameters()
    {
        return self::getInstance()->getVars($_REQUEST);
    }

    public function getPostParameter($name)
    {
        return self::getInstance()->getVar($_POST, $name);
    }

    public function getPostParameters()
    {
        return self::getInstance()->getVars($_POST);
    }

    public function getGetParameter($name)
    {
        return self::getInstance()->getVar($_GET, $name);
    }

    public function getGetParameters()
    {
        return self::getInstance()->getVars($_GET);
    }

    public function getCookieParameter($name)
    {
        return self::getInstance()->getVar($_COOKIE, $name);
    }

    public function getCookieParameters()
    {
        return self::getInstance()->getVars($_COOKIE);
    }

    public function getFile($name)
    {
        return self::getInstance()->getVar($_FILES, $name);
    }

    public function getFiles()
    {
        return self::getInstance()->getVars($_FILES);
    }

    private function getVar($varname, $key)
    {
        return (isset($varname[$key]) ? $varname[$key] : null);
    }

    private function getVars($varname)
    {
        return $varname;
    }

    public function setFlash($type, $value, $name=null)
    {
        if(!is_null($name) && !empty($name))
            return $_SESSION[Config::Core_get('session_flash_varname')]['new'][$name] = array('content' => $value, 'type' => $type);
        else
            return $_SESSION[Config::Core_get('session_flash_varname')]['new'][] = array('content' => $value, 'type' => $type);
    }

    public function getFlash($name)
    {
        return $_SESSION[Config::Core_get('session_flash_varname')]['old'][$name];
    }

    public function getAllFlash()
    {
        return $_SESSION[Config::Core_get('session_flash_varname')]['old'];
    }

    public function getFlashByType($type)
    {
        return array_filter($_SESSION[Config::Core_get('session_flash_varname')]['old'], function($e) use ($type){ return $e['type'] === $type; });
    }

    public function setAttribute($name, $value)
    {
        return self::getInstance()->attributes[$name] = $value;
    }

    public function &getAttribute($name)
    {
        return self::getInstance()->attributes[$name];
    }

    private function addReferer($uri)
    {
        array_pop($_SESSION[Config::Core_get('session_history_varname')]);
        array_unshift($_SESSION[Config::Core_get('session_history_varname')], $uri);
    }

    public function getReferer($num=0)
    {
        return $_SESSION[Config::Core_get('session_history_varname')][$num];
    }

    public function redirect($apps = array(), $controller='', $method='', $args=array())
    {
        FrontController::redirect($apps, $controller, $method, $args);
    }

    public function redirectController($class, $method, $args=array())
    {
        FrontController::redirectController($class, $method, $args);
    }

    public function redirectURL($url, $absolute=false, $external=false)
    {
        FrontController::redirectURL($url, $absolute, $external);
    }

    public function previous($num=1)
    {
        $this->redirectURL($this->getReferer($num), true, true);
    }

    public function refresh()
    {
        $this->redirectURL($this->uri, true, true);
    }

    public function throwError($code, $msg='')
    {
        FrontController::throwError($code, $msg);
    }
}