Finck is a small library for defining URL resources. It can be used for prototyping, simple web projects and even large scale applications. Finck does not provide any obligatory structure, particular template engine, or conventions, you can build upon its simplicity and integrate it in your own workflow.

```php
App::route($request_method, $regex, $handler, $route_name = null)
App::get($regex, $handler, $route_name = null)
App::post($regex, $handler, $route_name = null)
App::put($regex, $handler, $route_name = null)
App::delete($regex, $handler, $route_name = null)
App::all($regex, $handler, $route_name = null)
```

The routing is done by the `route` method. It accepts the following parameters: http request method, a regular expression string that will match the desired url, a handler (closure or an array of type `array('class', 'method')`) and route name.
Every handler must return a string (or method that generates string if you are using a template engine) and by default accepts one parameter - the Request object.

 - *Hint*: The regular expression can use named groups to simplify the usage of parameters in the handler.
 - *Hint 2*: There are shorthand methods for routing with different request methods - get, post, put, delete and all, which routes the matched url regardles of the request method.
 - *Hint 3*: Finck app is Singleton object, you can access the Finck object by simply call to `Finck::getInstance()`.

```php
use \Finck\Finck as Finck;

//Matches empty regex - the root url
Finck::route('get', '', function () {
    return "This is a homepage";
}, 'homepage');

//This is the same
Finck::get('', function () {
	return "This is a homepage";
}, 'homepage');

//matches user-profile and pass it to the controller class
class Users
{
    public function profile($request) {
        return $request->params['username']
    }
}

//other use cases
Finck::get('user/(?P<username>\w+)', array('Users', 'profile'));
Finck::post('articles', array('Articles', 'create'), 'articles_create');

//You can register restful http resource
Finck::register_resource('articles', 'Articles');

//will init the following (similar to Ruby on Rails) url structure
Finck::get('articles', array('Articles', 'index'), 'articles_index');
Finck::get('articles/(?P<id>\d+)', array('Articles', 'show'), 'articles_show');
Finck::post('articles', array('Articles', 'create'), 'articles_create');
Finck::put('articles/(?P<id>\d+)', array('Articles', 'update'), 'articles_update');
Finck::delete('articles/(?P<id>\d+)', array('Articles', 'destroy'), 'articles_destroy');
Finck::get('articles/new', array('Articles', 'add'), 'articles_new');
Finck::get('articles/edit/(?P<id>\d+)', array('Articles', 'edit'), 'articles_edit');
```

Example simple application:

```php
require_once 'finck.php';
use \Finck\Finck as Finck;

Finck::get('hello/(?P<name>\w+)', function ($request) {
    return "Hello, " . $request->params['name'];
});
Finck::get('', function () {
    return "Welcome to our homepage! ";
}, 'homepage');

//run the application
Finck::dispatch();
```