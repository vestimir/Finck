<?php

namespace Finck;

abstract class Middleware
{
    abstract public static function process_request($request);
    abstract public static function process_response($response);
}
