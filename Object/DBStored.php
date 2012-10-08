<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Object;

use \dibi;
/**
 * Description of DBStored
 *
 * @author David Menger
 */
abstract class DBStored extends StoredDataObject implements \Serializable {
    
    private $referenceCount = 0;


    private $_ownObjectCache = array();

    public function serialize() {
        if (!isset($this->_data)) var_dump (debug_backtrace());
        return serialize($this->getData());
    }

    public function unserialize($serialized) {
        foreach (unserialize($serialized) as $key => $value) {
            $this[$key] = $value;
        }
    }
    
    /**
     *
     * @return \DibiConnection 
     */
    public static function getDbContext() {
        $r = self::getReflection();
        if (!$r->hasAnnotation('table') || !$r->hasAnnotation('storage')) {
            throw new \Nette\Application\ApplicationException("Class has no table annotation");
        }
        return \Nette\Environment::getService($r->getAnnotation('storage'));
    }


     /**
     *
     * @param \Foundation\Predicates\Base $predicate
     * @param type $limit
     * @param type $offset
     * @return \Foundation\Set\DBResultSet 
     */
    public static function getAll($predicate = null, $limit = null, $offset = null) {
        if (!$predicate instanceof \Foundation\Predicates\Base) {
            $p = new \Foundation\Predicates\pAnd(is_array($predicate) ? $predicate : ($predicate instanceof DBStored ? array($predicate) : array()));
        } else {
            $p = $predicate;
        }
        $r = self::getReflection();
        if (!$r->hasAnnotation('table') || !$r->hasAnnotation('storage')) {
            throw new \Nette\Application\ApplicationException("Class has no table annotation");
        }
        
        if ($r->getAnnotation('cached') === TRUE && $p->isEmpty() && $predicate !== false) {
            
            return \Foundation\DBCache\ObjectCache::getAllWithReflection($r);
        } else {
            $selectData = \Foundation\DBCache\SelectCache::getOrCreateWithObjectReflection($r);
            $sel = self::getDbContext()->select($selectData->ownSelect)->from("%n", $r->getAnnotation('table'));
            if (!$p || $p->getLazy() !== TRUE) {
                $miss = $p->getLazy() === FALSE ? array() : $predicate->getLazy();
                foreach ($selectData->joins as $info) {
                    $x = null;
                    preg_match("/^\[([a-zA-Z0-9_]+)\]/", $info['table'], $x);
                    if (!in_array($x[1], $miss))
                            $sel->leftJoin($info['table'])->on($info['on'])->select($info['select']);
                }
            }
            if ($p!=null) {
                $p->proceed($sel, $r);
            }
            $countable = clone $sel;
            return new \Foundation\Set\DBResultSet($sel->getIterator($offset, $limit), $r, $countable);
        }
    }
    
    /**
     *
     * @param type $id
     * @return DBStored 
     */
    public static function getById($id) {
        if ($id === null)            throw new \Nette\Application\ApplicationException("ID cannot be NULL");
        $fields = array();
        $r = self::getReflection();
        if (\Foundation\DBCache\SessionObjectCache::hasObjectWithReflectionAndId($r, $id)) {
            return \Foundation\DBCache\SessionObjectCache::getObjectWithReflectionAndId($r, $id);
        }
        if (!$r->hasAnnotation('table') || !$r->hasAnnotation('storage')) {
            throw new \Nette\Application\ApplicationException("Class has no table annotation");
        }
        if ($r->getAnnotation('cached') === TRUE ) {
            return \Foundation\DBCache\ObjectCache::getByIdAndReflection($r, $id);
        } else {
            $lastname = null;
            foreach (self::getFields(true) as $field) {
                $lastname = $field->getName();
                $fields[$field->getName()] = $field->getStorageName();
            }
            if (count($fields) != (is_array($id) ? count($id) : 1)) {
                throw new \Foundation\Exception("ID is incomplete");
            } else {
                $id = is_array($id) ? $id : array($lastname => $id); 
                $predicateArray = array();
                \Nette\Diagnostics\Debugger::barDump($id, "ID");
                foreach ($fields as $name => $storageName) {
                    if (!isset($id[$name])) throw new \Foundation\Exception("ID is incomplete");
                    $predicateArray[$r->getAnnotation('table').".".$storageName] = $id[$name];
                }
            }
            $o = self::getAll(\Foundation\Predicates\pAnd::init($predicateArray), 1)->getFirst();
            if ($o) {
                \Foundation\DBCache\SessionObjectCache::addObjectWithReflectionAndId($o);
            }
            return $o;        
        }
    }

    /**
     *
     * @return DBStored 
     */
    public function create() {
        $r = $this->getReflection();
        if (!$r->hasAnnotation('table') || !$r->hasAnnotation('storage')) {
            throw new \Nette\Application\ApplicationException("Class has no table annotation");
        }
        try {
            self::getDbContext()->insert($r->getAnnotation('table'), $this->getDbFormatedData(true))->execute();
            foreach ($this->getFields(true) as $field) {
                /* @var $field Field */
                if ($field->isAutoIncrement()) {
                    $this->setId(array($field->getName() => self::getDbContext()->insertId()));
                    break;
                }
            }
        } catch (\DibiDriverException  $e) {
            return $this->processException($e, callback($this, "create"));
        }
        if ($r->getAnnotation('cached') === TRUE ) {
            \Foundation\DBCache\ObjectCache::add($this);
        }
        return $this;
    }
    
