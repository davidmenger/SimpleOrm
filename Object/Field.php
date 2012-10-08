<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Object;



/**
 * Description of DataObjectField
 *
 * @author David Menger
 */
class Field extends \Nette\Object {
    
    protected $classReflection; 
    
    protected $annotations;
    
    protected $property;
    
    /**
     *
     * @var \Nette\Reflection\ClassType 
     */
    protected $type;
    
    /**
     *
     * @var Field
     */
    protected $joined;


    /**
     * COLUMN PARAMS
     *  name    string            pokud je specifikovano, pouzije se jako nazev sloupce
     *  type    (INT,VARCHAR, ..) nepovinne - podle rowtype
     *  len     int               povinne pouze pro varchar
     *  null    (true, false)     IS NULL / IS NOT NULL
     *  signed  (true, false)     IS UNSIGNED OR SIGNED
     */

    // SAMPLE ANNOTATION
    
    /**
     * @var int
     * @column('type'=>'lazyobject', 'len'=>7, 'cached'=>'memcached')
     */
    
    public function getInsertSequence() {
        
        
        $conf = array();
        
        // column name
        $conf[] = "[".$this->getStorageName()."]";
        
        // column type
        $len = $this->getStorageLen();
        $conf[] = strtoupper($this->getStorageType()) . ($len === null ? "" : " (". $len .")");
        
        // signed
        if ($this->isNumeric() && $this->isUnsigned()) {
            $conf[] = "UNSIGNED";
        }
        $conf[] = $this->isNull() ? "NULL" : "NOT NULL";
        
        //autoincrement
        if ($this->isAutoIncrement()) {
            $conf[] = "AUTOINCREMENT";
        }
        
        //key
        if ($this->isKey()) {
            $conf[] = "PRIMARY KEY";
        }
        
        //comment
        if ($this->getTitle()) {
            $conf[] = "COMMENT '" .$this->getTitle() ."'";
        }
        
        if ($this->isJoined()) {
            $r = $this->getJoinedFieldAnnotation();
            $conf[] = "REFERENCES [" .$r->getTableName() . "] ([".$r->getStorageName()."])";
        }
        
        return implode(" ", $conf);
    }

    public function getTableName() {
        if (!$this->classReflection->hasAnnotation('table')) {
            throw new \Foundation\Exception('Referenced dataobject has no table annotation!');
        }
        return $this->classReflection->getAnnotation('table');
    }

    function __construct($object, $attr) {
        if ($object instanceof \Nette\Reflection\ClassType) {
            $this->classReflection = $object;
        } else if ($object instanceof DataObject) {
            $this->classReflection = $object->getReflection();
        } else if (is_string($object)) {
            $refl = new \Nette\Reflection\ClassType($object);
            if (!$refl || !$refl->isSubclassOf("\\Foundation\\Object\\DataObject")) {
                throw new \Foundation\Exception("Object has to be subclass of Foundation\DataObject");
            }
            $this->classReflection = $refl;
        }
        if (!$this->classReflection || (!$attr instanceof \Nette\Reflection\Property && !$this->classReflection->hasProperty($attr))) {
            throw new \Foundation\Exception("Object has to be valid string or subclass of Foundation/DataObject and '$attr' has to exists!");
        }
        if ($attr instanceof \Nette\Reflection\Property) {
            $this->property = $attr;
        } else {
            $this->property = $this->classReflection->getProperty($attr);
        }
        $this->annotations = $this->property->getAnnotations();
    }
    
    protected function getTypeClass() {
        if ($this->type===null && isset($this->annotations['var'])) {
            try {
                $ref = \Nette\Reflection\ClassType::from("\\Foundation\\Object\\Types\\".$this->annotations['var'][0]);
                $this->type = $ref->newInstanceArgs();
            } catch (\ReflectionException $e) {
                if (strpos($this->annotations['var'][0], "\\")!==null) {
                    try {
                        $this->type = \Nette\Reflection\ClassType::from($this->annotations['var'][0]);
                    } catch (\ReflectionException $e) {
                        
                    }
                } else {
                    try {
                        $this->type = \Nette\Reflection\ClassType::from($this->classReflection->getNamespaceName() . "\\" .$this->annotations['var'][0]);
                    } catch (\ReflectionException $e) {

                    }
                }
            }
            if ($this->type && !$this->type instanceof Types\type && !($this->type instanceof \Nette\Reflection\ClassType && ($this->type->isSubclassOf("\\Foundation\\Object\\DataObject") || $this->type->isSubclassOf("\\Foundation\\Set\\Set")))) {
                        \Nette\Diagnostics\Debugger::barDump($this->type, "TYPE");
                throw new \Foundation\Exception("Type '".$this->property->getName()."' is not primitive, Foundation DataObject or Set.");
            }
        } 
        if ($this->type === null) {
            $this->type = new Types\type();
        }
        return $this->type;
    }
    
