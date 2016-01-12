<?php
namespace Melody\Framework\Routing;

use Melody\Framework\Controller;
use Melody\Framework\Utils\Tools;

class ErrorManager extends Controller
{
    public function __melody_access()
    {
        $this->setDefaultAccessRole(0);
    }

    public function anyTestAction($req, $res, $code, $args)
    {
        if(!is_null($req->getArg(0)))
        {
            list($uri, $msg) = unserialize(Tools::base64url_decode($req->getArg(0)));
        }
        else
        {
            if(empty($code))
            {
                $code = $args[0];
                $msg = '';
            }
            else
            {

                $msg = $args[0];
            }
        }

        echo 'URL : /'.(isset($uri) ? $uri : FrontController::$uri)."\n";
        echo 'Error : '.$code.' '.$this->MessageFor($code)."\n";
        echo 'Message : '.$msg."\n";

        return  $res->setViewAbs('', 'buffer')->setHeader('Content-Type', 'text/plain');
    }

    public function __call($method, $args)
    {
        $code = preg_replace('#(^[a-z]{1,})|(Action)#', '', $method);

        $req = array_shift($args);
        $res = array_shift($args);

        return $this->anyTestAction($req, $res, $code, $args);
    }

    private function MessageFor($code)
    {
        switch ($code)
        {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default : $text = ''; break;
        }

        return $text;
    }
}