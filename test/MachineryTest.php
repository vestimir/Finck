<?php

use Finck\Machinery;
use Finck\InvalidHandlerException;

class Dummy
{
    public function method($requ)
    {
        return 'This is a test';
    }
}

class MachineryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        //remove all routes
        foreach (Machinery::getRoutes() as $route_name => $opts) {
            Machinery::removeRoute($route_name);
        }

        //remove any matched routes
        Machinery::getInstance()->getRequest()->route = null;
    }


    public function invalidHandlers()
    {
        return array(
            array('a string'),
            array(true),
            array(false),
            array((new stdClass)),
            array(array(1, 2, 3)),
            array(array('class', 'method', 'not needed'))
        );
    }


    /**
     * @dataProvider invalidHandlers
     */
    public function testInvalidHandler($handler)
    {
        $this->assertNotEquals(true, Machinery::isValidHandler($handler));
    }


    /**
     * @dataProvider invalidHandlers
     * @expectedException Exception
     */
    public function testInvalidRouteThrowsException($handler)
    {
        Machinery::route('get', 'test', $handler, 'test_route');
    }


    public function testAddingRoute()
    {
        Machinery::route('get', 'test', function ($request) {}, 'test_route');
        $this->assertArrayHasKey('test_route', Machinery::getRoutes());
    }


    public function testAddingGetRoute()
    {
        Machinery::get('test', function ($request) {}, 'test_route');
        $this->assertArrayHasKey('test_route', Machinery::getRoutes());
    }


    public function testAddingArrayRoute()
    {
        Machinery::get('test', array('Dummy', 'method'), 'test_route');
        $this->assertArrayHasKey('test_route', Machinery::getRoutes());
    }


    public function testAddingFunctionRoute()
    {
        function dummy_function() {};

        Machinery::get('test', 'dummy_function', 'test_route');
        $this->assertArrayHasKey('test_route', Machinery::getRoutes());
    }


    public function testRemovingRoute()
    {
        Machinery::get('test', function () {}, 'test_route');
        Machinery::removeRoute('test_route');
        $this->assertArrayNotHasKey('test_route', Machinery::getRoutes());
    }


    public function testMakingUrlFromRoute()
    {
        Machinery::get('test', function () {}, 'test_route');
        $this->assertEquals('/test', Machinery::url('test_route'));
    }


    /**
     * @expectedException Exception
     */
    public function testMakingNonExistentUrlThrowsExceptions()
    {
        Machinery::url('not_existing');
    }


    public function routeRegexes()
    {
        return array(
            array('test/(?P<name>\w+)', '/test/gosho', array('name' => 'gosho')),
            array('test/(?P<id>\d+)', '/test/15', array('id' => 15)),
            array('test/(?P<id>\d+)/(?P<slug>\w+)', '/test/15/dummy-slug', array('id' => 15, 'slug' => 'dummy-slug')),
        );
    }


    /**
     * @dataProvider routeRegexes
     */
    public function testMakingUrlWithParams($regex, $expected, $params)
    {
        Machinery::get($regex, function () {}, 'test_route');
        $this->assertEquals($expected, Machinery::url('test_route', $params));
    }


    /**
     * @expectedException Exception
     */
    public function testThrowsExceptionOnDispatchIfEmptyRoutes()
    {
        Machinery::dispatch();
    }


    public function testProperDispatch()
    {
        Machinery::get('test', function () {}, 'test_route');
        Machinery::dispatch('/test');

        $current_route = Machinery::getCurrentRoute();
        $this->assertEquals('test_route', $current_route['name']);
    }


    /**
     * @expectedException Finck\NotFoundException
     */
    public function testNotFound()
    {
        Machinery::get('test', function () {}, 'test_route');
        Machinery::dispatch('/non_existing');
    }


    public function testProperRenderOfClosureRoute()
    {
        Machinery::get('test', function ($req) { return 'This is a test'; }, 'test_route');
        $this->assertEquals('This is a test', Machinery::dispatch('/test'));
    }


    public function testProperRenderOfArrayRoute()
    {
        Machinery::get('test', array('Dummy', 'method'), 'test_route');
        $this->assertEquals('This is a test', Machinery::dispatch('/test'));
    }


    public function testProperRenderOfFunctionRoute()
    {
        function plain_function($req) { return 'This is a test'; };
        Machinery::get('test', 'plain_function', 'test_route');
        $this->assertEquals('This is a test', Machinery::dispatch('/test'));
    }
}
