<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\DBCache;
/**
 * Description of SessionObjectCache
 *
 * @author David Menger
 */
class SessionObjectCache extends \Nette\Object {
    
    protected static $cache = array();


    protected static function getKey($id) {
        return is_array($id) ? crc32(serialize($id)) : $id;
    }
    
    protected static function getClassKey(\Nette\Reflection\ClassType $reflection) {
        return crc32($reflection->getName());
    }

    public static function &getObjectWithReflectionAndId(\Nette\Reflection\ClassType $reflection, $id, &$updateData = null) {
        //\Nette\Diagnostics\Debugger::barDump(array('r'=>$reflection, 'id'=>$id, 'classKey'=>self::getClassKey($reflection), 'key'=>self::getKey($id)), "GETTER");
        if (self::hasObjectWithReflectionAndId($reflection, $id)) {
            $o = self::$cache[self::getClassKey($reflection)][self::getKey($id)];
            if ($updateData) {
                $o->setData((array) $updateData);
            }
            return $o;
        } else {
            return null;
        }
    }
    
    public static function hasObjectWithReflectionAndId(\Nette\Reflection\ClassType $reflection, $id) {
        return isset(self::$cache[self::getClassKey($reflection)]) && isset(self::$cache[self::getClassKey($reflection)][self::getKey($id)]);
    }
    
    public static function addObjectWithReflectionAndId(\Foundation\Object\DataObject $object) {
        $reflection = $object->getReflection();
        if (isset(self::$cache[self::getClassKey($reflection)])) {
            self::$cache[self::getClassKey($reflection)] = array();
        }
        self::$cache[self::getClassKey($reflection)][self::getKey($object->getId())] = $object;
        return $object;
    }
    
    public static function unsetObject(\Foundation\Object\DataObject $object) {
        $reflection = $object->getReflection();
        if (isset(self::$cache[self::getClassKey($reflection)], self::$cache[self::getClassKey($reflection)][self::getKey($object->getId())])) {
            release(self::$cache[self::getClassKey($reflection)][self::getKey($object->getId())]);
        }
    }
    
}
