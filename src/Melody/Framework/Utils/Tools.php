<?php
namespace Melody\Framework\Utils;

use Melody\Framework\Config;

class Tools
{
    static function flatten($array, $prefix = '')
    {
        if(is_array($array))
        {
            $result = array();

            foreach ($array as $key => $value)
            {
                $new_key = $prefix . (empty($prefix) ? '' : '_') . $key;

                if (is_array($value) && Tools::is_assoc($value))
                {
                    $result = array_merge($result, self::flatten($value, $new_key));
                }
                else
                {
                    $result[$new_key] = $value;
                }
            }

            return $result;
        }
        else
        {
            return $array;
        }
    }

    static function file_get_contents_loop($path, $files=array(), $sorted=false)
    {
        $buffer = array();
        $dir = opendir($path);
        while($file = readdir($dir))
        {
            if($file != '.' && $file != '..')
            {
                if(empty($files))
                {
                    $buffer[basename($file, '.json')] = file_get_contents($path.DIRECTORY_SEPARATOR.$file);
                }
                else if(!is_array($files))
                {
                    if($file == $files)
                    {
                        $buffer[basename($file, '.json')] = file_get_contents($path.DIRECTORY_SEPARATOR.$file);
                    }
                }
                else
                {
                    if(in_array($file, $files))
                    {
                        $buffer[basename($file, '.json')] = file_get_contents($path.DIRECTORY_SEPARATOR.$file);
                    }
                }
            }
        }
        closedir($dir);

        return ($sorted) ? sort($buffer) : $buffer;
    }

    static function is_assoc($array)
    {
        return (array_values($array) !== $array);
    }

    static function array_get_val($array, $path)
    {
        for($i=$array; $key=array_shift($path); $i=$i[$key])
        {
            if(!isset($i[$key])) return null;
        }

        return $i;
    }

    static function array_set_val(&$array, $path, $value)
    {
        for($i=&$array; $key=array_shift($path); $i=&$i[$key])
        {
            if(!isset($i[$key])) $i[$key] = array();
        }

        $i = $value;
    }

    static function urlfor($apps=array(), $controller='', $method='', $args=array(), $absolute=true)
    {
        /*

         $domain = !is_null(Config::get('app_base_url')) ? Config::get('app_base_url') : strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]).'://'.$_SERVER['HTTP_HOST'].'/';

        return 	(($absolute) ? $domain : '')
                .(empty($apps) ? '' : ((is_array($apps)) ? join('/', $apps).'/' : $apps.'/'))
                .((empty($controller) && !empty($args)) ? 'home' : (empty($controller) ? '' : $controller))
                .(((empty($controller) && !empty($args)) || !(empty($controller)) && !(empty($method) && empty($args) && empty($controller))) ? '-' : '')
                .(empty($method) && empty($args) && empty($controller) ? '': (empty($method) ? 'index' : $method))
                .(empty($args) ? '' : '/'.((is_array($args)) ? join('/', $args) : $args));

        */
        $controller = ($controller == 'home') ? '' : $controller;
        $method 	= ($method == 'index') ? '' : $method;

        $domain = !is_null(Config::get('app_base_url')) ? Config::get('app_base_url') : strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]).'://'.$_SERVER['HTTP_HOST'].'/';

        // traitement apps

        if(!empty($apps))
        {
            if(is_array($apps))
            {
                $apps = join('/', $apps).'/';
            }
            else
            {
                $apps .= '/';
            }
        }
        else
        {
            $apps = '';
        }

        // traitement controller

        if(empty($controller))
        {
            if(!empty($args))
            {
                $controller = 'home';
            }
        }

        // traitement method

        if(empty($method))
        {
            if(!empty($args))
            {
                $method = 'index';
            }
        }

        // traitement separateur

        if(!empty($controller) && !empty($method))
        {
            $separator = '-';
        }
        else
        {
            $separator = '';
        }

        // traitement arg

        if(!empty($args))
        {
            if(is_array($args))
            {
                $args = '/'.join('/', $args);
            }
            else
            {
                $args = '/'.$args;
            }
        }
        else
        {
            $args = '';
        }

        return ($absolute ? $domain : '').$apps.$controller.$separator.$method.$args;
    }

    static function urlfor2($apps=array(), $controller='', $method='', $args=array(), $absolute=true)
    {
        $apps = is_array($apps) ? $apps : array($apps);

        $domain = Config::Host_getConfigFromPath($apps);
        $appsdomain = Config::Host_getConfigFromDomain($domain['domain']);
        $apps = array_slice(array_reverse($apps), 0, 0 - count($appsdomain));

        $controller = ($controller == 'home') ? '' : $controller;
        $method 	= ($method == 'index') ? '' : $method;

        $domain = !is_null(Config::get('app_base_url')) ? Config::get('app_base_url') : strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]).'://'.$_SERVER['HTTP_HOST'].'/';

        // traitement apps

        if(!empty($apps))
        {
            if(is_array($apps))
            {
                $apps = join('/', $apps).'/';
            }
            else
            {
                $apps .= '/';
            }
        }
        else
        {
            $apps = '';
        }

        // traitement controller

        if(empty($controller))
        {
            if(!empty($args))
            {
                $controller = 'home';
            }
        }

        // traitement method

        if(empty($method))
        {
            if(!empty($args))
            {
                $method = 'index';
            }
        }

        // traitement separateur

        if(!empty($controller) && !empty($method))
        {
            $separator = '-';
        }
        else
        {
            $separator = '';
        }

        // traitement arg

        if(!empty($args))
        {
            if(is_array($args))
            {
                $args = '/'.join('/', $args);
            }
            else
            {
                $args = '/'.$args;
            }
        }
        else
        {
            $args = '';
        }

        return $domain.$apps.$controller.$separator.$method.$args;
    }

    static function pathfor($apps=array(), $filename='', $suffix='')
    {
        return 	ROOT.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR
        .(empty($apps) ? '' : DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.((is_array($apps)) ? join(DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR, $apps) : $apps).DIRECTORY_SEPARATOR)
        .$filename.$suffix;
    }

    static function rrmdir($dir)
    {
        if (is_dir($dir))
        {
            $objects = scandir($dir);
            foreach ($objects as $object)
            {
                if ($object != "." && $object != "..")
                {
                    if (is_dir($dir.DIRECTORY_SEPARATOR.$object)) self::rrmdir($dir.DIRECTORY_SEPARATOR.$object); else unlink($dir.DIRECTORY_SEPARATOR.$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    static function values2dimensions($values, &$arr)
    {
        $e = array_shift($values);
        if(!is_null($e))
        {
            if(!isset($arr[$e]))
            {
                $arr[$e] = array();
            }
            Tools::values2dimensions($values, $arr[$e]);
        }
    }

    static function method_exists($class, $method)
    {
        return class_exists($class) && (method_exists($class, $method) || (method_exists($class, '__call') && method_exists($class, '__melody_method_exists') && $class::__melody_static_method_exists($method)));
    }

    static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    static function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    static function diverse_array($array)
    {
        $result = array();
        foreach($array as $key1 => $value1)
            foreach($value1 as $key2 => $value2)
                $result[$key2][$key1] = $value2;
        return $result;
    }
}