<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class pA extends Foundation\Predicates\pAnd {
    
    /**
     * 
     * @param type $tabArrayOrCsv
     * @param type $predicate
     * @param type $o
     * @return type
     */
    public static function lazy($tabArrayOrCsv = true, $predicate = null, $o = null) {
        return self::i($predicate, $o)->setLazy($tabArrayOrCsv);
    }

    /**
     *
     * @param type $predicate
     * @return \Foundation\Predicates\pAnd 
     */
    public static function f($filterArray) {
        $filterArray = is_array($filterArray) ? $filterArray : array();
        $proceed = array();
        foreach ($filterArray as $key => $value) {
            if ($value) {
                $proceed[$key] = $value;
            }
        }
        return self::i($proceed);
    }
    
    /**
     *
     * @param type $predicate
     * @return type 
     */
    public static function i($predicate = null, $o = null) {
        if ($o || is_array($o)) {
            $predicate = array($predicate => ($o instanceof \Foundation\Object\DataObject ? $o->getId() : $o));
        }
        return self::init(is_array($predicate) ? $predicate : ($predicate ? array($predicate) : array()));
    }
}

class pOr extends \Foundation\Predicates\pOr {
    
    
    /**
     *
     * @param type $predicate
     * @return \Foundation\Predicates\pOr 
     */
    public static function i($predicate = null, $o = null) {
        if ($o) {
            $predicate = array($predicate => ($o instanceof \Foundation\Object\DataObject ? $o->getId() : $o));
        }
        return self::init(is_array($predicate) ? $predicate : ($predicate ? array($predicate) : array()));
    }
    
}





class filt extends \Foundation\Utils\Filter {
    
}


