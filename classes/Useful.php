<?php
/**
 * Created by PhpStorm.
 * User: mh
 * Date: 12/25/2017
 * Time: 8:35
 */

class Useful
{

    const VALIDATION_PREG_PATERN_DATE = "/^[0-9]{4}\/(0[0-9]|1[0-2])\/([0-2][0-9]|3[0-1])$/";

    public function InputCheck($arr)
    {
        if (!is_array($arr))
            return false;
        foreach ($arr as $k => $v) {
            if (is_array($arr[$k]))
                $arr[$k] = $this->InputCheck($arr[$k]);
            else
                $arr[$k] = $this->test_input($arr[$k]);
        }

        return $arr;
    }

    public function InputCheck2($arr, $skip = array(), $lastKey = '')
    {
        if (!is_array($arr))
            return false;
        foreach ($arr as $k => $v) {
            $skipItem = trim($lastKey . '.' . $k, '.');

            if (!in_array($skipItem, $skip)) {
                if (is_object($arr[$k])) {
                    $arr[$k] = $this->ObjectInputCheck($arr[$k], $skip, $skipItem);
                } else if (is_array($arr[$k]))
                    $arr[$k] = $this->InputCheck2($arr[$k], $skip);
                else
                    $arr[$k] = $this->test_input($arr[$k]);
            }
        }

        return $arr;
    }

    public function ObjectInputCheck($obj, $skip = array(), $lastKey = '')
    {
        if (is_array($obj))
            return $this->InputCheck2($obj, $skip);
        else if (is_object($obj)) {
            foreach ($obj as $key => $value) {
                $skipItem = trim($lastKey . '.' . $key, '.');
                if (in_array($skipItem, $skip))
                    continue;

                if (is_object($obj->$key))
                    $obj->$key = $this->ObjectInputCheck($obj->$key, $skip, $skipItem);
                else if (is_array($obj->$key)) {
                    $obj->$key = $this->InputCheck2($obj->$key, $skip);
                } else
                    $obj->$key = $this->test_input($obj->$key);
            }
            return $obj;
        } else {
            return $this->test_input($obj);
        }
    }

    public function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }


    public function GUID()
    {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    public function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        /*else
            $ipaddress = 'UNKNOWN';*/
        return $ipaddress;
    }

    public function get_client_ip2()
    {
        $ipaddress = false;
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        /*else
            $ipaddress = 'UNKNOWN';*/
        return $ipaddress;
    }

    public function mail_utf8($to, $from_user, $from_email, $subject = '(No subject)', $message = '')
    {
        $from_user = "=?UTF-8?B?" . base64_encode($from_user) . "?=";
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        $headers = "From: $from_user <$from_email>\r\n" .
            "MIME-Version: 1.0" . "\r\n" .
            "Content-type: text/html; charset=UTF-8" . "\r\n";

        return mail($to, $subject, $message, $headers);
    }

    public function mail_utf8_withoutSender($to, $subject = '(No subject)', $message = '')
    {
        $headers = "MIME-Version: 1.0" . "\r\n" .
            "Content-type: text/html; charset=UTF-8" . "\r\n";

        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        return mail($to, $subject, $message, $headers);
    }


    public function IsImage($filename)
    {
        if(!file_exists($filename))
            return false;
        $ims = getimagesize($filename);
        if (!$ims)
            return false;

        $accptable = array(
            IMAGETYPE_JPEG,
            IMAGETYPE_JPEG2000,
            IMAGETYPE_GIF,
            IMAGETYPE_PNG,
            IMAGETYPE_ICO
        );

        return in_array($ims[2], $accptable);
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object))
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }

    public function deleteFile($filename)
    {
        if (is_dir($filename)) {
            $objects = scandir($filename);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($filename . "/" . $object))
                        $this->rrmdir($filename . "/" . $object);
                    else
                        unlink($filename . "/" . $object);
                }
            }
            rmdir($filename);
        } else
            unlink($filename);
    }

    public function getRequest()
    {
        $content = file_get_contents('php://input');
        $result = json_decode($content, true);

        if (json_last_error() > 0) {
            $result = array();
            parse_str($content, $result);
        }

        return $result;
    }

    public function absuloteURL($path)
    {
        $patern = '/\.\.\//';
        $service = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
        $service = trim(str_replace(basename($service), '', $service), '/');
        $num = preg_match_all($patern, $path);
        for ($i = 0; $i < $num; $i++)
            $service = trim(substr($service, 0, strrpos($service, '/')));
        $path = preg_replace($patern, '', $path);

        return $service . '/' . trim($path, '.');
    }

    public function convertJalaliDateToGregorian($date)
    {
        if (preg_match(Useful::VALIDATION_PREG_PATERN_DATE, $date) > 0) {
            $date = explode('/', $date);
            return jalali_to_gregorian($date[0], $date[1], $date[2], '-');
        }
    }

    public function complete_url($path = "")
    {
        if (empty($path))
            $path = $_SERVER["REQUEST_URI"];
        return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $path;
    }

}