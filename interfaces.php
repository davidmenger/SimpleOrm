<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Interfaces;
/**
 * Description of interfaces
 *
 * @author David Menger
 */
interface dbSet {
    
    public function isSavedInDb();
    public function getDbSaveData();
    public static function getWithDBObject(\Foundation\Object\DBStored $object, $dbLoadedData);
}

interface storageSet {
    
    public function getStorageSaveData();
    public static function getWithStorageObject(\Foundation\Object\StoredDataObject $object, $storageLoadedData);
}

interface IStringStorable extends \Serializable {
    public function jsonSerialize();
    //public function serialize();
    //public function unserialize($serialized);
}

interface IIterableDataObjectSet extends \Iterator {
    
    public function count();
    /**
     * !! NENI APLIKOVAN COUNT ALL
     * @return type 
     */
    public function countAll();
    public function getFirst();
    
}

interface IBaseDataTranslator {

    public function translate(\Foundation\Object\DataObject $item, $var);
    public function getTitle();
}

interface IExpandable {
    public static function newForNamespace($namespace, \Nette\Reflection\ClassType $refl = null);
}