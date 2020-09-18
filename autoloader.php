<?php
spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class);
    @include __DIR__ . '/' . str_replace('SimpleRESTful/', '', $path) . '.php';
});