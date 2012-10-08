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
 * @table _object_cache
 * @key hash
 */
class ObjectCache extends \Foundation\Object\CachedStorage  {
   
    
    /**
     * @key
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $className;
    
    /**
     * 
     * //var StorageDictionary
     * @var array
     */
    public $items;
    
    protected static $_internalcache = null;

    protected static function getClassKey(\Nette\Reflection\ClassType $reflection) {
        return crc32($reflection->getName());
    }

    /**
     * 
     * @param \Nette\Reflection\ClassType $ref
     * @return \Foundation\Set\StorageDictionary
     * @throws \Nette\NotImplementedException
     */
    public static function getAllWithReflection(\Nette\Reflection\ClassType $ref) {
        $all = self::getSingleItemWithReflection($ref);
        return $all->items;
    }
    
    protected static function getSingleItemWithReflection(\Nette\Reflection\ClassType $ref) {
        if (self::$_internalcache == null) self::$_internalcache = new \ArrayObject();
        
        if (!isset(self::$_internalcache[self::getClassKey($ref)])) {
            $all = self::getById(self::getClassKey($ref));
            if (!$all) {
                $all = new ObjectCache();
                $all->hash = self::getClassKey($ref);
                $all->className = $ref->getName();
                $data = call_user_func($ref->getName() .'::getAll', false);
                $keys =  call_user_func($ref->getName() .'::getFields', true);
                if (count($keys)!=1)   throw new \Nette\NotImplementedException("Objectcache needs only one key.");
                $dbRef = array_pop($keys);
                /* @var $dbRef \Foundation\Object\Field */
                $all->items = $data->getDictionary($dbRef->getStorageName());
                $all->create();
            }
            self::$_internalcache[self::getClassKey($ref)] = $all;
        }
        //\Nette\Diagnostics\Debugger::barDump($all, "ALL");
        //throw new \Nette\Application\ApplicationException();
        return self::$_internalcache[self::getClassKey($ref)];
    }

        /**
     * 
     * @param \Nette\Reflection\ClassType $ref
     * @param type $id
     * @return \Foundation\Object\DBStored
     */
    public static function getByIdAndReflection(\Nette\Reflection\ClassType $ref, $id) {
        $all = self::getAllWithReflection($ref);
        return $all->offsetExists($id) ? $all->offsetGet($id) : null;
    }
    
    public static function add(\Foundation\Object\DBStored $object) {
        $all = self::getSingleItemWithReflection($object->getReflection());
        $all->items[$object->getId()] =  ($object);
        $all->update();
    }
    
    public static function save(\Foundation\Object\DBStored $object) {
        $all = self::getSingleItemWithReflection($object->getReflection());
        //\Nette\Diagnostics\Debugger::barDump($all->items[$object->getId()], "INNER");
        //\Nette\Diagnostics\Debugger::barDump($object, "MODIFIED");
        $all->items[$object->getId()] =  $object;
        $all->update();
    }
    
    public static function del(\Foundation\Object\DBStored $object) {
        $all = self::getSingleItemWithReflection($object->getReflection());
        unset($all->items[$object->getId()]);
        $all->update();
    }
    
}
