# SimpleRESTFUL is a agile framework for build RESTFUL api with PHP
##### Author: 
Mahdi Hasanpour [<mh.hasnapour@gmail.com>](mailto:mh.hasnapour@gmail.com)

## Getting Start
Install library with Composer
``` shell script
composer require mhr/simple-restful
```
Create a php file that all incoming request rewrite to it file (e.g. with .htaccess and RewriteEngine ) with below codes. 
```php
<?php
 require __DIR__ . '/vendor/autoload.php';
 
 $core = new \SimpleRESTful\Core();
 
 $core->addMiddleware(function ($next, \SimpleRESTful\HTTP\Response $response) {
     $next();
     $response->generateOutput();
 });
 
 $core->run();
```