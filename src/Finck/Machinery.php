<?php

namespace Finck;

class Machinery
{
    public $routes = array();

    protected static $_instance;
    protected static $allowed_http_verbs = array('get', 'post', 'put', 'delete', 'all');
    protected $request;

    public function __construct()
    {
        $this->request = new Request();
    }


    public function getRequest()
    {
        return $this->request;
    }


    public static function getInstance()
    {
        if (!self::$_instance) self::$_instance = new Machinery();
        return self::$_instance;
    }


    public static function getRoutes()
    {
        return self::getInstance()->routes;
    }


    public static function isValidHandler($handler)
    {
        if (is_object($handler) && get_class($handler) == 'Closure') return true;
        elseif (is_array($handler) && count($handler) == 2 && method_exists($handler[0], $handler[1])) return true;
        elseif (is_callable($handler)) return true;

        return false;
    }


    public static function route($http_method, $regex, $handler, $name)
    {
        //validate handler
        if (!self::isValidHandler($handler)) {
            throw new \Exception('Expected function, or array of class and method, got ' . gettype($handler) . '. ');
        }

        self::getInstance()->routes[$name] = array(
            'name'    => $name,
            'method'  => $http_method,
            'regex'   => $regex,
            'handler' => $handler,
        );
    }


    public static function __callStatic($method, $args = array())
    {
        if (in_array($method, self::$allowed_http_verbs)) {
            $args = array_merge(array($method), $args);
            call_user_func_array(array('self', 'route'), $args);
        }
    }


    public static function removeRoute($route_name)
    {
        unset(self::getInstance()->routes[$route_name]);
    }


    public static function url($name, $args = array())
    {
        $routes = self::getInstance()->getRoutes();
        if (!array_key_exists($name, $routes)) throw new \Exception("No such route for key {$name}");
        $route_string = $routes[$name]['regex'];

        foreach ($args as $k => $v) {
            $route_string = preg_replace("@\(\?P\<{$k}\>\\\(w|d)\+\)@i", $v, $route_string);
        }

        return '/' . $route_string;
    }


    public static function dispatch($requested_route = null)
    {
        $_self = self::getInstance();

        if (!$_self->routes) throw new \Exception('No routes defined. ');
        
        $requested_route = !empty($requested_route) ? $requested_route : Request::get('route');

        //fix the server method
        if (!empty($_POST['_method'])) $_SERVER['REQUEST_METHOD'] = $_POST['_method'];

        foreach ($_self->routes as $route) {
            $regex = "@{$route['regex']}@i";
            $matches = array();
            if (preg_match($regex, $requested_route, $matches)) {
                //skip this route if method doesn't match
                if ($route['method'] != 'all' && Request::method() != $route['method']) continue;

                $_self->request->route = $route;

                array_shift($matches);
                $params = array_unique($matches);
                $_self->request->params = $params;

                break;
            }
        }

        if (!$_self->request->route) throw new NotFoundException("No route found for {$requested_route}");

        //here execute the route
        $handler = $_self->request->route['handler'];
        if (is_object($handler) && get_class($handler) == 'Closure') {
            $response = $handler($_self->request);
        } elseif (is_array($handler)) {
            //get the class and method name
            list($class_name, $handler_name) = $handler;

            $class = new $class_name();
            $response = $class->$handler_name($_self->request);
        } elseif (is_string($handler) && is_callable($handler)) {
            $response = $handler($_self->request);
        } else {
            throw new \Exception('Invalid handler. Handlers must be either Closure or array of two elements: class, method. ');
        }

        return $response;
    }


    public static function getCurrentRoute()
    {
        return self::getInstance()->request->route;
    }
}
