<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Object\Types;
/**
 * Description of types
 *
 * @author David Menger
 */
class type {
    public function getDbType() {
        return "varchar";
    }

    public function getDibiWildCard() {
        return "%s";
    }
    
    public function set($var) {
        return $var;
    }
}

/**
 * @numeric
 */
class int extends type {
    public function getDibiWildCard() {
        return "%i";
    }

    public function getDbType() {
        return "int";
    }
    
    public function set($var) {
        return intval(preg_replace('[^0-9]', '', $var));
    }
}

class string extends type {
    public function getDibiWildCard() {
        return '%s';
    }

    public function getDbType() {
        return "varchar";
    }
    
    public function set($var) {
        return trim($var);
    }

}

/**
 * @numeric
 */
class float extends type {
    public function getDibiWildCard() {
        return '%f';
    }

    public function getDbType() {
        return "float";
    }
    
    public function set($var) {
        return floatval(preg_replace('/[^[0-9\.]/', '', $var));
    }
}

/**
 * @numeric
 */
class bool extends type {
    public function getDibiWildCard() {
        return '%b';
    }

    public function getDbType() {
        return "tinyint";
    }
    
    public function set($var) {
        return $var ? true : false;
    }
}

class DateTime extends type {
    public function getDibiWildCard() {
        return '%t';
    }

    public function getDbType() {
        return "datetime";
    }
    
    public function set($var) {
        if ($var instanceof \DateTime) {
            return $var;
        } else {
            return new \DateTime($var);
        }
    }

}

class binary extends type {
    public function getDbType() {
        return "BLOB";
    }

    public function getDibiWildCard() {
        return "%bin";
    }

    public function set($var) {
        return $var;
    }
}
