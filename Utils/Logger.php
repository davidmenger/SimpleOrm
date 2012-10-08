<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Foundation\Utils;
/**
 * Description of Logger
 *
 * @author David Menger
 */
class Logger extends \Nette\Object {
    
    const DEBUG = 'debug',
		INFO = 'info',
		WARNING = 'warning',
		ERROR = 'error',
		CRITICAL = 'critical';
    
   public static function log($event, $objectOrMessage, $sender = null, $priority = self::INFO) {
       
       $appendTo = TEMP_DIR . "/../log/".$priority."-". \Nette\Utils\Strings::webalize($event) .".txt";
       
       $data = date('Y-m-d H:i:s')." [".$event."] ".  ($sender ? substr(preg_replace("/\s+/S", " ", var_export($sender, true)), 0, 100) : "")."\n"
               
                .\Nette\Utils\Strings::indent($objectOrMessage instanceof \Exception ? $objectOrMessage->getMessage() : (gettype($objectOrMessage) == "string" ? $objectOrMessage : var_export($objectOrMessage, true)));
       $data .= "\n\n";
       if (file_exists($appendTo)) {
           $data .= file_get_contents($appendTo);
       }
       @file_put_contents($appendTo, $data);
       if ($objectOrMessage instanceof \Exception) {
           \Nette\Diagnostics\Debugger::log($objectOrMessage, $priority);
       } else if ($priority == self::CRITICAL) {
           \Nette\Diagnostics\Debugger::log(new \Foundation\Exception($event . ": ".  substr(var_export($objectOrMessage, true), 200)), $priority);
       }
       
   }
    
}
