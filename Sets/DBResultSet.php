<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of DBResultSet
 *
 * @author David Menger
 */
class DBResultSet extends \Nette\Object implements \Foundation\Interfaces\IIterableDataObjectSet, \IReleasable {
    
   
    private $classReflection;
    /**
     *
     * @var DibiResultIterator 
     */
    protected $result;
    
    protected $count;
    
    protected $key;


    /**
     *
     * @var type \ArrayIterator
     */
    protected $references;

    /**
     *
     * @var DibiFluent
     */
    protected $select;


    public function countAll() {
        if ($this->count === null && $this->select != null) {
            $sel = clone $this->select;
            if (stripos((string) $this->select, ' HAVING ') === false) {
                if ($this->classReflection->hasAnnotation('table') && $this->classReflection->hasAnnotation('key')) {
                    $sel->removeClause('select')->select('%n.%n', $this->classReflection->getAnnotation('table'), $this->classReflection->getAnnotation('key'));
                }
            }
            $this->count = $sel->removeClause('orderBy')->count();
        }
        return $this->count;
    }

    public function setAllCount($count) {
        $this->count = $count;
    }

    public function  __construct(\Iterator $result = null, \Nette\Reflection\ClassType $reflection = null, \DibiFluent $countAbleSelect = null) {
        if (!$reflection->isSubclassOf('\\Foundation\\Object\\StoredDataObject') || !$result instanceof \DibiResultIterator) {
            throw new \Foundation\Exception("Class is not instance of \Foundation\Object\StoredDataObject");
        }
        if ($result instanceof \DibiResultIterator) {
            $result->getResult()->setRowClass(null);
        }
        //\Nette\Diagnostics\Debugger::barDump($result, "refl");
        $this->select = $countAbleSelect;
        $this->classReflection = $reflection;
        $this->result = $result;
        $this->references = new \ArrayIterator();
    }

    public function current() {
        if ($this->result!=null) {
            $c = $this->result->current();
            if ($c instanceof \Foundation\Object\DBStored) {
                return $c;
            } else {
                $n = $this->classReflection->getName();
                return $n::objectWithData($c);
            }
            //return $this->classReflection->getMethod('objectWithData')->invoke($this->classReflection->getName(), array('data' => $this->result->current()));
        } else {
            return null;
        }
    }

    public function key() {
        if ($this->result!=null) {
            /*$ref = new NClassReflection($this->class);
            $obj = $this->classReflection->getMethod('objectWithData')->invoke($this->classReflection->getName(), $this->result->current());
            return $obj->getId();*/
            return $this->result->key();
        } else {
            return null;
        }
    }

    public function next() {
        if ($this->result!=null) {
            $this->result->next();
        }
    }

    public function rewind() {
        if ($this->result!=null) {
            $this->result->rewind();
        }
    }

    public function valid() {
        if ($this->result!=null) {
            return $this->result->valid();
        } else {
            return false;
        }
    }

    public function count() {
        if (!isset($this->result, $this->references)) throw new \Foundation\Exception("Result released!"); 
        if ($this->result!=null) {
            return $this->result->count();
        } else {
            return 0;
        }
    }
    
    


    /**
     * 
     * @return \Foundation\Object\DBStored
     */
    public function getFirst() {
       if ($this->count()) {
            $this->rewind();
            return $this->current();
        } else {
            return null;
        }
        
    }

    public function getDistinct($key, $value, $releaseAfterAll = false) {
        if (!isset($this->result, $this->references)) throw new \Foundation\Exception("Result released!"); 
        $ret = array();
        foreach ($this as $item) {
            if ($key!=null) {
                $ret[$item[$key]] = $item[$value];
            } else {
                $ret[] = $item[$value];
            }
        }
        if ($releaseAfterAll) $this->release();
        return $ret;
    }
    
    /**
     *
     * @param type $key
     * @return Dictionary 
     */
    public function getDictionary($key, $releaseAfterAll = false) {
        if ($this->references->count() > 0 && $this->key !== null && $this->key == $key) {
            return new Dictionary($this->references);
        } 
        $dict = new Dictionary();
        foreach ($this as $item) {
            $dict[$item[$key]] = $item;
        }
        if ($this->key !== null && $this->key == $key) {
            unset($dict);
            return new Dictionary($this->references);
        } else {
            if ($releaseAfterAll) $this->release();
            return $dict;
        }
    }

    /**
     *
     * @param \Foundation\Predicates\Base $predicate
     * @return \Foundation\Set\Set 
     */
    public function filterWithPredicate($predicate, $releaseAfterAll = false) {
        if (!$predicate instanceof \Foundation\Predicates\Base) {
            $predicate = new \Foundation\Predicates\pAnd(is_array($predicate) ? $predicate : array());
        }
        $r =  \Foundation\Utils\Filter::filterSetWithPredicate($predicate, $this);
        if ($releaseAfterAll) $this->release();
        return $r;
    }
    
    public function __sleep() {
        /*$iterator = new \ArrayIterator();
        foreach ($this as $value) {
            $iterator->append($value);
        }
        $this->result = $iterator;*/
        return array('classReflection', 'count');
    }
    
    public function release() {
        if (isset($this->result)) {
            $this->result->getResult()->free();
            unset($this->result);
        }
        if (isset($this->references)) unset($this->references);
        if (isset($this->classReflection)) unset($this->classReflection);
        if (isset($this->count)) unset($this->count);
        if (isset($this->key)) unset($this->key);
        if (isset($this->select)) unset($this->select);
    }
    
    
    
}