    protected function processException(\DibiDriverException $e, \Nette\Callback $callback, array $args = null) {
        $r = $this->getReflection();
        $this->referenceCount++;
        if ($r->hasAnnotation('noModify')) throw $e;
        if (in_array($e->getCode() , array(1146 /*  NO TABLE */, 1054 /* NO ROW */))) {
                $columns = array();
                foreach ($this->getFields() as $field) {
                    /* @var $field Field */
                    $columns[$field->getStorageName()] = "ADD " . $field->getInsertSequence();
                }
                
                if(!in_array($e->getCode(), array(1146 /* NO ROW */))) {
                    $info = new \DibiTableInfo(new \DibiMySqlReflector( self::getDbContext()->getDriver() ), 
                            array( 'name' => $r->getAnnotation('table')));
            //\Nette\Diagnostics\Debugger::barDump($info->getColumnNames() , 'CnAMES');
                        foreach ($info->getColumnNames() as $name) {
                            unset($columns[$name]);
                        }
                }
                if ($e->getCode() == 1146) {
                    self::getDbContext()->query("CREATE TABLE %n (
                      %sql
                    ) COMMENT='' ENGINE='InnoDB' COLLATE 'utf8_general_ci'", $r->getAnnotation('table'), implode(", ", $columns));
                } else if ($e->getCode() == 1054) {
                    self::getDbContext()->query("ALTER TABLE %n", $r->getAnnotation('table'), implode(", ", $columns));
                } 
                if ($this->referenceCount < 3) {
                    return $callback->invokeArgs(is_array($args) ? $args : array());
                    $this->referenceCount = 0;
                }
            } else {
                throw $e;
            }
    }
    
    

    /**
     *
     * @return DBStored 
     */
    public function update() {
        $r = $this->getReflection();
        if (!$r->hasAnnotation('table') || !$r->hasAnnotation('storage')) {
            throw new \Nette\Application\ApplicationException("Class has no table annotation");
        }
        try {
            $keys = array();
            foreach ($this->getFields(true) as $field) {
                /* @var $field Field */
                $keys[$field->getStorageName()] = $field->getStorageValue($this[$field->getName()]);
            }
            
            self::getDbContext()->update($r->getAnnotation('table'), $this->getDbFormatedData(true))
                            ->where('%and', $keys)
                            ->execute();
        } catch (\DibiDriverException  $e) {
            return $this->processException($e, callback($this, "update"));
        }
        if ($r->getAnnotation('cached') === TRUE ) {
            \Foundation\DBCache\ObjectCache::save($this);
        }
        return $this;
    }
    
    public function __call($name, $args) {
        
        if (preg_match("/^(get|set)/", $name)) {
            
            $shortname = preg_replace("/^get|^set/", "", $name);
            $found = null;
            foreach ($this->getFields() as $fname =>$field) {
                /* @var $field Field */
                //\Nette\Diagnostics\Debugger::barDump(array('joined'=>$field->isJoined(), 'shortname'=> $shortname, 'compTo'=> $field->isJoined() ? self::translateToMethodName($field->getJoinedTypeName(), $field->getStorageName(), true) : null), "$fname");
                if ($field->isJoined() 
                        && $shortname == self::translateToMethodName($field->getJoinedTypeName(), $field->getStorageName(), true)) {
                    $found = $field;
                }
            }
            //\Nette\Diagnostics\Debugger::barDump($found, "CALLED: ".$name);
            if ($found) {
                if(preg_match("/^set/", $name)) {
                    if (isset($args[0]) && $args[0] instanceof DBStored) {
                        $this[$found->getName()] = $found->set($args[0]->getId());
                        $this->_ownObjectCache[$found->getName()] = $args[0];
                        return;
                    } else {
                        throw new \Foundation\Exception('Argument must be DBStored object');
                    }
                } else {
                    if ($this[$found->getStorageName()] === null) return null;
                    $reflection = $this->getReflection();
                    //\Nette\Diagnostics\Debugger::barDump($found->getName(), "NAME");
                    //\Nette\Diagnostics\Debugger::barDump($this->_ownObjectCache, "OWNCACHE");
                    if ($found->getJoinedClassReflection()->getAnnotation('cached') === TRUE ) {
                        $this->_ownObjectCache[$found->getName()] = \Foundation\DBCache\ObjectCache::getByIdAndReflection($found->getJoinedClassReflection(), $this[$found->getStorageName()]);
                    } else if (\Foundation\DBCache\SessionObjectCache::hasObjectWithReflectionAndId($found->getJoinedClassReflection(), $this[$found->getStorageName()])) {
                        $this->_ownObjectCache[$found->getName()] = \Foundation\DBCache\SessionObjectCache::getObjectWithReflectionAndId($found->getJoinedClassReflection(), $this[$found->getStorageName()]);
                    }
                    if (!isset($this->_ownObjectCache[$found->getName()])) {
                        $cache = \Foundation\DBCache\SelectCache::getOrCreateWithObjectReflection($reflection);
                        $jtpn = preg_replace("/^(\/|\\\\)/", "", $found->getJoinedTypeName());
                        $instance = $found->getJoinedClassReflection()->newInstanceArgs();
                        $noDataInside = true;
                        if (isset($cache->joins[$jtpn])) {
                            $noDataInside = false;
                            foreach ($cache->joins[$jtpn]['translation'] as $fieldName => $alias) {
                                if (!isset($this->_data[$alias]) && !array_key_exists($alias, $this->_data)) {
                                    //\Nette\Diagnostics\Debugger::barDump(array('fn'=>$fieldName, 'al'=>$alias, 'data'=>$this->_data), "INCOPLETE");
                                    $noDataInside = true;
                                    break;
                                }
                                $instance[$fieldName] = $this->_data[$alias];
                            }
                            //\Nette\Diagnostics\Debugger::barDump ($instance, "AFTER");
                            if (!$noDataInside) {
                                $this->_ownObjectCache[$found->getName()] = $instance;
                            }
                        }
                        if ($noDataInside) {
                            //\Nette\Diagnostics\Debugger::barDump($instance, " NIC VEVNITR");
                            $this->_ownObjectCache[$found->getName()] = $instance->getById($this[$found->getStorageName()]);
                        }
                    }
                    //\Nette\Diagnostics\Debugger::barDump($this->_ownObjectCache, "OWNCACHE AFTER");
                    return $this->_ownObjectCache[$found->getName()];
                }
            }
        }
        parent::__call($name, $args);
    }

