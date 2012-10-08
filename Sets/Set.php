<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Set;
/**
 * Description of Set
 *
 * @author David Menger
 */
class Set extends \Nette\Object implements \Foundation\Interfaces\IIterableDataObjectSet {

    /**
     * @var \ArrayIterator 
     */
    protected $iterator;



    public function __construct(\Iterator &$initialArray = null) {
        $this->getIterator($initialArray);
    }

    public function __wakeup() {
       if (!$this->iterator instanceof \Iterator) {
           $this->createIterator();
       }
    }
    
    public function current() {
        return $this->iterator->current();
    }

    public function key() {
        return  $this->iterator->key();
    }

    public function next() {
        return $this->iterator->next();
    }

    public function rewind() {
        return $this->iterator->rewind();
    }

    public function valid() {
        return $this->iterator->valid();
    }
    
    public function add(\Foundation\Object\DataObject $object) {
        $this->iterator->append($object);
    }
    
    public function remove(\Foundation\Object\DataObject $object) {
        foreach ($this as $key => $in) {
            if ($in === $object) {
                $this->iterator->offsetUnset($key);
                return;
            }
        }
        throw new \Nette\Application\ApplicationException("Object not exists");
    }
    
    public function mergeWithSet(Set $set) {
        $current = $this->iterator->getArrayCopy();
        \Nette\Diagnostics\Debugger::barDump($current, "CURRENT");
        \Nette\Diagnostics\Debugger::barDump($set, "SET");
        $new = array();
        foreach ($set as $key => $value) {
            $new[$key] = $value;
        }
        $new = array_merge($current, $new);
        $this->createIterator($new);
    }

    public function count() {
        return $this->iterator->count();
    }
    
    /**
     *
     * @param type $key
     * @return Dictionary 
     */
    public function getDictionary($key) {
        $dict = new Dictionary();
        foreach ($this as $item) {
            $dict[$item[$key]] = $item;
        }
        return $dict;
    }
    
    public function getDistinct($key, $value) {
        $ret = array();
        foreach ($this as $item) {
            if ($key!=null) {
                $ret[$item[$key]] = $item[$value];
            } else {
                $ret[] = $item[$value];
            }
        }
        return $ret;
    }

     /**
     * !! NENI APLIKOVAN COUNT ALL
     * @return type 
     */
    public function countAll() {
        return $this->iterator->count();
    }
    
    
    public function getFirst() {
        if ($this->count()) {
            $this->rewind();
            return $this->current();
        } else {
            return null;
        }
        
    }
    
    /**
     *
     * @param \Foundation\Predicates\Base $predicate
     * @return \Foundation\Set\Set 
     */
    public function filterWithPredicate(\Foundation\Predicates\Base $predicate) {
        return \Foundation\Utils\Filter::filterSetWithPredicate($predicate, $this);
    }
    
    /**
     *
     * @return \ArrayIterator 
     */
    public function getIterator(&$withData = null) {
        if (!$this->iterator instanceof \Iterator || $withData !== null) {
           $this->iterator = $withData instanceof \Iterator ? $withData : new \ArrayIterator(is_array($withData) ? $withData : array());
        }
       return $this->iterator;
    }
    
    public function sortWithCallback(\Closure $callback) {
        if ($this->getIterator() instanceof \ArrayIterator) {
            $this->getIterator()->uasort($callback);
        } else {
            $array = $this->getIterator()->getArrayCopy();
            if ($this instanceof Set) {
                @usort($array, $callback);
            } else {
                @uasort($array, $callback);
            }
            $this->createIterator($array);
        }
        return $this;
    }

    
}
