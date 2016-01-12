<?php
namespace Melody\Framework;

class Autoloader
{
    public function __construct()
    {

    }

    protected function getControllersAliases()
    {
        return [
            'Controller'    => 'Melody\\Framework\\Controller',
            'Model'         => 'Melody\\Framework\\Model',
            'Database'      => 'Melody\\Framework\\Database',
            'ErrorManager'  => 'Melody\\Framework\\Routing\\ErrorManager'
        ];
    }

    public function registerAutoload()
    {
        spl_autoload_register(function ($class)
        {
            $prefix = 'app';
            $base_dir = ROOT.'/app';

            // does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0)
            {
                return;
            }

            $nsarr = explode('\\', $class);
            if(isset($nsarr[count($nsarr) - 2]) && $nsarr[count($nsarr) - 2] == 'controllers')
            {
                $aliases = $this->getControllersAliases();
                if(isset($aliases[$nsarr[count($nsarr) - 1]]))
                {

                    class_alias($aliases[$nsarr[count($nsarr) - 1]], $class);
                }
            }

            // get the relative class name
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require($file);
            }
        });
    }
}