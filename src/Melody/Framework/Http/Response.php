<?php
namespace Melody\Framework\Http;

class Response
{
    public $viewpath 	= array();
    public $viewvars 	= array();
    public $cache 		= false;
    public $cache_mode	= '';
    public $expiration 	= 0;
    public $buffer 		= '';

    public function __construct()
    {
        header('Content-Type: text/html; charset=utf-8');
    }

    public function setCode($code=200)
    {
        http_response_code($code);
        return $this;
    }

    public function setHeader($key, $value)
    {
        header($key.': '.$value.'; charset=utf-8');
        return $this;
    }

    public function setView($path, $vars=array(), $abs=false)
    {
        $this->viewpath = array($path, $abs);
        $this->viewvars = $vars;
        return $this;
    }

    public function setViewAbs($apps, $name, $vars=array())
    {
        return $this->setView(array($apps, $name), $vars, true);
    }

    public function addToBuffer($value)
    {
        $this->buffer .= $value;
        return $this;
    }

    public function cache($mode='on_demand', $time=0)
    {
        $this->cache 		= true;
        $this->cache_mode 	= $mode;
        $this->expiration	= $time;

        return $this;
    }
}