    public function isSetOfData() {
        return $this->getTypeClass() instanceof \Nette\Reflection\ClassType && $this->getTypeClass()->isSubclassOf("\\Foundation\\Set\\Set");
    }
    
    public function isLazy() {
        return isset($this->annotations['lazy']);
    }

    public function set($var) {
        //\Nette\Diagnostics\Debugger::barDump(array('field'=>$this, 'var'=>$var, 'tc'=>  $this->getTypeClass()), "TRYINGTOSET: ".$this->getName());
        if ($var === null) { 
            
        } else if ($this->getTypeClass() instanceof Types\type) {
           if ($this->isEnum()) {
               $e = $this->getEnum();
               if (!isset($e[$var])) {
                   throw new \Foundation\Exception('Value "'.$var.'" is not in Enumeration '.$this->getName());
               }
           } 
           $var = $this->getTypeClass()->set($var);
        } else if ($this->getTypeClass() instanceof \Nette\Reflection\ClassType) {
            if ($this->getTypeClass()->implementsInterface("\Foundation\Interfaces\IStringStorable") && gettype($var) == "string") {
                $instance = $this->getTypeClass()->newInstanceArgs();
                $instance->unserialize($var);
                $var = $instance;
            } else if (get_class($var) != $this->getTypeClass()->getName()) {
                 $data = @unserialize($var);
                 throw new \Nette\NotImplementedException("NENI IMPLEMENTOVAN SET PRO DANY DATOVY TYP!!");
            }
        }
        return $var;
    }
    
    public function isKey() {
        if (!isset($this->annotations['key']) && $this->classReflection->hasAnnotation('key')) {
            $keys = explode(' ', $this->classReflection->getAnnotation('key'));
            return in_array($this->property->getName(), $keys);
        } else {
            return isset($this->annotations['key']);
        }
    }
    
    public function isAutoIncrement() {
        //\Nette\Diagnostics\Debugger::barDump($this->annotations , "IS AK");
        if (isset($this->annotations['key'], $this->annotations['key'][0]) && $this->isKey()) {
            return ($this->annotations['key'][0] === "autoincrement" && $this->annotations['key'][0] !== true);
        } else {
            return false;
        }
    }
    
    public function getTitle() {
        if (isset($this->annotations['title'], $this->annotations['title'][0])) {
            return $this->annotations['title'][0];
        } else {
            return null;
        }
    }
    
    public function getName() {
        return $this->property->getName();
    }

    public function getStorageType() {
        $a = $this->getColAnnotation();
        if (isset($a->type)) {
            return $a->type;
        } else if ($this->getTypeClass() instanceof Types\type) {
            return $this->getTypeClass()->getDbType();
        } else {
            throw new \Foundation\Exception("Col type is not specified for '".$this->getName()."'");
        }
    }
    
    public function isNumeric() {
        $t = $this->getStorageType();
        if ($t instanceof Types\type) {
            return \Nette\Reflection\ClassType::from($t)->hasAnnotaton('unsigned');
        } else if ($this->isJoined()) {
            return $this->getJoinedFieldAnnotation()->isNumeric();
        } 
    }


    public function isUnsigned() {
        $a = $this->getColAnnotation();
        if ($this->isJoined()) {
            return $this->getJoinedFieldAnnotation()->isUnsigned();
        } else if (isset($a->signed)) {
            return $a->signed ? false : true;
        } else {
            return true;
        }
    }


    public function isNull() {
        $a = $this->getColAnnotation();
        if (isset($a->null) || (isset($this->annotations['null'], $this->annotations['null'][0]) && $this->annotations['null'][0])) {
            return $a->null ? true : false;
        } else if ($this->isJoined()) {
            return $this->getJoinedFieldAnnotation()->isNull();
        } else {
            return false;
        }
    }

