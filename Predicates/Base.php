<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Predicates;
/**
 * Description of Base
 *
 * @author David Menger
 */
abstract class Base  {
    
    protected $where = array();

    private static $deep;
    
    protected $glue;
    
    protected $lazy = FALSE;

    protected $callings = array();
    
    public function isEmpty() {
        return count($this->callings) == 0 && count($this->where) == 0;
    }

    function __construct($predicate) {
        $this->where = is_array($predicate) ? $predicate : array($predicate);
    }

    /**
     *
     * @param type $predicate
     * @return \Foundation\Predicates\Base 
     */
    public static function init($predicate) {
        return new static($predicate);
    }
    
    protected function processOnce(\DibiFluent $sel) {
        foreach ($this->callings as $value) {
            $sel->__call($value->name, $value->args);
        }
    }

    public function getLazy() {
        return $this->lazy;
    }

    public function setLazy($lazy = TRUE) {
        if ($lazy===TRUE || $lazy === FALSE || is_array($lazy)) {
            $this->lazy = $lazy;
        } else {
            if (!is_array($this->lazy)) {
                $this->lazy = array();
            } 
            $tokens = explode(",", $lazy);
            foreach ($tokens as $tab) {
                $tab = trim($tab);
                $this->lazy[] = $tab;
            }
        }
        return $this;
    }

    
    final public function proceed(\DibiFluent $sel, \Nette\Reflection\ClassType $reflection) {
        $result = array();
        self::$deep++;
        $this->processOnce($sel);
        foreach ($this->where as $key => $value) {
            if ($value instanceof \Foundation\Object\DBStored) {
                $k = $value->getFields(true);
                $k = array_pop($k); 
                $sel->getCommand();
                /* @var $k \Foundation\Object\Field*/
                $r = $this->parseItem($reflection->getAnnotation('table') . "." . $k->getStorageName(), $value->getId(),  $sel);
            } else if ($value instanceof Base) {
                $r = $value->proceed($sel, $reflection);
            } else if (is_scalar($value) || is_array($value) || $value instanceof \DateTime || $value === null) {
                $r = $this->parseItem($key, $value,  $sel);
                
            } else {
                throw new \Foundation\PredicateException("Predicate array should contain only predicates, scalars or arrays.");
            }
            if ($r != null) {
                $result[] = $r;
            }
        }
        self::$deep--;
        if (self::$deep == 0 && count($result)>0) {
            $sel->where("(%sql)", implode(" ".$this->getGlue()." ", $result));
        } else if (self::$deep != null && count($result) > 0) {
            return "(".implode(" ".$this->getGlue()." ", $result).")";
        }
    }
    
    protected function expandTextItem($key, $value) {
        $found = array();
        $r = 0;
        if (preg_match('/^(.*?)\~(.*?)\%(.*?)$/', $key, $found)) {
            $row = $found[1];
            $sign = $found[2];
            $modificator = $found[3];
            $r = 1;
        } else if (preg_match('/^(.*?)\%(.*?)$/', $key, $found)) {
            $row = $found[1];
            $sign = "=";
            $modificator = $found[2];
            $r = 2;
        } else if (preg_match('/^(.*?)\~(.*?)$/', $key, $found)) {
            $row = $found[1];
            $sign = $found[2];
            $modificator = "s";
            $r = 3;
        } else {
            $row = $key;
            $sign = "=";
            $modificator = is_int($value) ? "i" : (is_float($value) ? "f" : "s");
            $r = 4;
        }
        
        //\Nette\Diagnostics\Debugger::barDump(array('row' => $row, 'sign' => $sign, 'modifier' => $modificator, 'fnd'=>$found), $r);//
        
        if ($value instanceof \DateTime && $modificator != 'd') {
            $modificator = 't';
        }
        
        if (is_array($value) && strtoupper($sign) != "NOT IN") {
            $sign = 'IN';
        }
        if (is_array($value)) {
            $modificator = 'in';
        }
         
        if ($sign == 'sql' || $modificator == 'sql') {
            $sign = ''; 
            $modificator = 'sql';
        }
        
        if ($value === null && $sign != "IS NOT") {
            $sign = "IS";
        }
        
        return (object) array('row' => $row, 'sign' => $sign, 'modifier' => $modificator);
    }


    protected function parseItem($key, $value, \DibiFluent $sel) {
        $translator = new \DibiTranslator($sel->getConnection());
        $translator->translate(array());
        $row = $this->expandTextItem($key, $value);
        
        return "[".$row->row."] ".$row->sign." ".$translator->formatValue($value, $row->modifier);
    }
    
    protected  function getGlue() {
        return $this->glue;
    }
    
    /**
     *
     * @param type $glue
     * @return Base 
     */
    public function setGlue($glue) {
        $this->glue = $glue;
        return $this;
    }
       
    /**
     *
     * supports = != < 
     * 
     * @param \Foundation\Object\DataObject $object 
     * 
     */
    public function isMatching(\Foundation\Object\DataObject $object) {
        //\Nette\Diagnostics\Debugger::barDump($object, "isMatching");
        $result = strtoupper($this->getGlue()) == "OR" ? false : true;
        foreach ($this->where as $key => $value) {
            if ($value instanceof Base) {
                $result = $value->isMatching($object);
            } else {
                $row = $this->expandTextItem($key, $value);
                //\Nette\Diagnostics\Debugger::barDump($row, "isMatchingRow");
                
                if (preg_match("/\\(\\)$/", $row->row)) {
                    $method = str_replace("()", "", $row->row);
                    $compare = $object->$method();
                }  else {
                    $compare = $object->{$row->row};
                }
                
                if ($compare instanceof \Foundation\Object\DataObject && $value instanceof \Foundation\Object\DataObject) {
                    $result = ($compare->getId() == $value->getId());
                } if ($row->sign == '=' || $row->sign == "==") {
                    $result = ($value == $compare);
                } else if ($row->sign == '<') {
                    $result = ($compare < $value);
                } else if ($row->sign == '>') {
                    $result = ($compare > $value);
                } else if ($row->sign == '===') {
                    $result = ($compare === $value);
                } else if ($row->sign == '!=') {
                    $result = ($compare != $value);
                } else if ($row->sign == '<=') {
                    $result = ($compare <= $value);
                } else if ($row->sign == '>=') {
                    $result = ($compare >= $value);
                } else if ($row->sign == 'W') {
                    $result = (\Nette\Utils\Strings::webalize($compare) == $value);
                }
            }
            if ((strtoupper($this->getGlue()) == "OR" && $result == true) || (strtoupper($this->getGlue()) != "OR" && $result == false)) {
                //\Nette\Diagnostics\Debugger::barDump(strtoupper($this->getGlue()) == "OR", "RESULT IS");
                return strtoupper($this->getGlue()) == "OR";
            }  
        }
        //\Nette\Diagnostics\Debugger::barDump(strtoupper($this->getGlue()) != "OR", "RESULT IS");
        return strtoupper($this->getGlue()) != "OR";
    }
    
    public function __call($name, $args) {
        $this->callings[] = (object) array('name'=>$name, 'args'=>$args);
        return $this;
    }
    
}
