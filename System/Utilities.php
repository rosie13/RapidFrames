<?php

namespace RapidFrames\System;

class Load_XML
{
    var $path;
    var $xml;
    function __construct($path)
    {
        if(!$path)
        exit('path has not been set');
        
        $this->path = $path;
        
        $this->_getXml();
        return $this;
    }
    
    private function _getXml(){
        $this->xml = simplexml_load_file($this->path,'SimpleXMLElement',LIBXML_NOCDATA);

        return $this;
    }

    
}

/**
 * Alert
 */
class Alert
{
    public static function fatal($msg)
    {
        self::base($msg,'danger'); 
        die;
    }
    public static function fail($msg)
    {
        self::base($msg,'danger'); 
        die;
    }
    private static function base($msg)
    {
        printf('<div style="background:#ccc;padding:10px;color:#333;font-size:14px;margin:10px 0">%s</div>',$msg);
    }
}

