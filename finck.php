<?php

namespace Finck;

class App
{
    protected $debug = false;
    public $request;
    protected $routes = array();
    protected $middleware = array();

    public function __construct()
    {
        $this->request = new Request();
    }


    public function route($regex, $handler, $name = null)
    {
        $route = array(
            'regex'   => $regex,
            'handler' => $handler
        );

        if ($name) {
            $this->routes[$name] = $route;
        } else {
            $this->routes[] = $route;
        }
    }


    public function add_middleware($class_name)
    {
        if (!in_array($class_name, $this->middleware)) {
            $this->middleware[] = $class_name;
        }
    }


    public function dispatch($debug = false)
    {
        $this->debug = $debug;
        if (!$this->routes) throw new \Exception("No routes defined. ");

        foreach ($this->routes as $route) {
            $regex = "@{$route['regex']}@i";
            $matches = array();
            if (preg_match($regex, Request::get('route'), $matches)) {
                $this->request->route = $route;

                array_shift($matches);
                $params = array_unique($matches);
                $this->request->params = $params;

                break;
            }
        }

        if (!$this->request->route) throw new NotFoundException("No route found");

        //here process middleware request
        foreach ($this->middleware as $m) {
            $this->request = $m::process_request($this->request);
        }

        $response = null;

        //here execute the route
        $handler = $this->request->route['handler'];
        if (is_object($handler) && get_class($handler) == 'Closure') {
            $response = $handler($this->request);
        } elseif (is_array($handler) && count($handler) == 2) {
            //get the class and method name
            list($class_name, $handler_name) = $handler;
            $class = new $class_name();
            $class->$handler_name($this->request);
        }

        //here process middleware response
        foreach ($this->middleware as $m) {
            $response = $m::process_response($response);
        }

        print $response;
        die;
    }
}


class Request
{
    public $route;
    public $params;

    public static function get($key, $default = null)
    {
        return !empty($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }


    public static function all()
    {
        return $_REQUEST;
    }


    public static function set($key, $value)
    {
        $_REQUEST[$key] = $value;
    }


    public static function remove($key)
    {
        if (isset($_REQUEST[$key])) unset($_REQUEST[$key]);
    }
}


class Middleware
{
    public static function process_request($request)
    {
        return $request;
    }


    public static function process_response($response)
    {
        return $response;
    }
}

class NotFoundException extends \Exception {}
