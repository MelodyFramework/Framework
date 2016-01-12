<?php

namespace Melody\Framework;

class Cache
{
    private static $expiration_varname 		= 'expiration';
    private static $expiration_mode_varname = 'expiration_mode';
    private static $content_varname			= 'cache';

    public static function get($component, $class)
    {
        $path = self::getPath($component, $class);

        if(file_exists($path))
        {
            include($path);

            $mode = (isset(${self::$expiration_mode_varname}) ? ${self::$expiration_mode_varname} : '' );

            switch(strtolower($mode))
            {
                case 'on_expiration':

                    if(isset(${self::$expiration_varname}) &&  ${self::$expiration_varname} >= time())
                    {
                        return ${self::$content_varname};
                    }
                    else
                    {
                        unlink($path);
                        return false;
                    }

                    break;
                case 'on_demand':
                default:

                    return ${self::$content_varname};

                    break;
            }
        }
        else
        {
            return false;
        }
    }

    public static function create($component, $class, $data, $mode, $time=0)
    {
        $path = self::getPath($component, $class);
        if(!file_exists(dirname($path)))
            @mkdir(dirname($path), 0700, true);

        switch(strtolower($mode))
        {
            case 'on_expiration':
                $expiration_str = '$'.self::$expiration_varname.' = '.$time.'; $'.self::$expiration_mode_varname.' = \'on_expiration\';';
                break;
            case 'on_demand':
            default:
                $expiration_str = '$'.self::$expiration_mode_varname.' = \'on_demand\';';
                break;
        }

        file_put_contents($path, '<?php '.$expiration_str.' $'.self::$content_varname.' = '.var_export($data, true).'; ?>');

        return $data;
    }

    public static function expire($component, $class)
    {
        $path = self::getPath($component, $class);

        if(file_exists($path))
        {
            unlink($path);
        }
    }

    private static function getPath($component, $class)
    {
        return ROOT.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$component.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class.'.cache');
    }
}