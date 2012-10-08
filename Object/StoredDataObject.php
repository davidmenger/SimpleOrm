<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Object;

use Foundation\Predicate;

/**
 * Description of StoredDataObject
 *
 */
abstract class StoredDataObject extends DataObject {
    
    
    public static function __callStatic($name, $arguments)
    {
        $refl = new \Nette\Reflection\ClassType( new static );
        //\Nette\Diagnostics\Debugger::barDump($arguments, $name . " - invoke");
        if ($refl->hasMethod($name) && $refl->getMethod($name)->isStatic()) {
            return $refl->getMethod($name)->invoke($name, $arguments);
        } else {
            throw new \Foundation\Exception("Method '$name' not exists in '".$refl->getName()."'");
        }
    }
    
    public static function getAll($predicate = null, $limit = null, $offset = null) {
        return \Nette\Reflection\ClassType::from( new static )->getName();
    }
    
    //abstract public  static function getById($id);

    public abstract function create();
    
    public abstract function update();
    
    /**
     *
     * @param type $data
     * @return StoredDataObject 
     */
    public static function objectWithData($data) {
        if ($data === null) {
            return null;
        }
        //\Nette\Diagnostics\Debugger::barDump($data);
        $obj = self::getReflection()->newInstanceArgs();
        $obj->setData((array) $data);
        return $obj;
    }
    
    
    
}
