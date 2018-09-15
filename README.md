#Usage Example
Sample Project structure
```
    + API
        -+ classes
            --+ ns1
                ---+ ns2
                    ----+ ns3
                        ----- className.php
            test.php
        -- .htaccess
        -- index.php
    + core -> this repository
    
```

* first create a directory as root of project
* clone this repository in core directory at root directory
    `git clone "https://github.com/mh828/SimpleRESTful" core`
* create API directory and copy `core/.htaccess` on it
* create an empty directory in `API` directory with name `classes`.
 this directory contain classes that response to request
* create `index.php` file that all request rewrite to it.

## `API/index.php`
This file receive a request and call a function in proper class based on request url.

```php
<?php

include_once '../core/RESTFulCore.php';

$rest = new RESTFulCore();
//add classes path to autoloader
$rest->addClassAutoLoader('classes/');
//retrieve class name and namespace from url. is API url is www.hostname.com/API
// every thin after API is class identifier for example www.hostname.com/API/test
// is test.php file in `API/classes' that contain a class with name test.
//the name of class must be same as the name of file
// if url is something like this: www.hostname.com/API/namespace1/namespace2/namespace3/className
// this related class located in `API/namespace1/namespace2/namespace3/className.php' and its class name is className
// and its namespaces is: namespace namespace1\namespace2\namespace3;
$class_name = $rest->trace_request('', true);

header('content-type: application/json');
// get result by call `doRequest` by define class name and method name and print its response
echo $rest->doRequest($class_name,strtolower($_SERVER['REQUEST_METHOD']));

```


## `API/classes/test.php`

sample url: localhost/API/test
```php
<?php

class test
{
    public function get()
    {
        return json_encode(
            array(
             'variable1' => 'value 1',
             'variable2' => 'value 2',
             'variable3' => 'value 3',
             'variable4' => 'value 4',
             'variable5' => 'value 5'
            )
        );
    }
}
```


## `API/classes/ns1/ns2/ns3/className.php`
sample url : `localhost/API/ns1/ns2/ns3/className`
```php
<?php

namespace ns1\ns2\ns3;
class className
{
    public function get()
    {
        return json_encode(array(
            'welcome_message' => 'Hello World...!'
        ));
    }
}
```

