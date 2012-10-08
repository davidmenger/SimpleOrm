<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Utils;
/**
 * Description of WSDL
 *
 * @author David Menger
 */
abstract class WSDLservice extends \Nette\Object {
    
    protected $location;
    protected $uri;
    protected $functions;

    /**
     *
     * @var SoapClient 
     */
    protected $soap;
    
    public function __construct() {
        if ($this->getReflection()->hasAnnotation('location')) {
            $this->location = $loc = new \Nette\Http\Url($this->getReflection()->getAnnotation('location'));
            $this->uri = $loc->getScheme() . "://" . $loc->getAuthority() . "/";
        }
        ini_set("soap.wsdl_cache_enabled", "0");
    }
    
    /**
     *
     * @return SoapClient 
     */
    protected function getSoap() {
        if ($this->soap == null) {
            $this->soap = new \SoapClient($this->location->getAbsoluteUrl());
            
        }
        return $this->soap;
    }

    public function getFunctions() {
        if ($this->functions===null) {
            $funcs = $this->getSoap()->__getFunctions();
            $this->functions = array();
            foreach ($funcs as $func) {
                $functionname = substr($func, strpos($func, " ")+1, strpos($func, "(")-strpos($func, " ")-1);
                $result = substr($func, 0, strpos($func, " "));
                $this->functions[$functionname] = (object) array(
                    'function' => $functionname,
                    'result' => $result
                );
            }
        }
        \Nette\Diagnostics\Debugger::barDump($this->getSoap()->__getFunctions(), "FUNC");
        return $this->functions;
    }
    
    public function __call($name, $args) {
        if (key_exists($name, $this->getFunctions())) {
            \Nette\Diagnostics\Debugger::barDump(array('name'=>$name, 'args'=>$args), "SOAP CALL");
            try {
                $res =  $this->getSoap()->__soapCall($name, $args);
            } catch (\SoapFault $fault) {
                \Nette\Diagnostics\Debugger::barDump($fault->detail->ExceptionDetail);
                throw $fault;
            }
            \Nette\Diagnostics\Debugger::barDump($res, "RES");
            $retArg = $this->functions[$name]->result;
            $retArg = str_replace(array('Response'), array('Result'), $retArg);
            //\Nette\Diagnostics\Debugger::barDump($res->$retArg->List[0], "RETARG");
            
            if (isset($res->$retArg)) {
                if (count($res->$retArg) == 1) {
                    foreach ($res->$retArg as $key => $value) { $key = $key; $value = $value; break; }
                    if (isset($key, $value) && $this->getReflection()->hasMethod('processResponseType'.$key)) {
                        return $this->getReflection()->getMethod('processResponseType'.$key)->invokeArgs($this, array($value));
                    }
                }
                return $res->$retArg;
            } else {
                return null;
            }
        }
        return parent::__call($name, $args);
    }
    
}