    public function getStorageLen() {
        $a = $this->getColAnnotation();
        if ($this->isJoined()) {
            return $this->getJoinedFieldAnnotation()->getStorageLen();
        } else if (isset($a->len)) {
            return $a->len;
        } else if ($this->getStorageType() == "varchar") {
            return 120;
        } else if ($this->getTypeClass() instanceof Types\bool) {
            return 1;
        } else {
            return null;
        }
    }
    
    public function getStorageName() {
        $a = $this->getColAnnotation();
        if (isset($a->name[0])) {
            return $a->name[0];
        } else {
            return $this->getName();
        }
        throw new \Foundation\Exception("Cant resolve column name!");
    }

    public function getStorageValue($var) {
        if ($var === null) {
            return null;
        } else if ($this->getTypeClass() instanceof Types\type) {
            return $this->getTypeClass()->set($var);
        } else if ($this->getTypeClass() instanceof \Nette\Reflection\ClassType) {
            if ($var instanceof StoredDataObject) {
                $k = $var->getId();
                if (count($k) != 1) {
                    throw new \Foundation\Exception('There are no or more then one key in "'.$this->getTypeClass()->getName().'" class.');
                }
                return array_pop($k);
            } else if ($var instanceof DataObject) {
                return serialize($var);
            } else {
                return $var;
            }
        }
    }
    
    public function isJoined() {
        return isset($this->annotations['join'], $this->annotations['join'][0]);
    }
    
    public function getJoinedTypeName() {
        if ($this->isJoined()) {
            return $this->annotations['join'][0];
        } else {
            throw new \Foundation\Exception("This field is not 'joined'");
        }
    }
    
    /**
     * 
     * @return \Nette\Reflection\ClassType
     */
    public function getJoinedClassReflection() {
        return call_user_func($this->getJoinedTypeName() . "::getReflection");
    }

    public function getJoinedClassFields() {
        $classtype = $this->getJoinedClassFields();
        return $classtype->getMethod('getFields')->invoke($classtype->getName(), array('justKeys'=>true));
    }


    public function isEnum() {
        return isset($this->annotations['enum']);
    }
    
    public function getEnum() {
        if (isset($this->annotations['enum'], $this->annotations['enum'][0])) {
            $ret = (array) $this->annotations['enum'][0];
            if (is_array($ret)) {
                if (isset($ret[0]) && $ret[0]===0) unset($ret[0]);
                return (array) $ret;
            } else {
                throw new \Foundation\Exception($this->getName().' enum should be array.');
            }
        } else {
            throw new \Foundation\Exception($this->getName().' is not enum type');
        }
    }

    /**
     *
     * @return Field 
     */
    protected function getJoinedFieldAnnotation() {
        if ($this->joined === null) {
            if ($this->isJoined()) {
                $classtype = $this->getJoinedClassReflection();
                //\Nette\Diagnostics\Debugger::barDump($classtype, "CTYPE");
                $keys = DataObject::getFieldsWithReflection($classtype, true);
                if (count($keys) != 1) {
                    throw new \Foundation\Exception('There are no or more then one key in "'.$this->getTypeClass()->getName().'" class.');
                } else {
                    $this->joined = array_pop($keys);
                }
            } else {
                throw new \Foundation\Exception("This field is not 'joined'");
            }
        }
        return $this->joined;
    }
    
    public function getJoinStorageName() {
        return $this->getJoinedFieldAnnotation()->getStorageName();
    }


    protected function getColAnnotation() {
        if ($this->isJoined() && $this->getTypeClass() instanceof \Nette\Reflection\ClassType) {
            return $this->getJoinedFieldAnnotation()->getColAnnotation();
        } else if (isset($this->annotations['column'])) {
            return $this->annotations['column'];
        } else {
            return new \stdClass();
        }
    }
    
    

    public function dibiModifier() {
        $var = '%s';
        if (isset($this->annotations['var'])) {
            try {
                $ref = \Nette\Reflection\ClassType::from("\\Foundation\\Types\\".$this->annotations['var']);
                $instance = $ref->newInstanceArgs();
                if ($instance instanceof Types\type) {
                    $var = $instance->getDibiWildCard();
                }
            } catch (ReflectionException $e) {
                
            }
        }
        return $var;
    }
    
    
    
    
}
