<?php
function input_validate($input)
{
    if (is_array($input) || is_object($input)) {
        foreach ($input as $k => $v) {
            if (is_array($input))
                $input[$k] = input_validate($v);
            else
                $input->$k = input_validate($v);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input);
    }

    return $input;
}

/**
 * @param $data mixed
 * @param null $xml SimpleXMLElement
 * @return null|SimpleXMLElement
 */
function xml_decrypt($data, $xml = null)
{
    if ($xml == null)
        $xml = new SimpleXMLElement('<root/>');

    foreach ($data as $key => $value) {
        if (is_object($value) || is_array($value)) {
            $tmpXml = $xml->addChild($key);
            xml_decrypt($value, $tmpXml);
        } else {
            $xml->addChild($key, $value);
        }
    }

    return $xml;
}

function xml_decrypt2($data)
{
    $xml = new SimpleXMLElement('<root/>');
    $array = (array)$data;
    array_walk_recursive($array, 'xml_decrypt2_add_element_to_xml', $xml);

    return $xml;
}

/**
 * @param $value
 * @param $key
 * @param $xml SimpleXMLElement
 */
function xml_decrypt2_add_element_to_xml($value, $key, $xml)
{
    var_dump($value);
    $xml->addChild($key, $value);
}

function url_joiner($url1, $url2)
{
    $url1 = trim(str_replace('\\', '/', $url1), '/');
    $url2 = trim(str_replace('\\', '/', $url2), '/');

    $urls = explode('/', trim($url1 . '/' . $url2, '/'));
    $url_result = '';
    foreach ($urls as $url) {
        if ($url == '..')
            $url_result = dirname($url_result) . '/';
        else
            $url_result .= $url . '/';
    }
    if (preg_match('/^\.\//', $url_result))
        $url_result = '.' . $url_result;
    return trim($url_result, '/');//trim($url1 . '/' . $url2, '/');
}

/**
 * @param $param array
 * @return mixed|string
 */
function array_url_joiner($param)
{
    $url = $param[0];
    for ($i = 0; $i < count($param) - 1; $i++) {
        $url = url_joiner($url, $param[$i + 1]);
    }

    return $url;
}

function append_php_extension($path)
{
    if (!preg_match('/.*\.php/i', $path))
        $path .= '.php';

    return $path;
}