    /**
     *
     * @return DBStored 
     */
    public function remove() {
        $r = $this->getReflection();
        if (!$r->hasAnnotation('table') || !$r->hasAnnotation('storage')) {
            throw new \Nette\Application\ApplicationException("Class has no table annotation");
        }
        try {
            $keys = array();
            foreach ($this->getFields(true) as $field) {
                /* @var $field Field */
                $keys[$field->getStorageName()] = $field->getStorageValue($this[$field->getName()]);
            }
            self::getDbContext()->delete($r->getAnnotation('table'))
                            ->where('%and', $keys)
                            ->execute();
        } catch (\DibiDriverException  $e) {
            return $this->processException($e, callback($this, "remove"));
        }
        if ($r->getAnnotation('cached') === TRUE ) {
            \Foundation\DBCache\ObjectCache::del($this);
        }
        return $this;
    }
    
    protected function getDbFormatedData($withoutAutoincrementKeys = false) {
        $data = array();
        foreach ($this->getFields() as $name => $field) {
            /* @var $field Field */
            if ($withoutAutoincrementKeys == false || !$field->isAutoIncrement()) {
                $data[$field->getStorageName()] = $field->getStorageValue($this->{$field->getName()});
            } 
        }
        return $data;
    }
    
    public static function translateToMethodName($referencedTableName, $reffererColumnName, $tableNameIsClassName = false) {
        $table = $tableNameIsClassName ? substr($referencedTableName, strripos($referencedTableName, "\\")+1) : self::translateToClassName($referencedTableName);
        $column = self::translateToClassName(str_replace(array('_id'), '', $reffererColumnName));
        //\Nette\Diagnostics\Debugger::barDump(array('tab'=>$table, 'col'=>$column, 'rr'=>substr($referencedTableName, strripos($referencedTableName, "\\")), 'rrIn'=>$referencedTableName ), "col");
        if ($table == $column) {
            return $table;
        } else {
            return $column . $table;
        }
    }
    
    public static function translateToClassName($name) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }
    
    
    protected static function isCached() {
        return self::getReflection()->getAnnotation('cached') === TRUE ;
    }

    /**
     *
     * @param type $data
     * @return StoredDataObject 
     */
    public static function objectWithData($data, &$referrer = null, &$key = null) {
        if ($data === null) {
            return null;
        }
        $r = self::getReflection();
        $keys =  $key ? null : call_user_func($r->getName() .'::getFields', true);
        if ($key) {
            $referrer = (is_array($data) ? $data[$key] : $data->{$key});
        } else if (count($keys) == 1) {
            $dbRef = array_pop($keys);
            $key = $dbRef->getStorageName();
            $referrer = (is_array($data) ? $data[$key] : $data->{$key});
        } else {
            $referrer = null;
        }
        if ($referrer && \Foundation\DBCache\SessionObjectCache::hasObjectWithReflectionAndId($r, $referrer)) {
            return \Foundation\DBCache\SessionObjectCache::getObjectWithReflectionAndId($r, $referrer, $data);
        } else {
            $obj = $r->newInstanceArgs();
            $obj->setData((array) $data);
            return $obj;
        }
    }
    
    public function release() {
        \Foundation\DBCache\SessionObjectCache::unsetObject($this);
        if (isset($this->_ownObjectCache)) {
            foreach ($this->_ownObjectCache as $cached) {
                unset($cached);
            }
            unset($this->_ownObjectCache);
        }
        return parent::release();
    }
    
    
}
