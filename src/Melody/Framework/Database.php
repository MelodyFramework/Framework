<?php
namespace Melody\Framework;

use PDO;

class Database
{
    protected static $instance = null;
    protected static $included = array();

    private function __construct()
    {
        if(self::$instance === null)
        {
            self::$instance = self::getInstance();
        }

        return self::$instance;
    }

    private static function connection($settings)
    {
        $emulate_prepares_below_version = '5.1.17';

        $dsndefaults = array_fill_keys(array('host', 'port', 'unix_socket', 'dbname', 'charset'), null);
        $dsnarr = array_intersect_key($settings, $dsndefaults);
        $dsnarr += $dsndefaults;

        // connection options I like
        $options = array(
            PDO::ATTR_ERRMODE => (DEV_ENV) ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );

        // connection charset handling for old php versions
        if ($dsnarr['charset'] and version_compare(PHP_VERSION, '5.3.6', '<'))
        {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES '.$dsnarr['charset'];
        }
        $dsnpairs = array();
        foreach ($dsnarr as $k => $v)
        {
            if ($v===null)
                continue;
            $dsnpairs[] = "{$k}={$v}";
        }

        $dsn = 'mysql:'.implode(';', $dsnpairs);
        $dbh = new PDO($dsn, $settings['user'], $settings['pass'], $options);

        // Set prepared statement emulation depending on server version
        $serverversion = $dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
        $emulate_prepares = empty(Config::get('database_emulate_prepared_stmt')) ? (version_compare($serverversion, $emulate_prepares_below_version, '<')) : Config::get('database_emulate_prepared_stmt') ;
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate_prepares);

        return $dbh;
    }

    static function getInstance($infos=array())
    {
        if(!isset(self::$instance))
        {
            if(empty($infos))
            {
                $settings = array(
                    'host' 			=> Config::get('database_host'),
                    'port' 			=> Config::get('database_port'),
                    'unix_socket' 	=> Config::get('database_unix_socket'),
                    'dbname'	 	=> Config::get('database_dbname'),
                    'charset' 		=> Config::get('database_charset'),
                    'user' 			=> Config::get('database_user'),
                    'pass' 			=> Config::get('database_passwd')
                );

                self::$instance = self::connection($settings);
            }
            else
            {
                $settings = array(
                    'host' 			=> $infos['host'],
                    'port' 			=> Config::get('database_port'),
                    'unix_socket' 	=> Config::get('database_unix_socket'),
                    'dbname'	 	=> $infos['dbnale'],
                    'charset' 		=> Config::get('database_charset'),
                    'user' 			=> $infos['user'],
                    'pass' 			=> $infos['pass']
                );
                self::$instance = self::connection($settings);
            }
        }
        return self::$instance;
    }

    function query($table='')
    {
        return new Query((!empty($table) ? $table : substr(strtolower(get_class($this)), 0, -5)));
    }

    function found_rows()
    {
        $req = self::$instance->query('SELECT FOUND_ROWS() as rows');
        $data = $req->fetch(PDO::FETCH_NUM);
        $req->closeCursor();

        return $data[0];
    }

    function last_insert_id()
    {
        $req = self::$instance->query('SELECT LAST_INSERT_ID() as id');
        $data = $req->fetch(PDO::FETCH_NUM);
        $req->closeCursor();

        return $data[0];
    }
}