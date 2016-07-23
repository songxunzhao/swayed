<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 7/23/2016
 * Time: 10:01 PM
 */
namespace App\Loader;

class Config {
    public static function loadConfig($name) {
        $base_dir = __DIR__ . '/../config/';
        if(ends_with($name, '.php'))
            $config = require $base_dir . $name;
        else
            $config = require $base_dir . $name . '.php';

    }
}