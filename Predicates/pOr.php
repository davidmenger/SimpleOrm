<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Predicates;
/**
 * Description of pOr
 *
 * @author David Menger
 */
class pOr extends Base {
   
    protected  function getGlue() {
        return $this->glue !== null ? $this->glue : "OR";
    }

    
}
