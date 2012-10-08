<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of Dictionary
 *
 * @author David Menger
 */
class Dictionary extends Set implements \ArrayAccess {
    
   

    public function offsetExists($offset) {
        return $this->iterator->offsetExists($offset);
    }

    public function offsetGet($offset) {
        return $this->iterator->offsetGet($offset);
    }

    public function offsetSet($offset, $value) {
        $this->validateInputValue($value, $offset);
        return $this->iterator->offsetSet($offset, $value);
    }

    public function offsetUnset($offset) {
        return $this->iterator->offsetUnset($offset);
    }
    
    
    protected function validateInputValue($value, $key = null) {
        
    }
    
}
