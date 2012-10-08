<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of StorageDictionary
 *
 * @author David Menger
 */
class StorageDictionary extends Dictionary implements \Foundation\Interfaces\storageSet {
   
    public function getStorageSaveData() {
        if ($this->iterator) {
            return $this->iterator->getArrayCopy();
        } else {
            return array();
        }
    }

    public static function getWithStorageObject(\Foundation\Object\StoredDataObject $object, $storageLoadedData) {
        $obj = self::getReflection()->newInstanceArgs(array('owner'=>$object));
        $obj->createIterator($storageLoadedData);
        return $obj;
    }

    
}
