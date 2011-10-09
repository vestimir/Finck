Finck is a small library for defining URL resources. It can be used for prototyping, simple web projects and even large scale applications. Finck does not provide any obligatory structure, particular template engine, or conventions, you can build upon its simplicity and integrate it in your own workflow.

*`App::route($regex, $handler, $route_name = null)`*

The routing is done by the `route` method. It accepts the following parameters: a regular expression string that will match the desired url, a handler (closure or an array of type `array('class', 'method')`) and route name.
Every handler must return a string (or method that generates string if you are using a template engine) and by default accepts one parameter - the Request object.
Hint: The regular expression can use named groups to simplify the usage of parameters in the handler.

Examples:

    //Matches empty regex - the root url
    $app->route('', function () {
        return "This is a homepage";
    }, 'homepage');

    //matches user-profile and pass it to the controller class
    class Users
    {
        public function profile($request) {
            return $request->params['username']
        }
    }

    $app->route('user/(?P<username>\w+)', array('Users', 'profile'));

Exmaple index.php:

    <?php
    require_once 'finck.php';

    $app = new \Finck\App();
    $app->route('hello/(?P<name>\w+)', function ($request) {
        return "Hello, " . $request->params['name'];
    });
    $app->route('', function () {
        return "Welcome to our homepage! ";
    }, 'homepage');

    $app->dispatch();
