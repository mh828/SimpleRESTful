<?php
/**
 * Created by PhpStorm.
 * User: mh
 * Date: 1/22/2019
 * Time: 17:43
 */

class URLProcessor
{
    public $page;
    public $params;


    public function findPage($root_dir, $request)
    {
        //this make assurance thant root_dir end with `/`
        $root_dir = rtrim($root_dir, '/') . '/';
        $request = trim($request, '/');

        $page = false;
        $rst = preg_split("/\//", $request);
        $prms = array();

        do {
            $current = rtrim($root_dir . implode("/", $rst), '/');
            $is_dir = is_dir($current);
            $file_exist = file_exists($current . '.php');


            if ($file_exist)
                $page = $current . '.php';
            else if ($is_dir && file_exists($current . '/index.php'))
                $page = $current . '/index.php';

            if ($page)
                break;

            $tmp = array_pop($rst);
            if (!empty($tmp))
                array_push($prms, $tmp);
        } while (!$page && count($rst) > 0);

        if (!$page && file_exists($root_dir . '/index.php')) {
            $page = $root_dir . 'index.php';
        }
        $prms = array_reverse($prms);

        $this->page = $page;
        $this->params = $prms;

        return $this;
    }
}