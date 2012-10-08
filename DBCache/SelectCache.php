<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\DBCache;
/**
 * Description of SelectCache
 *
 * @storage memCachedDb
 * //@storage cacheStorage
 * @table _select_cache
 * @key className
 */
class SelectCache extends \Foundation\Object\CachedStorage {
    
    /**
     *
     * @var string
     * @key
     */
    public $className;
    
    /**
     *
     * @var string
     */
    public $ownSelect;
    
    /**
     * 
     * @var StorageDictionary
     */
    public $joins;
    
    /**
     *
     * @var string
     */
    public $hash;
    
    /**
     *
     * @param \Nette\Reflection\ClassType $reflection
     * @return SelectCache 
     */
    public static function getOrCreateWithObjectReflection(\Nette\Reflection\ClassType $reflection) {
        $obj = self::getById($reflection->getName());
        $regenerate = false;
        if (!\Nette\Environment::isProduction() && $obj && $obj->hash != \Foundation\Object\DataObject::getTypeHash($reflection)) {
            $regenerate = true;
        }
        if (!\Nette\Environment::isProduction() && $obj) {
            //$fields = \Foundation\Object\DataObject::getFieldsWithReflection($reflection);
            foreach ($obj->joins as $classname => $value) {
                if ($value['hash'] != \Foundation\Object\DataObject::getTypeHash(new \Nette\Reflection\ClassType($classname))) {
                    $regenerate = true;
                    break;
                }
            }
        }
        if ($regenerate) {
            $obj->createOwnSelectWithReflection($reflection);
            $obj->generateJoinsWithReflection($reflection);
            $obj->hash = \Foundation\Object\DataObject::getTypeHash($reflection);
            $obj->update();
        } else if (!$obj) {
            $obj = new SelectCache();
            $obj->className = $reflection->getName();
            $obj->createOwnSelectWithReflection($reflection);
            $obj->generateJoinsWithReflection($reflection);
            $obj->hash = \Foundation\Object\DataObject::getTypeHash($reflection);
            $obj->create();
        }
        
        //\Nette\Diagnostics\Debugger::barDump(array('obj'=>$obj, 'reg'=>$regenerate), "haha?");
        //$obj->joins = array();
        //$obj->update();
        return $obj;
    }
    
    


    public function createOwnSelectWithReflection(\Nette\Reflection\ClassType $reflection) {
        $fields = \Foundation\Object\DataObject::getFieldsWithReflection($reflection);
        $select = array();
        foreach ($fields as $field) {
            /* @var $field Field */
            if ($field->isSetOfData() && !$field->isLazy()) {
                /* @implement Lazy data sets in select statement */ 
                throw new \Nette\NotImplementedException("Sets are not implemented");
            } else if (!$field->isSetOfData()) {
                $select[] = "[".$reflection->getAnnotation('table'). "." .$field->getStorageName() . "]" . ($field->getName() == $field->getStorageName() ? "" : " as [".$field->getName()."]");
            };
        }
        $this->ownSelect = implode(", ", $select);
    }
    
    public function generateJoinsWithReflection(\Nette\Reflection\ClassType $reflection) {
        $fields = \Foundation\Object\DataObject::getFieldsWithReflection($reflection);
        $currentlyJoinedTables = array();
        $this->joins = array();
        foreach ($fields as $field) {
            /* @var $field Field */
           if ($field->isJoined() && !$field->isLazy() && $field->getJoinedClassReflection()->getAnnotation('cached') !== TRUE ) { // && $field->isSetOfData()
                $joinreflection  = $field->getJoinedClassReflection();
                \Nette\Diagnostics\Debugger::barDump($field, "REF?");
                $joinfields = \Foundation\Object\DataObject::getFieldsWithReflection($joinreflection);
                if ($joinreflection instanceof \Nette\Reflection\ClassType && $joinreflection->isSubclassOf("\\Foundation\\Object\\DBStored")) {
                    $joinTabAlias = ($field->getName() == $joinreflection->getAnnotation('table') . '_id') ? $joinreflection->getAnnotation('table') : $field->getName()."_".$joinreflection->getAnnotation('table');
                    $table = "[".$joinreflection->getAnnotation('table')."] as [".$joinTabAlias."]";
                    $on    = "[".$joinTabAlias.".". $field->getJoinStorageName() ."] = [".$reflection->getAnnotation('table').".".$field->getStorageName()."]";
                    $select = array();
                    $joinTrasnlation = array();
                    foreach ($joinfields as $joinfield) {
                        /* @var $joinfield Field */
                        if ($field->isSetOfData() && !$field->isLazy()) {
                            /* @implement Lazy data sets in join statement */ 
                        } else if (!$field->isSetOfData()) {
                            $select[] = "[".$joinTabAlias. "." .$joinfield->getStorageName() . "] as [r_".$joinTabAlias."_".$joinfield->getName()."]";
                        }
                        $joinTrasnlation[$joinfield->getName()] = "r_".$joinTabAlias."_".$joinfield->getName();
                    }
                    $select = implode(", ", $select);
                    $this->joins[$joinreflection->getName()] = array(
                        'table' => $table,
                        'on' => $on,
                        'select' => $select,
                        'translation' => $joinTrasnlation,
                        'hash' => \Foundation\Object\DataObject::getTypeHash($joinreflection)
                    ); 
                }
           }
        }
        
    }
    
}
