<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Object;

use \Nette\ObjectMixin;
/**
 * Description of DataObject
 * 
 * @key _default_object_key
 * @author David Menger
 */
class DataObject extends \Nette\Object implements \ArrayAccess, \Iterator, \IReleasable {
    
    protected $_data = array();
    protected $_dataIterator;
    protected static $r_cache = array();


    function __construct($id = null) {
        if ($id !== null) $this->setId ($id);
    }


    protected static $annotationCache = array();


    public function setData(array $data) {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
    }
    
    public function getData() {
        $data = $this->_data;
        foreach ($this->getReflection()->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflection) {
            if (!$reflection->isPublic())    continue;
            $data[$reflection->getName()] = $this[$reflection->getName()];
        }
        return $data;
    }

    public function getId() {
        if ($this->getReflection()->hasAnnotation("key")) {
            return $this[$this->getReflection()->getAnnotation("key")];
        } else {
            $annotations = array();
            foreach ($this->getReflection()->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->hasAnnotation('key')) {
                    $annotations[$property->getName()] = $this[$property->getName()];
                }
            }
            if (count($annotations) == 0) {
                throw new \Foundation\Exception("Dataobject has no KEY annotation");
            } else {
                return count($annotations) == 1 ? array_pop($annotations) : $annotations;
            }
        }
    }
    
    public function setId($id) {
        if ($this->getReflection()->hasAnnotation("key") && !is_array($id)) {
            $this[$this->getReflection()->getAnnotation("key")] = $id;
        } else {
            $annotations = array();
            foreach ($this->getReflection()->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->hasAnnotation('key')) {
                    $annotations[] = $property->getName();
                }
            }
            if (count($annotations) == 0) {
                throw new \Foundation\Exception("Dataobject has no ID annotation");
            } else if (count($annotations) == 1 && !is_array($id)) {
                $this[$annotations[0]] = $id;
            } else {
                foreach ($annotations as $name) {
                    if (isset($id[$name])) {
                        $this[$name] = $id[$name];
                    } else {
                        throw new \Foundation\Exception('Key is not complete');
                    }
                }
            }
        }
    }

    public function offsetExists($offset) {
        return (ObjectMixin::has($this, $offset) || isset($this->_data[$offset]));
    }

    public function offsetGet($offset) {
        $r = $this->getReflection();
        if ($r->hasProperty($offset) && $r->getProperty($offset)->isPublic()) {
            return $this->$offset;
        } else {
            return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
        }
    }

    public function offsetSet($offset, $value) {
        $fields = $this->getFields();
        if (isset($fields[$offset])) {
             $this->$offset = $fields[$offset]->set($value);
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        if ( ObjectMixin::has($this, $offset) ) {
            unset ($this->$offset);
        } else {
            unset($this->_data[$offset]);
        }
    }
    
    public function __call($name, $args) {
        
        parent::__call($name, $args);
    }

     /**
     *
     * @return Iterator 
     */
    protected function getIterator() {
        if ($this->_dataIterator == null) {
            $this->_dataIterator = new \Nette\Iterators\CachingIterator($this->getData());
        }
        return $this->_dataIterator;
    }

    public function current() {
        return $this->getIterator()->current();
    }

    public function key() {
        return $this->getIterator()->key();
    }

    public function next() {
        $this->getIterator()->next();
    }

    public function rewind() {
        $this->_dataIterator = null;
        $this->getIterator()->rewind();
    }

    public function valid() {
        return $this->getIterator()->valid();
    }

    /**
     *
     * @param type $attr
     * @return Field 
     */
    public function getFieldReflection($fieldName) {
        foreach (self::getFields() as $key => $value) {
            if ($fieldName == $key) {
                return $value;
            }
        }
        throw new \Foundation\Exception("Field not found");
    }
    
    /**
     *
     * @return Field <array>
     */
    public static function getFields($justKeys = false) {
        $ref = static::getReflection();
        return self::getFieldsWithReflection($ref, $justKeys);
    }
    
    public static function getFieldsWithReflection(\Nette\Reflection\ClassType $ref, $justKeys = false) {
        if (!isset (self::$annotationCache[$ref->getName()])) {
            foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                self::$annotationCache[$ref->getName()][$property->getName()] = new Field($ref, $property);
            }
        }
        if ($justKeys) {
            $ret = array(); 
            foreach (self::$annotationCache[$ref->getName()] as $name => $annotation) {
                if ($annotation->isKey()) {
                    $ret[$name] = $annotation;
                }
            }
            return $ret;
        } else {
            return isset (self::$annotationCache[$ref->getName()]) ? self::$annotationCache[$ref->getName()] : array();
        }
    }
    
    /**
     *
     * @param \Nette\Reflection\ClassType $withClassReflection
     * @return string
     */
    public static function getTypeHash(\Nette\Reflection\ClassType $withClassReflection = null) {
        $ref = $withClassReflection ? $withClassReflection : static::getReflection();
        $props = $ref->getProperties();
        $text = $ref->hasAnnotation('cached')?"X":"0";
        foreach ($props as $prop) {
            $text .= $prop->getName().($prop->hasAnnotation('lazy')?"M":"0");
        }
        //\Nette\Diagnostics\Debugger::barDump(array('hash:'=>$text), "HFOR: ".$ref->getName());
        return md5($text);
    }
    
    /**
	 * Access to reflection.
	 * @return Nette\Reflection\ClassType
	 */
	public static function getReflection()
	{
        $n = get_called_class();
        if (!isset(self::$r_cache[$n])) {
            self::$r_cache[$n] = new \Nette\Reflection\ClassType($n);
        }
		return self::$r_cache[$n];
	}
    
    /**
     * 
     * @param type $attr
     * @param type $value
     * @return \Foundation\Object\DataObject
     */
    public function setAttr($attr, $value) {
        $this[$attr] = $value;
        return $this;
    }
    
    public function getAttr($attr) {
        return $this[$attr];
    }
    
    public function release() {
        if (isset($this->_data)) {
            foreach ($this->_data as $key => $value) {
                unset($this[$key]);
            }
            unset($this->_data);
        }
        foreach (get_object_vars($this) as $key => $value) {
            unset($this->$key);
        }
        if (isset($this->_dataIterator)) unset($this->_dataIterator);
        return $this;
    }
    
}
