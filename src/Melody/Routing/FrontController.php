<?php
namespace Melody\Routing;

use Melody\Config;
use Melody\Http\Request;
use Melody\Http\Response;
use Melody\Utils\Tools;

class FrontController
{
    static public $uri 				= null;
    static public $apps				= null;
    static public $class 			= null;
    static public $method 			= null;
    static public $request_method 	= null;


    static function routeTo($uri)
    {
        self::$uri 			= $uri;

        $data 				= self::parseURI($uri);
        $data['apps'] 		= self::applyHostConfiguration($data);
        $http_method 		= strtolower($_SERVER['REQUEST_METHOD']);

        $default_method 		= 'Index';
        $default_controller		= 'Home';

        Config::loadFor($data['apps']);

        // cas possible | par ordre de priorité
        // ======================
        //  /
        //  /app
        //	/method
        //	/my-method
        //	/controller
        //	/controller-method
        //	/app/
        //	/app/method
        //	/app/my-method
        //	/app/controller
        //	/app/controller-method
        //	/app/app
        //	/app/app/

        // Si /(app/)?/method
        if(empty($data['controller']) && empty($data['method']))
        {
            if(false !== $route = self::isRoutable($data['apps'], $default_controller, $default_method, $http_method))
            {
                list($apps, $class, $method) = $route;
            }
            else
            {
                self::throwError(404) ;
            }
        }
        else if(empty($data['method']))
        {
            if(false !== $route = self::isRoutable(array_merge(array_filter($data['apps']), array($data['controller'])), $default_controller, $default_method, $http_method))
            {
                list($apps, $class, $method) = $route;
                self::redirectController($class);
            }
            else if(false !== $route = self::isRoutable($data['apps'], $default_controller, $data['controller'], $http_method))
            {
                list($apps, $class, $method) = $route;
            }
            else if(false !== $route = self::isRoutable($data['apps'], $data['controller'], $default_method, $http_method))
            {
                list($apps, $class, $method) = $route;
            }
            else
            {
                self::throwError(404);
            }
        }
        else
        {
            if(false !== $route = self::isRoutable($data['apps'], $default_controller, $data['controller'].'-'.$data['method'], $http_method))
            {
                list($apps, $class, $method) = $route;
            }
            else if(false !== $route =  self::isRoutable($data['apps'], $data['controller'], $data['method'], $http_method))
            {
                list($apps, $class, $method) = $route;
            }
            else
            {
                self::throwError(404);
            }
        }

        /*
        if(empty($method) || empty($method))
        {
            self::throwError(404);
        }
        */


        $req = Request::getInstance($data['args']);
        $res = new Response();

        $page = new $class();

        self::$apps 			= $apps;
        self::$class 			= $class;
        self::$method 			= $method;
        self::$request_method 	= strtolower($_SERVER['REQUEST_METHOD']);

        $page->{'__melody_invoke'}($method, array($req, $res));

    }

    static function parseURI($uri)
    {
        $data = [];

        if(preg_match('/^((?:[a-z0-9]+\/)*)(?:([a-z0-9]+)(?:-([a-z0-9-]+)(?:\/(.*))?)?)?$/', $uri, $matches))
        {

            $data = array(
                'URI' 			=> $matches[0],
                'apps' 			=> array_filter(isset($matches[1]) ? explode('/', $matches[1]) : array()),
                'controller' 	=> (isset($matches[2]) ? $matches[2] : ''),
                'method' 		=> (isset($matches[3]) ? $matches[3] : ''),
                'args' 			=> array_filter(explode('/', (isset($matches[4]) ? $matches[4] : ''))),
            );
        }
        else
        {
            self::throwError(403, 'URN not matching');
        }

        return $data;
    }

    static function applyHostConfiguration($data)
    {
        $apps = $data['apps'];

        // recupération du chemin "complet"
        $de = explode('.', $_SERVER['HTTP_HOST']);
        $host = implode('.', $de);

        $configFromDomain = Config::Host_getConfigFromDomain($_SERVER['HTTP_HOST']);
        $apps = array_merge($configFromDomain, $apps);
        $configFromPath = Config::Host_getConfigFromPath(array_filter($apps));

        if(!empty($configFromPath))
        {
            if($configFromPath['domain'] != $host)
            {
                switch($configFromPath['action'])
                {
                    case 'forbid':
                        self::throwError(403);
                        break;
                    case 'redirect':
                        $host_apps = Config::Host_getConfigFromDomain($configFromPath['domain']);
                        self::redirectURL(strtolower(explode('/', 	$_SERVER['SERVER_PROTOCOL'])[0]).'://'
                            .$configFromPath['domain']
                            .'/'.Tools::urlfor(array_slice(array_filter($apps), count($host_apps)),
                                $data['controller'] == 'home' ? '': $data['controller'],
                                $data['method'] == 'index' ? '' : $data['method'],
                                $data['args'],
                                false), true, true);
                        break;
                    case 'none':
                    default:
                        break;
                }
            }
        }

        return $apps;
    }

    static function isRoutable($apps, $controller, $method, $http_method)
    {
        //header('Content-Type: text/plain');
        if(!empty($apps)) $apps[] = ''; // petit trick pour avoir un antislash final

        $class = 'www\\'.join('\\', $apps).'controllers\\'.ucfirst($controller);
        $method = str_replace('-', '', mb_convert_case($method, MB_CASE_TITLE, "UTF-8")).'Action';

        if(class_exists($class))
        {
            if(Tools::method_exists($class, $http_method.$method))
            {
                return array(array_filter($apps), $class, $http_method.$method);
            }
            else if(Tools::method_exists($class, 'any'.$method))
            {
                return array(array_filter($apps), $class, 'any'.$method);
            }
            else
            {
                return false;
            }
        }

        return false;
    }

    static function throwError($code, $msg='')
    {
        if(empty(Config::getAll()))
        {
            if(!is_null(self::$apps))
            {
                Config::loadFor(self::$apps);
            }
            else
            {
                Config::loadFor(array());
            }
        }

        $action = !is_null(Config::get('access_error_'.$code.'_action')) ?  Config::get('access_error_'.$code.'_action') : Config::get('access_error_default_action');
        $method_array = !is_null(Config::get('access_error_'.$code.'_controller')) ?  Config::get('access_error_'.$code.'_controller') : Config::get('access_error_default_controller');

        if(is_null($action) || is_null($method_array))
        {
            echo($code.' : '.$msg);
            exit();
        }
        else
        {
            list($controller, $method) = $method_array;

            switch($action)
            {
                case 'redirect':
                    self::redirectController($controller, $method, Tools::base64url_encode(serialize(array(FrontController::$uri, $code, $msg))));
                    break;
                case 'include':
                default:
                    echo $controller::invoke($method, Request::getInstance(), $code, $msg);
                    break;
            }
        }
        exit();
    }

    static function redirect($apps = array(), $controller='', $method='', $args=array())
    {
        self::redirectURL(Tools::urlfor($apps, $controller, $method, $args), true, true);
    }

    static function redirectController($class, $method='', $args=array())
    {
        $class = (explode('\\', $class));
        $apps = array_slice($class, 1, -2);
        $controller = strtolower($class[count($class)-1]);

        self::redirect($apps, $controller, $method, $args);
    }

    static function redirectURL($url, $absolute=false, $external=false)
    {
        if(!$external)
        {
            ($absolute) ? header('Location: /'.$url) : header('Location: '.Config::get('app_base_url').$url);
        }
        else
        {
            header('Location: '.$url);
        }
        exit();
    }
}