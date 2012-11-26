<?php

namespace Finck;

class Finck
{
    protected static $_instance;
    protected static $allowed_http_verbs = array('get', 'post', 'put', 'delete', 'all');

    public $request;

    protected $ENV;
    protected $routes = array();
    protected $middleware = array();

    public function __construct()
    {
        $this->request = new Request();
    }


    public static function getInstance()
    {
        if (!self::$_instance) self::$_instance = new Finck();
        return self::$_instance;
    }


    public static function route($http_method, $regex, $handler, $name = null)
    {
        $route = array(
            'regex'   => $regex,
            'handler' => $handler,
            'method'  => $http_method
        );

        $_self = self::getInstance();
        if ($name) {
            $_self->routes[$name] = $route;
        } else {
            $_self->routes[] = $route;
        }
    }


    public static function register_resource($resource, $handler)
    {
        self::get($resource . '/(?P<id>\d+)', array($handler, 'show'), $resource . '_show');
        self::post($resource, array($handler, 'create'), $resource . '_create');
        self::put($resource . '/(?P<id>\d+)', array($handler, 'update'), $resource . '_update');
        self::delete($resource . '/(?P<id>\d+)', array($handler, 'destroy'), $resource . '_destroy');

        self::get($resource . '/new', array($handler, 'add'), $resource . '_new');
        self::get($resource . '/edit/(?P<id>\d+)', array($handler, 'edit'), $resource . '_edit');

        self::get($resource, array($handler, 'index'), $resource . '_index');
    }


    public function get_routes()
    {
        return $this->routes;
    }


    public static function url($name, $args = array())
    {
        $routes = self::getInstance()->get_routes();
        if (!array_key_exists($name, $routes)) throw new \Exception("No such route for key {$name}");
        $route_string = $routes[$name]['regex'];

        foreach ($args as $k => $v) {
            $route_string = preg_replace("@\(\?P\<{$k}\>\\\(w|d)\+\)@i", $v, $route_string);
        }

        return '/' . $route_string;
    }


    public static function __callStatic($method, $args = array())
    {
        if (in_array($method, self::$allowed_http_verbs)) {
            $args = array_merge(array($method), $args);
            call_user_func_array(array('self', 'route'), $args);
        }
    }


    public static function add_middleware($class_name)
    {
        if (!in_array($class_name, self::getInstance()->middleware)) self::getInstance()->middleware[] = $class_name;
    }


    public function get_middleware()
    {
        return $this->middleware;
    }


    public static function dispatch($ENV = 'development')
    {
        $_self = self::getInstance();
        $_self->ENV = $ENV;
        if (!$_self->routes) throw new \Exception("No routes defined. ");

        //fix the server method
        if (!empty($_POST['_method'])) $_SERVER['REQUEST_METHOD'] = $_POST['_method'];

        $requested_route = Request::get('route');
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

        //TODO: add here default 404 as route or maybe handle exceptions as static files 404.html, 500.html
        if (!$_self->request->route) throw new NotFoundException("No route found for {$requested_route}");

        //here process middleware request
        $middleware = $_self->get_middleware();
        foreach ($middleware as $m) $_self->request = $m::process_request($_self->request);

        $response = null;

        //here execute the route
        $handler = $_self->request->route['handler'];
        if (is_object($handler) && get_class($handler) == 'Closure') {
            $response = $handler($_self->request);
        } elseif (is_array($handler)) {
            //get the class and method name
            list($class_name, $handler_name) = $handler;

            $class = new $class_name();
            $response = $class->$handler_name($_self->request);
        } else {
            throw new \Exception('Invalid handler. Handlers must be either Closure or array of two elements: class, method. ');
        }

        //here process middleware response
        foreach ($middleware as $m) $response = $m::process_response($response);

        print $response; die;
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


    public static function all($default = array())
    {
        return $_REQUEST ? $_REQUEST : $default;
    }


    public static function get_body()
    {
        return file_get_contents('php://input');
    }


    public static function set($key, $value)
    {
        $_REQUEST[$key] = $value;
    }


    public static function remove($key)
    {
        if (isset($_REQUEST[$key])) unset($_REQUEST[$key]);
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
        return strtolower($_SERVER['REQUEST_METHOD']);
    }


    public static function redirect($location, $code = 302)
    {
        header('Location: ' . $location); die;
    }
}


abstract class Middleware
{
    abstract public static function process_request($request);
    abstract public static function process_response($response);
}

class NotFoundException extends \Exception {}
