Finck is a small library for defining URL resources. It can be used for prototyping, simple web projects and even large scale applications. Finck does not provide any obligatory structure, particular template engine, or conventions, you can build upon its simplicity and integrate it in your own workflow.

*`App::route($request_method, $regex, $handler, $route_name = null)`*

The routing is done by the `route` method. It accepts the following parameters: http request method, a regular expression string that will match the desired url, a handler (closure or an array of type `array('class', 'method')`) and route name.
Every handler must return a string (or method that generates string if you are using a template engine) and by default accepts one parameter - the Request object.
Hint: The regular expression can use named groups to simplify the usage of parameters in the handler.
Hint 2: There are shorthand methods for routing with different request methods - get, post, put, delete and all, which routes the matched url regardles of the request method.

Examples:

    //Matches empty regex - the root url
    $app->route('get', '', function () {
        return "This is a homepage";
    }, 'homepage');

    //This is practiaclly the same
    $app->get('', function () {
    	return "This is a homepage";
    }, 'homepage');

    //matches user-profile and pass it to the controller class
    class Users
    {
        public function profile($request) {
            return $request->params['username']
        }
    }

    $app->get('user/(?P<username>\w+)', array('Users', 'profile'));

    $app->post('articles', array('Articles', 'create'), 'articles_create');
    $app->put('articles/(?P<id>\d+)', array('Articles', 'update'), 'articles_update');
    $app->delete('articles/(?P<id>\d+)', array('Articles', 'destroy'), 'articles_destroy');

Exmaple index.php:

    <?php
    require_once 'finck.php';

    $app = new \Finck\App();
    $app->get('hello/(?P<name>\w+)', function ($request) {
        return "Hello, " . $request->params['name'];
    });
    $app->get('', function () {
        return "Welcome to our homepage! ";
    }, 'homepage');

    $app->dispatch();
