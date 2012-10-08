<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Utils;

use \Foundation\Predicate, \Foundation\Interfaces\IIterableDataObjectSet;
/**
 * Description of Filter
 *
 * @author David Menger
 */
class Filter extends \Nette\Object {
   
    /**
     *
     * @param type string
     * @return type string
     */
    public static function webalizeClassName($value){
        return str_replace(" ", "", ucwords(preg_replace("/\-/", " ", \Nette\Utils\Strings::webalize($value))));
    }
    
    /**
     *
     * @param \Foundation\Predicates\Base $predicate
     * @param IIterableDataObjectSet $set
     * @return \Foundation\Set\Set 
     */
    public static function filterSetWithPredicate(\Foundation\Predicates\Base $predicate, IIterableDataObjectSet $set) {
        $ret = new \Foundation\Set\Set();
        foreach ($set as $value) {
            if ($predicate->isMatching($value)) {
                $ret->add($value);
            }
        }
        return $ret;
    }
    
    /**
     *
     * @param type $phone
     * @return type 
     */
    public static function sanitizePhone($phone) {
        $num = preg_replace("/[^0-9]/", "", $phone->getValue());
        return intval(substr($num, strlen($num)-9, 9));
    }
    
    public static function encodeNumber($number, $short = false) {
        if (strlen($number) > ($short ? 11 : 16) || $number < 1)            throw new \Nette\Application\ApplicationException("Potencial overflow");
        $mod = $number%36;
        $res = base_convert(($number+($short ? 73 : 9876543)) * ($short ? 3 : (73-$mod)), 10, $short ? 36 : 30);
        while (strlen($res)%3 != 0) {
            $res = "0".$res;
        }
        if ($short) {
            return base64_encode($res);
        } else {
            return base_convert($mod, 10, 36) . base64_encode($res);
        }
    }
    
    public static function decodeNumber($number, $short = false) {
        if (!$short) {
            $mod = base_convert(substr($number, 0, 1), 36, 10);
            $number = substr($number, 1);
        }
        $n = (base_convert(base64_decode($number), $short ? 36 : 30, 10) / ($short ? 3 : (73-$mod))) - ($short ? 73 : 9876543);
        if (round($n) != $n)            throw new \Nette\Application\ApplicationException('Neplatný kód');
        return $n;
    }
    
    
}
