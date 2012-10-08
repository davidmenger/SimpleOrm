<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of DBDictionary
 *
 * @author David Menger
 */
class DBDictionary extends Set implements \ArrayAccess {
    
    protected $tablename;
    /**
     *
     * @var \Foundation\Object\DBStored
     */
    protected $parentObject;
    protected $parentFieldName;
    
    protected $keyField;
    protected $integerField;
    protected $string45Field;
    protected $textField;

    protected $singleValueField;




    protected $data;
    


    function __construct($tablename, \Foundation\Object\DBStored $parentObject, $parentFieldName, $keyField = 'key', 
                            $singleValueField = false, $integerField = 'integer', $string45Field = 'string45', $textField = 'text') {
        $this->tablename = $tablename;
        $this->parentObject = $parentObject;
        $this->parentFieldName = $parentFieldName;
        
        $this->keyField = $keyField;
        $this->integerField = $integerField;
        $this->singleValueField = $singleValueField;
        $this->string45Field = $string45Field;
        $this->textField = $textField;
        
        $this->singleValueField = $singleValueField;
        
    }

    /**
     *
     * @return ArrayIterator 
     */
    protected function getResult() {
        if ($this->data == null) {
            if ($this->singleValueField) {
                $sel = $this->getStorage()->select('%n as [key], %n as value', $this->keyField, $this->singleValueField);
            } else {
                $sel = $this->getStorage()->select('%n as [key], IFNULL(%n, IFNULL(%n, %n)) as value', $this->keyField, $this->integerField, $this->string45Field, $this->textField);
            }
            
            $this->data = new \ArrayIterator($sel->from('%n', $this->tablename)->fetchPairs('key', 'value'));
        } 
        return $this->data;
    }
    
    protected function getStorage() {
        return $this->parentObject->getDbContext();
    }


    
    
    public function current() {
        return $this->getResult()->current();
    }

    public function key() {
        return $this->getResult()->key();
    }

    public function next() {
        return $this->getResult()->next();
    }

    public function rewind() {
        return $this->getResult()->rewind();
    }

    public function valid() {
        return $this->getResult()->valid();
    }
    
    public function getDistinct($key, $value) {
        return $this->getResult()->getArrayCopy();
    }

    public function createIterator(array $withData = null) {
        $this->iterator = new \ArrayIterator($withData);
    }
    
    public function count() {
        return $this->getResult()->count();
    }
    
    /**
     * !! NENI APLIKOVAN COUNT ALL
     * @return type 
     */
    public function countAll() {
        return $this->getResult()->count();
    }
    
    public function offsetExists($offset) {
        return $this->getResult()->offsetExists($offset);
    }

    public function offsetGet($offset) {
        return $this->getResult()->offsetGet($offset);
    }

    public function offsetSet($offset, $value) {
        return $this->getResult()->offsetSet($offset, $value);
    }

    public function offsetUnset($offset) {
        return $this->getResult()->offsetUnset($offset);
    }
    
    public function update() {
        
        if ($this->singleValueField == false) {
            $insert = array(
                $this->parentFieldName => array(), $this->keyField => array(), $this->integerField => array(), $this->string45Field => array(), $this->textField => array()
            );
            foreach ($this as $key => $value) {
                $insert[$this->parentFieldName][] = $this->parentObject->getId();
                $insert[$this->keyField][] = $key;
                \Nette\Diagnostics\Debugger::barDump($value, "val:".$key);
                if (is_numeric($value) || is_bool($value)) {
                    $insert[$this->integerField][] = $value;
                    $insert[$this->string45Field][] = null;
                    $insert[$this->textField][] = null;
                } else if (gettype ($value) != "object" &&  strlen($value)<=45) {
                    $insert[$this->integerField][] = null;
                    $insert[$this->string45Field][] = $value;
                    $insert[$this->textField][] = null;
                } else {
                    $insert[$this->integerField][] = null;
                    $insert[$this->string45Field][] = null;
                    $insert[$this->textField][] = $value instanceof \Foundation\Interfaces\IStringStorable ? $value->serialize() : $value;
                }
            }
        } else {
            $insert = array(
                $this->parentFieldName => array(), $this->keyField => array(), $this->singleValueField => array()
            );
            foreach ($this as $key => $value) {
                $insert[$this->parentFieldName][] = $this->parentObject->getId();
                $insert[$this->keyField][] = $key;
                $insert[$this->singleValueField][] =  $value instanceof \Foundation\Interfaces\IStringStorable ? $value->serialize() : $value;
            }
        }
        $this->getStorage()->begin();
        $this->getStorage()->delete($this->tablename)->where('%n = %i', $this->parentFieldName, $this->parentObject->getId())->execute();
        $this->getStorage()->query("INSERT INTO %n %m", $this->tablename, $insert);
        $this->getStorage()->commit();
        
    }

    
}
