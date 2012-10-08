<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of DbSet
 *
 * @author David Menger
 * //key (optional)
 * //value (required)
 * //storage
 */
class DbSet extends Set  {
    
    protected $tablename;
    /**
     *
     * @var \Foundation\Object\DBStored
     */
    protected $parentObject;
    protected $parentFieldName;
    
    /**
     *
     * @var \Nette\Reflection\ClassType
     */
    protected $valueFieldReflection;
    protected $valueType;
    protected $arrayOfKeys;
    
    
    protected $data;


    function __construct($tablename, \Foundation\Object\DBStored $parentObject, $parentFieldName, \Nette\Reflection\ClassType $valueFieldReflection, $valueType, $arrayOfKeys = null) {
        $this->tablename = $tablename;
        $this->parentObject = $parentObject;
        $this->parentFieldName = $parentFieldName;
        $this->valueFieldReflection = $valueFieldReflection;
        $this->valueType = $valueType;
        $this->arrayOfKeys = $arrayOfKeys;
    }

    /**
     *
     * @return DBResultSet 
     */
    protected function getResult() {
        if ($this->data == null) {
            $keys = \Foundation\Object\DataObject::getFieldsWithReflection($this->valueFieldReflection, true);
            $key = array_pop($keys);
            /* @var $key \Foundation\Object\Field */

            $join = new \Foundation\Predicates\pJoin(array($this->tablename.".".$this->parentFieldName => $this->parentObject->getId()));

            $join->setJoinProperties($this->tablename, $this->valueFieldReflection->getAnnotation('table'), $this->valueType, $key->getStorageName());
            $instance = $this->valueFieldReflection->newInstanceArgs();
            $this->data = $instance->getAll($join);
        } 
        return $this->data;
    }
    
    protected function getStorage() {
        return $this->parentObject->getDbContext();
    }

     /**
     *
     * @param \Foundation\Object\DBStored $object
     * @return DbSet 
     */
    public function add(\Foundation\Object\DataObject $object) {
         $this->getStorage()->query("INSERT IGNORE INTO %n %v", $this->tablename, 
                         array($this->valueType => $object->getId(), $this->parentFieldName => $this->parentObject->getId()));
         return $this;
    }
    
    public function contains(\Foundation\Object\DataObject $object) {
        return !!$this->getStorage()->select("%n", $this->valueType)->from($this->tablename)
                ->where('%and', array($this->valueType => $object->getId(), $this->parentFieldName => $this->parentObject->getId()))->fetchSingle();
    }

        /**
     *
     * @param type $object 
     */
    public function remove(\Foundation\Object\DataObject $object) {
        $this->getStorage()->delete($this->tablename)
                ->where('%and', array($this->valueType => $object->getId(), $this->parentFieldName => $this->parentObject->getId()))->execute();
                
        return $this;
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
        return $this->getResult()->getDistinct($key, $value);
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


    
}
