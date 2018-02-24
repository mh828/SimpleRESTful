<?php
include_once 'functions.php';
include_once 'Useful.php';
include_once 'jdf.php';

class RESTFulCore implements Serializable, JsonSerializable
{
    const CONTENT_DISPOSITION_INLINE = 'inline';
    const CONTENT_DISPOSITION_ATTACHMENT = 'attachment';


    /**
     * @var array
     */
    private $class_dirs;

    private $authentication_attribute;
    /**
     * @var callable
     */
    private $callable_authentication_test;
    private $callable_arguments;
    public $user;

    /**
     * @var array
     */
    public $params;

    /**
     * @var mysqli
     */
    public $mysqli_connection;

    private $content_type;
    private $content_type_charset;
    private $content_disposition;
    private $content_disposition_file_name;

    private $output_handler;

    public function __construct($authentication_attribute = '')
    {
        spl_autoload_register(array($this, 'autoloader'));
        $this->class_dirs = array();

        $this->authentication_attribute = $authentication_attribute;

        $this->setContentType('application/json', 'utf-8');
        $this->setContentDisposition(self::CONTENT_DISPOSITION_INLINE, '');

        $this->setOutputHandler('application/json', function ($input) {
            echo json_encode($input);
        });
    }

    //<editor-fold desc="property setter and getter">

