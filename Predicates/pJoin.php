<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Predicates;
/**
 * Description of JoinPredicate
 *
 * @author David Menger
 */
class pJoin extends pAnd {
    
    protected $joinedTable;
    protected $mainTable;
    protected $joinedField;
    protected $mainField;
    protected $isJoined = false;
    protected $noLeftJoin = false;

    /**
     *
     * @param type $joinedTable
     * @param type $mainTable
     * @param type $joinedField
     * @param type $mainField
     * @param type $noLeftJoin
     * @return pJoin 
     */
    public function setJoinProperties($joinedTable, $mainTable, $joinedField, $mainField, $noLeftJoin = false) {
        $this->joinedTable = $joinedTable;
        $this->mainTable = $mainTable;
        $this->joinedField = $joinedField;
        $this->mainField = $mainField;
        $this->noLeftJoin = $noLeftJoin;
        
        $obj = new pJoin($this);
        //$this->where[] = $obj;
        
        return $obj;
    } 
    
    protected function processOnce(\DibiFluent $sel) {
        if ($this->isJoined==false && isset($this->joinedTable)) {
            if($this->noLeftJoin){
                $sel->join('%n', $this->joinedTable)->on('%n.%n = %n.%n', $this->mainTable, $this->mainField, $this->joinedTable, $this->joinedField);
            }else{
                $sel->leftJoin('%n', $this->joinedTable)->on('%n.%n = %n.%n', $this->mainTable, $this->mainField, $this->joinedTable, $this->joinedField);
            }
            $this->isJoined = true;
        }
        parent::processOnce($sel);
    }


    
    
}
