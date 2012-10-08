<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of SerializableDictionary
 *
 * @author David Menger
 */
class SerializableDictionary extends Dictionary implements \Foundation\Interfaces\IStringStorable {
    
    public function jsonSerialize() {
        $ret =  $this->iterator->getArrayCopy();
        //$ret["_json_type_"] = \Nette\Reflection\ClassType::from($this)->getName();
        return $ret;
    }
    
    public function jsonUnSerialize($decodedJsonArray) {
        $set = array();
        foreach ($decodedJsonArray as $key => $value) {
            if (is_array($value)) {
                if (!iss)
                $obj = new SerializableDictionary();
                $obj->jsonUnSerialize($value);
                $set[$key] = $obj;
            } else {
                $set[$key] = $value;
            }
        }
        $this->iterator = new \ArrayIterator($set);
    }
    
    public function serialize() {
        $array = array();
        foreach ($this as $key => $value) {
            if ($value instanceof \Foundation\Interfaces\IStringStorable) {
                $value = $value->jsonSerialize();
            }
            $array[$key] = $value;
        }
        return json_encode($array);
    }

    public function unserialize($serialized) {
        $array = json_decode($serialized, true);
        $this->jsonUnSerialize($array);
    }
    
    protected function validateInputValue($value, $key = null) {
        if (!in_array(gettype($value), \Foundation\CONS::$STATIC_TYPES)) {
            throw new \Foundation\BadInputException("Key shold be scalar type");
        }
        if (!in_array(gettype($value), \Foundation\CONS::$STATIC_TYPES) && !$value instanceof SerializableDictionary) {
            throw new \Foundation\BadInputException("Value of '$key' should be scalar or SerializableDictionary");
        }
    }


    
}
