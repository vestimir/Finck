<?php

use Finck\Request;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testGettingVar()
    {
        $_REQUEST['foo'] = 'bar';
        $this->assertEquals('bar', Request::get('foo'));
    }


    public function testGettingDefaultVar()
    {
        $this->assertEquals('baz', Request::get('cux', 'baz'));
    }


    public function testSettingVar()
    {
        Request::set('bum', 'bam');
        $this->assertEquals('bam', $_REQUEST['bum']);
    }


    public function testUnsettingVar()
    {
        $_REQUEST['foo'] = 'bar';
        Request::remove('foo');
        $this->assertArrayNotHasKey('foo', $_REQUEST);
    }


    public function testGettingAllVars()
    {
        $_REQUEST = array('foo' => 'bar', 'baz' => 'cux');
        $this->assertEquals(array('foo' => 'bar', 'baz' => 'cux'), Request::all());
    }
}
