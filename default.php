<?php
include_once 'RESTFulCore.php';
header('Content-Type: text/plain; charset=utf-8');

$rest = new RESTFulCore();
$rest->addClassAutoLoader('../app_core/services/');
$rest->addClassAutoLoader('../app_core/');
$rest->addClassAutoLoader('../app_core/test');

$connection = mysqli_connect('localhost', 'root', '', 'dbs_asantype');
mysqli_set_charset($connection, 'UTF8');
$rest->setMysqliConnection($connection);
$rest->setAuthenticationAttribute('@auth');
$rest->setCallableAuthenticationTest(function ($in1, $in2, $in3, $in4) {

    return true;
});

$user = \CLS\DBS\User::current_user($connection);
$rest->setUser($user);
$class_name = $rest->trace_request('', true);
var_dump($rest->doRequest($class_name, strtolower($_SERVER['REQUEST_METHOD'])));


