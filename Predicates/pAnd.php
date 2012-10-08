<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Predicates;
/**
 * Description of And
 *
 * @author David Menger
 */
class pAnd extends Base {

    protected  function getGlue() {
        return $this->glue !== null ? $this->glue : "AND";
    }
    


    
}
