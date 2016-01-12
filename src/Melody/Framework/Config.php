<?php
namespace Melody\Framework;

use Melody\Framework\Routing\FrontController;
use Melody\Framework\Utils\Tools;

class Config
{
    static public	$loaded			= false;
    static private 	$core_config 	= array();
    static private 	$hosts		 	= array();
    static private 	$app_config 	= array();
    static private 	$instance 		= null;

    static function Init()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Config();
        }
    }

    private function __construct()
    {
        $this->loadCoreVars();
        $this->loadHostvars();
    }

    function loadCoreVars()
    {
    if(empty(self::$core_config))
    {
    if(false === $cache = Cache::get('coreconfig', 'core'))
    {
    $vars = Tools::flatten(json_decode(file_get_contents(__DIR__.'/config/core.json'), true));

    if(empty($vars))
    {
    FrontController::throwError(500, 'Core config syntax error');
    }
    else
        {
            self::$core_config = $vars;
            Cache::create('coreconfig', 'core', $vars, 'on_demand');
        }
    }
    else
        {
            self::$core_config = $cache;
        }
    }
    }

    function loadHostvars()
    {
        if(empty(self::$hosts))
        {
            if(false === $cache = Cache::get('coreconfig/host', 'hostconfig'))
            {
                $vars = json_decode(file_get_contents(__DIR__.'/config/hosts.json'), true);
                $host = implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2));

                if(is_null($vars))
                {
                    FrontController::throwError(500, 'Hosts config syntax error');
                }
                else
                {
                    self::$hosts = array('domain' => array(), 'path'=>array(), 'base_config' => array('domain' => array(), 'path' => array()));
                    self::$hosts['domain']['_'.$host] 			= array();
                    self::$hosts['path']['/'] 				= array('action' => 'redirect', 'recursive' => true, 'domain' => $host, 'full_domain' => false);
                    self::$hosts['base_config']				=  self::$hosts;

                    foreach($vars as $key => $var)
                    {
                        $full_domain = (isset($var['full_domain']) && $var['full_domain']);
                        self::$hosts['domain'][$full_domain ? '_'.$key : ('_'.$key.'.'.$host)] 	= array_filter(explode('/', substr($var['path'], 1)));
                        self::$hosts['path'][$var['path']] 	= array('action' => $var['action'], 'recursive' => $var['recursive'], 'domain' => $full_domain ? $key : ($key.'.'.$host), 'full_domain' => $full_domain);
                    }
                    Cache::create('coreconfig/host', 'hostconfig', self::$hosts, 'on_demand');
                }
            }
            else
            {
                self::$hosts = $cache;
            }
        }
    }

    static function loadFor($apps)
    {
        if(empty(self::$app_config))
        {
            self::$loaded = true;

            if(false === $cache = Cache::get('appconfig', join(DIRECTORY_SEPARATOR, array_filter($apps))))
            {
                $appspath = array('');
                $buffer = '';
                foreach ($apps as $app)
                {
                    if(!empty($app))
                    {
                        $buffer .= DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$app;
                        $appspath[] = $buffer;
                    }
                }

                foreach ($appspath as $currentpath)
                {
                    $app_config = array();
                    $path = ROOT.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'www'.$currentpath.DIRECTORY_SEPARATOR.'config';

                    $configfiles = is_dir($path) ? Tools::file_get_contents_loop($path) : array();
                    foreach ($configfiles as $name => $content)
                    {
                        $app_config[$name] = json_decode($content, true);
                    }
                    $app_config = Tools::flatten($app_config);
                    self::$app_config = array_merge(self::$app_config, $app_config);
                }

                Cache::create('appconfig', join(DIRECTORY_SEPARATOR, array_filter($apps)), self::$app_config, 'on_demand');
            }
            else
            {
                self::$app_config = $cache;
            }
        }
    }

    static function Core_get($key)
    {
        return self::get($key, 'core');
    }

    static function Core_getAll()
    {
        return self::getAll('core');
    }

    static function Core_listMatching($key)
    {
        return self::listMatching($key, 'core');
    }

    static function get($key, $context='app')
    {
        return isset(self::${$context.'_config'}[$key]) ? self::${$context.'_config'}[$key] : null;
    }

    static function getAll($context='app')
    {
        return self::${$context.'_config'};
    }

    static function listMatching($key, $context='app')
    {
        return array_filter(self::${$context.'_config'}, function($keys) use ($key)
        {
            return preg_match('/^'.$key.'.*$/', $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    static function Host_getConfigFromPath($path)
    {
        $cachename = 'action_'.implode('_',  array_filter($path));
        if(false === $cache = Cache::get('coreconfig/host', $cachename))
        {
            $bufferedpath = '';
            $bufferedconfig = array();
            $isLast = true;


            $path = array_filter($path);

            $config = array();

            if(isset( self::$hosts['base_config']['path']['/']))
            {
                $bufferedconfig = self::$hosts['base_config']['path']['/'];
                if($bufferedconfig['recursive'])
                    $config = $bufferedconfig;

            }

            if(isset(self::$hosts['path']['/']))
                $bufferedconfig = self::$hosts['path']['/'];
            if($bufferedconfig['recursive'])
                $config = $bufferedconfig;

            while($key = array_shift($path))
            {
                $bufferedpath .= '/'.$key;

                if(isset(self::$hosts['path'][$bufferedpath]))
                {
                    $isLast = true;
                    $bufferedconfig = self::$hosts['path'][$bufferedpath];

                    if($bufferedconfig['recursive'])
                        $config = $bufferedconfig;
                }
                else
                {
                    $isLast = false;
                }
            }

            if($isLast)
            {
                Cache::create('coreconfig/host', $cachename, $bufferedconfig, 'on_demand');
                return $bufferedconfig;
            }
            else
            {
                Cache::create('coreconfig/host', $cachename, $config, 'on_demand');
                return $config;
            }
        }
        else
        {
            return $cache;
        }
    }

    static function Host_getConfigFromDomain($domain='')
    {
        return (isset(self::$hosts['domain']['_'.$domain]) ? self::$hosts['domain']['_'.$domain] : array());
    }

}