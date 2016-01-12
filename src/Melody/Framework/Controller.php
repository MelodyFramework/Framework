<?php
namespace Melody\Framework;

use Melody\Framework\Http\Request;
use Melody\Framework\Http\Response;
use Melody\Framework\Routing\FrontController;
use Melody\Framework\Utils\Tools;

class Controller
{
    private $method_access = array();

    public function __construct()
    {
        if(!empty(Config::get('api_is_on')))
        {
            if(Config::get('api_is_public') && Config::get('api_has_jsonp'))
            {
                header('Access-Control-Allow-Origin: *');

                if($_SERVER['REQUEST_METHOD'] == "OPTIONS")
                {

                    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
                    header('Access-Control-Max-Age: 1728000'); // 20 jours
                    header("Content-Length: 0");
                    header("Content-Type: text/plain");

                    exit();
                }
            }
        }

        if(false === $cache = Cache::get('access', get_class($this)))
        {
            if(method_exists($this, '__melody_access'))
                $this->__melody_access();

            if(method_exists($this, '__melody_init'))
                $this->__melody_init(Request::getInstance());


            Cache::create('access', get_class($this), $this->method_access, 'on_demand');
        }
        else
        {
            $this->method_access = $cache;
        }
    }

    public function setDefaultAccessRole($role)
    {
        foreach(get_class_methods($this) as $method)
        {
            $this->method_access[$method] = $role;
        }
    }

    public function setAccessRoleException($method, $role)
    {
        $this->method_access[$method] = $role;
    }

    public function __melody_invoke($method, $args)
    {
        $role 			= (isset($_SESSION[Config::Core_get('session_content_varname')][Config::Core_get('access_role_session_key')]) ? $_SESSION[Config::Core_get('session_content_varname')][Config::Core_get('access_role_session_key')] : Config::Core_get('access_role_if_missing'));
        $access_role 	= (isset($this->method_access[$method]) ? $this->method_access[$method] : (!is_null(Config::get('access_role_app')) ? Config::get('access_role_app') : Config::Core_get('access_role_default')));

        if($role >= $access_role)
        {
            $class = get_class($this);
            if(false === $cache = Cache::get('controller', $class.'_'.$method))
            {
                ob_start();
                $__melody_response = call_user_func_array(array($this, $method), $args);
                $buffer = ob_get_clean();

                echo self::execute_view($__melody_response, $buffer, $class, $args[0]);

            }
            else
            {
                echo $cache;
            }
        }
        else
        {
            FrontController::throwError(403);
        }
    }

    public static function invoke($method, $req, ...$args)
    {
        $class = get_called_class();

        if(false === $cache = Cache::get('controller', $class.'_'.$method))
        {
            ob_start();
            $c = new $class();

            array_unshift($args, new Response());
            array_unshift($args, $req);

            $__melody_response = call_user_func_array(array($c, $method), $args);
            $buffer = ob_get_clean();

            return self::execute_view($__melody_response, $buffer, $class, $req);
        }
        else
        {
            return $cache;
        }
    }

    public static function __melody_static_method_exists($method)
    {
        if(!Config::$loaded)
            Config::loadFor(array('www'));

        $class = get_called_class();
        $intance = new $class;
        if(!method_exists(get_called_class(), '__melody_method_exists'))
            return false;
        else
            return  $intance->__melody_method_exists($method);
    }

    private static function execute_view($__melody_response = null, $buffer, $class, $req)
    {
        $__melody_response = (is_null($__melody_response) ? new Response() : $__melody_response);

        // permet d'accéder aux différentes variables directement dans la view
        $vars = $__melody_response->viewvars;

        if(!empty($__melody_response->viewpath))
        {
            if($__melody_response->viewpath[1])
            {
                list($apps, $file) = $__melody_response->viewpath[0];
                $path = Tools::pathfor($apps, 'views'.DIRECTORY_SEPARATOR.$file, '.php');

            }
            else
            {
                $path = Tools::pathfor(array_filter(!is_null(FrontController::$apps) ? FrontController::$apps : array()), 'views'.DIRECTORY_SEPARATOR.$__melody_response->viewpath[0], '.php');
            }

            ob_start();
            include($path);
            $output = ob_get_clean();


            if($__melody_response->cache)
            {
                Cache::create('controller', $class.'_'.FrontController::$method, $output, $__melody_response->cache_mode, $__melody_response->expiration);
            }

            return  $output;

        }

        return $buffer;
    }

    public static function getView($view, $vars=array(), $return=false)
    {
        $apps = explode('\\', get_called_class());
        $apps = array_splice($apps, 1, -2);
        $path = Tools::pathfor($apps, 'views'.DIRECTORY_SEPARATOR.str_replace('..', '.', $view).'.php');

        ob_start();
        include($path);
        $output = ob_get_clean();

        if($return)
            return $output;

        echo $output;
    }
}