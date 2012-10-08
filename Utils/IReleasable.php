<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Administrator
 */
interface IReleasable {
    public function release();
}

function release(IReleasable $obj) {
    $obj->release();
    unset($obj);
}

