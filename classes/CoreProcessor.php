<?php
/**
 * Created by PhpStorm.
 * User: mh
 * Date: 1/23/2019
 * Time: 21:25
 */

class CoreProcessor
{
    private $_root_directory;

    private $root_dir;
    private $root_dir_relative;
    private $base_url;
    private $request;
    private $full_request;

    public function __construct($dir)
    {
        $this->_root_directory = $dir;
    }

    //<editor-fold desc="private property gathering">

    /**
     * @return mixed
     */
    public function getRootDir()
    {
        return $this->root_dir;
    }

    /**
     * @return mixed
     */
    public function getRootDirRelative()
    {
        return $this->root_dir_relative;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->base_url;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getFullRequest()
    {
        return $this->full_request;
    }

    //</editor-fold>

    public function pre_processor()
    {
        $this->root_dir = str_replace("\\", "/", $this->_root_directory);
        $this->root_dir_relative = str_replace(str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']), '', $this->root_dir);;
        $this->base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$this->root_dir_relative";
        $this->request = strtok(urldecode(str_replace($this->root_dir_relative, '', $_SERVER['REQUEST_URI'])), '?');
        $this->full_request = $this->root_dir . $this->request;
    }


    public function url_standardize($url)
    {
        $url = str_replace("\\", "/", $url);
    }

    public static function register_autoload($classes_root_dir, $throwError = true)
    {
        spl_autoload_register(function ($path) use ($classes_root_dir) {
            $file_path = str_replace('//', '/', str_replace("\\", "/", $classes_root_dir . $path)) . ".php";
            if (file_exists($file_path))
                include_once $file_path . '';
        }, $throwError);
    }
}