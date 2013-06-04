<?php

namespace Finck;

class Request
{
    public $route;

    public static function get($key, $default = null)
    {
        return !empty($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }


    public static function set($key, $value)
    {
        $_REQUEST[$key] = $value;
    }


    public static function remove($key)
    {
        unset($_REQUEST[$key]);
    }


    public static function all()
    {
        return $_REQUEST;
    }


    public static function is_post()
    {
        return self::method() == 'post';
    }


    public static function is_xhr()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }


    public static function method()
    {
        return !empty($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'get';
    }


    public static function redirect($location, $code = 302)
    {
        header('Location: ' . $location); die;
    }
}
