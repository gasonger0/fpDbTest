<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        foreach ($args as $k => $v) {
            switch (gettype($v)) {
                case 'string':
                    break;
                case 'integer':
                    break;
                case 'double':
                    break;
                case 'boolean':
                    break;
                case 'array':
                    break;
                case 'NULL':
                    break;
                default:
                    throw new Exception('Incorrect data type');
            }
        }
        preg_match_all("/\?./", $query, $temp);
        preg_match_all("/[{].*.[}]/", $query, $cond);
        if(!empty($cond[0])) {
            preg_match_all("/\?./", $cond[0][0], $var);
            $flag = True; 
            foreach($temp[0] as $k => $item) {
                if($item == $var[0][0] && empty($args[$k])) {
                    $query = str_replace($cond[0][0], '', $query);
                    $flag = False;
                    preg_match_all("/\?./", $query, $temp);
                    break;
                }       
            }
            if($flag) {
                $query = str_replace('{', '', $query);
                $query = str_replace('}', '', $query);
            }
        }
        
        foreach($temp[0] as $k => $v) {
            $pos = strpos($query, $v);
            switch($v[1]){
                case "d":
                    $query = substr_replace($query, $args[$k] ? (int)$args[$k] : $this->skip(), $pos, strlen($v));
                    break;
                case "f":
                    $query = substr_replace($query, $args[$k] ? (float)$args[$k] : $this->skip(), $pos, strlen($v));
                    break;
                case "a":
                    if(array_is_list($args[$k])){
                        $query = substr_replace($query, implode(", ", $args[$k]), $pos, strlen($v));
                    } else {
                        $query = substr_replace($query, $this->assocToString($args[$k]), $pos, strlen($v));
                    }
                    break;
                case "#":
                    if(is_array($args[$k])) {
                        $query = substr_replace($query, $this->arrayToId($args[$k]), $pos, strlen($v));
                    } else {
                        $query = substr_replace($query, "`{$args[$k]}`", $pos, strlen($v));
                    }
                    break;
                case " ":
                    if(gettype($args[$k]) == 'string') {
                        $query = substr_replace($query, "'{$args[$k]}'", $pos, strlen($v[0]));
                    } else if (gettype($args[$k]) == 'bool') {
                        $query = substr_replace($query, (int)$args[$k], $pos, strlen($v[0]));
                    } else {
                        $query = substr_replace($query, $args[$k], $pos, strlen($v[0]));
                    }
            }
        }
        return $query;
    }

    private function assocToString($array) : string {
        $str = '';
        foreach($array as $key => $value) {
            if($value) {
                $str .= "`{$key}` = '{$value}', ";
            } else {
                $str .= "`{$key}` = NULL, ";
            }
        }
        return substr($str, 0, -2);
    }

    private function arrayToId(array $array) : string{
        $str = '';
        foreach($array as $item) {
            $str .= "`{$item}`, ";
        }
        return substr($str, 0, -2);
    }

    public function skip()
    {
        return null;
    }
}
