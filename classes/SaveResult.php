<?php

class SaveResult
{
    public $result = false;
    public $errors = array();


    public function isValid()
    {
        return count($this->errors) <= 0;
    }
}