    /**
     * @param string $authentication_attribute
     */
    public function setAuthenticationAttribute($authentication_attribute)
    {
        $this->authentication_attribute = $authentication_attribute;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param mysqli $mysqli_connection
     */
    public function setMysqliConnection($mysqli_connection)
    {
        $this->mysqli_connection = $mysqli_connection;
    }

    /**
     * @param callable $callable_authentication_test
     */
    public function setCallableAuthenticationTest($callable_authentication_test)
    {
        $this->callable_authentication_test = $callable_authentication_test;
    }

    /**
     * @param array $callable_arguments
     */
    public function setCallableArguments($callable_arguments)
    {
        $this->callable_arguments = $callable_arguments;
    }

    /**
     * @return mixed
     */
    public function getCallableArguments()
    {
        if (empty($this->callable_arguments) || $this->callable_arguments == null)
            $this->callable_arguments = array($this, $this->user, $this->mysqli_connection, &$this->params);
        return $this->callable_arguments;
    }

    /**
     * @param $content_type
     * @param $content_type_charset
     */
    public function setContentType($content_type, $content_type_charset)
    {
        $this->content_type = $content_type;
        $this->content_type_charset = $content_type_charset;
    }

    /**
     * @param $content_disposition
     * @param $content_disposition_file_name
     */
    public function setContentDisposition($content_disposition, $content_disposition_file_name)
    {
        $this->content_disposition = $content_disposition;
        $this->content_disposition_file_name = $content_disposition_file_name;
    }

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * @return mixed
     */
    public function getContentTypeCharset()
    {
        return $this->content_type_charset;
    }

    /**
     * @return mixed
     */
    public function getContentDisposition()
    {
        return $this->content_disposition;
    }

    /**
     * @return mixed
     */
    public function getContentDispositionFileName()
    {
        return $this->content_disposition_file_name;
    }

    /**
     * @param $content_type string
     * @param $callback callable
     */
    public function setOutputHandler($content_type, $callback)
    {
        if (is_callable($callback)) {
            if (is_array($this->output_handler))
                $this->output_handler[$content_type] = $callback;
            else
                $this->output_handler = array($content_type => $callback);
        }
    }

    //</editor-fold>

    public function doRequest($class_name, $method_name)
    {
        $class_name = $this->convert_uri_to_namespace($class_name);

        if (class_exists($class_name)) {
            $perform_action = true;

            $class = new  ReflectionClass($class_name);

            if (!empty($this->authentication_attribute)) {
                if ($this->need_authentication_test($class->getDocComment())) {
                    if (is_callable($this->callable_authentication_test)) {
                        $callable_argument = $this->callable_argument_creator($this->callable_authentication_test, $this->getCallableArguments());
                        $perform_action = call_user_func_array($this->callable_authentication_test, $callable_argument);
                    } else {
                        $perform_action = false;
                    }
                }
            }

            if ($class->hasMethod($method_name)) {
                $method = $class->getMethod($method_name);

                if ($this->need_authentication_test($method->getDocComment())) {
                    if (is_callable($this->callable_authentication_test)) {
                        $callable_argument = $this->callable_argument_creator($this->callable_authentication_test, $this->getCallableArguments());
                        $perform_action = call_user_func_array($this->callable_authentication_test, $callable_argument);
                    } else {
                        $perform_action = false;
                    }
                }

                if ($perform_action) {
                    $class_constructor = $class->getConstructor();
                    if ($class_constructor) {
                        $callable_argument = $this->argument_creator($class_constructor->getNumberOfParameters(), $this->getCallableArguments());
                        $class_instance = $class->newInstanceArgs($callable_argument);
                    } else
                        $class_instance = $class->newInstance();

                    $callable_argument = $this->argument_creator($method->getNumberOfParameters(), $this->getCallableArguments());
                    return $method->invokeArgs($class_instance, $callable_argument);
                }
            }

        }
    }

    private function callable_argument_creator($callable, $args_array)
    {
        $ref = is_array($callable) ? new ReflectionMethod($callable[0], $callable[1]) : new ReflectionFunction($callable);
        $param_count = $ref->getNumberOfParameters();
        return $this->argument_creator($param_count, $args_array);
    }

    private function argument_creator($count, $args_array)
    {
        $args = array();
        for ($i = 0; $i < $count; $i++) {
            $args[] = isset($args_array[$i]) ? $args_array[$i] : null;
        }

        return $args;
    }

    public function trace_request($request = '', $accept_empty_params = false)
    {
        $temp_params = array();

        if (empty($request))
            $request = $this->cut_request_from_uri();
        $class = '';
        $temp_path = str_replace('/', '\\', $request);

        while (!empty($temp_path) && strlen($temp_path) > 0) {
            if (class_exists($temp_path)) {
                $class = $temp_path;
                break;
            }

            $indx = strrpos($temp_path, '\\');
            $tmp = substr($temp_path, $indx + 1);
            if (!empty($tmp) || $accept_empty_params)
                $temp_params[] = $tmp;
            $temp_path = substr($temp_path, 0, $indx);
        }
        $this->params = input_validate(array_reverse($temp_params));
        return $class;
    }

    private function need_authentication_test($docComment)
    {
        $res = false;

        if (!empty($this->authentication_attribute))
            $res = preg_match("/{$this->authentication_attribute}/", $docComment) > 0;

        return $res;
    }

    public function addClassAutoLoader($path_to_class)
    {
        $this->class_dirs[] = $path_to_class;
    }

    public function autoloader($path)
    {
        foreach ($this->class_dirs as $class_dir) {
            $pt = ($class_dir . $path) . ".php";
            if (file_exists($pt)) {
                @include_once $pt;
                break;
            }
        }
    }

    public function cut_request_from_uri($root_file = '', $request = '')
    {
        if (empty($root_file))
            $root_file = $_SERVER['PHP_SELF'];
        if (empty($request))
            $request = $_SERVER['REQUEST_URI'];

        $request = strtok($request, '?');
        $root_file = dirname($root_file);

        $result = str_replace(($root_file), '', $request);

        return $result;
    }

    public function convert_uri_to_namespace($input)
    {
        return preg_replace('/\//', '\\', $input);
    }

    public function headerGenerator()
    {
        if (!empty($this->content_type) && !empty($this->content_type_charset)) {
            header("Content-Type: {$this->content_type}; charset={$this->content_type_charset}");
        } else if (!empty($this->content_type)) {
            header("Content-Type: {$this->content_type}; ");
        }

        if (!empty($this->content_disposition) && !empty($this->content_disposition_file_name)) {
            header("Content-Disposition: {$this->content_disposition}; filename=\"{$this->content_disposition_file_name}\"");
        } else if (!empty($this->content_disposition)) {
            header("Content-Disposition: {$this->content_disposition};");
        }
    }

    public function make_output($output)
    {
        $this->headerGenerator();

        if (!empty($this->content_type) && !empty($this->output_handler[$this->content_type]) && is_callable($this->output_handler[$this->content_type]))
            call_user_func($this->output_handler[$this->content_type], $output);
        else
            echo $output;
    }

    //<editor-fold desc="Serializing Section">
    private function serializable_data_result()
    {
        return null;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return $this->serializable_data_result();
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        return $this->serializable_data_result();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->serializable_data_result();
    }
//</editor-fold>

    //<editor-fold desc="Input Helper">
    public function read_input()
    {
        return file_get_contents('php://input');
    }

    public function get_input_json()
    {
        $json = json_decode($this->read_input());
        return $json;
    }

    public function get_input_xml()
    {
        $xml = '';
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new Exception($errstr, $errno);
        });

        try {
            $xml = new SimpleXMLElement($this->read_input());
        } catch (Exception $e) {

        }

        restore_error_handler();
        return $xml;
    }

    private function get_input_auto_detect($validation = true)
    {
        $result = $this->get_input_xml();
        if (empty($result))
            $result = $this->get_input_json();
        if (empty($result))
            parse_str($this->read_input(), $result);
        if (empty($result))
            $result = $_POST;
        if (empty($result))
            $result = $_REQUEST;

        if ($validation)
            $result = input_validate($result);
        return (object)$result;
    }

    public function get_input($validation = true)
    {
        return $this->get_input_auto_detect($validation);
    }
    //</editor-fold>
}