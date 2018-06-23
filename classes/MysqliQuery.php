<?php
/**
 * Created by PhpStorm.
 * User: mh
 * Date: 06/20/2018
 * Time: 08:54
 */

class MysqliQuery
{
    public function insert($tableName, $columnsAndValueArray)
    {
        $cols = $vals = '';

        foreach ($columnsAndValueArray as $k => $v) {
            $cols .= " `{$k}`,";
            if (($v == '' || $k == null) && strval($k) !== '0') {
                $vals .= ' NULL,';
            } else {
                $vals .= " '{$v}',";
            }
        }

        $cols = rtrim($cols, ',');
        $vals = rtrim($vals, ',');

        return "INSERT INTO `{$tableName}` ({$cols}) VALUES ({$vals})";
    }

    public function update($tableName, $columnsAndValueArray, $condition)
    {
        $temp = '';
        foreach ($columnsAndValueArray as $k => $v) {
            $val = ' NULL';
            if (($v != '' && $k != null) || strval($k) === '0') {
                $val = " '{$v}'";
            }

            $temp .= " `{$k}` = {$val},";
        }

        $temp = rtrim($temp, ',');

        return "UPDATE  `{$tableName}`  SET {$temp} WHERE $condition ";
    }
}