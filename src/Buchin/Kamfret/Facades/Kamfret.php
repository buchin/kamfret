<?php namespace Buchin\Kamfret\Facades;

use Illuminate\Support\Facades\Facade;

class Kamfret extends Facade{
    
    protected static function getFacadeAccessor() { return 'kamfret'; }
}