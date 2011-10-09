Usage:

    <?php
    /*
     * File: index.php
     */

    require_once 'finck.php';

    $app = new \Finck\App();
    $app->route('hello/(?P<name>)', function ($request) {
        return "Hello, " . $request->params['name'];
    });
    $app->route('', function () {
        return "Welcome to our homepage! ";
    }, 'homepage');
