<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Object;
/**
 * Description of Memcached
 *
 * private tables: _storage_indexes
 * 
 * @author David Menger
 */
class CachedStorage extends StoredDataObject {
    
    protected static $storage;
    
    /**
     *
     * @return Nette\Caching\IStorage 
     */
    protected static function getStorage() {
        if (!self::$storage) {
            if (!self::getReflection()->hasAnnotation('storage')) {
                throw new \Foundation\Exception('CachedStorageObject has to have storage annotation');
            }
            self::$storage = \Nette\Environment::getContext()->getService(self::getReflection()->getAnnotation('storage'));
        }
        return self::$storage;
    }
    
    protected static function getStorageKey($id) {
        return self::getReflection()->getAnnotation('table') . "." . (is_array($id) ? implode(".", $id) : $id);
    }

    protected function getCleanObject() {
        $obj = clone $this;
        $obj->cleanForSaveProcess();
        return $obj;
    }
    
    public function cleanForSaveProcess() {
        $this->_data = array();
        foreach ($this->getReflection()->getProperties(\Nette\Reflection\Property::IS_PUBLIC) as $property) {
            /* @var $property  \Nette\Reflection\Property */
           if ($property->getValue($this) instanceof DataObject) {
               if (is_array($property->getValue($this)->getId())) {
                   throw new \Nette\NotImplementedException("Zatím nejsou vyřešené multiple id relations");
               }
               $property->setValue($this, $property->getValue($this)->getId());
           } else if ($property->getValue($this) instanceof Set) {
               
           }
        }
    }

    public function create() {
        self::getStorage()->write( $this->getStorageKey($this->getId()), $this, array());
        
    }
    
    /**
     *
     * @param type $id
     * @return CachedStorage 
     */
    public static function getById($id) {
        return self::getStorage()->read(self::getStorageKey($id));
    }

    public function update() {
        self::getStorage()->write( $this->getStorageKey($this->getId()), $this, array());
        return $this;
    }
    
    public function remove() {
        self::getStorage()->remove($this->getStorageKey($this->getId()));
        return $this;
    }

    
}
