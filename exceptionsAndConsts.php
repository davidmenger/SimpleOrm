<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation;
/**
 * Description of exceptions
 *
 * @author David Menger
 */
class Exception extends \Exception  {
    
}

class PredicateException extends Exception {
    
}

class BadInputException extends Exception {
    
}

class CONS {
    
    public static $STATIC_TYPES = array("boolean", "integer", "double", "string");
    public static $STATIC_TYPES_W_ARRAY = array("boolean", "integer", "double", "string", "array");
    
    
    
}
