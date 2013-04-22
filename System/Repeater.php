<?php 
namespace RapidFrames\System;
/**
 * Repeater.
 * 
 * The repeater loops through any object and allows you to set a template filled with 
 * expressions that need to be evaluated to populate the the repeater.
 * 
 * You can additionally handle your own expressions 
 */


class Repeater
{
    /**
     * Matched callers
     * @var array 
     */
    private $callers = array();
    
    /**
     * The repeater string
     * @var string
     */
    private $repeaterString;
    
    /**
     * The repeatable collection
     * @var mixed array|object
     */
    private $obj;

    public function __construct($obj, $repeaterString, array $handlers = array())
    {
        $this->obj = $obj;
        $this->handlers = $handlers;
        $this->repeaterString = $repeaterString;
        $this->split();
        return $this;
    }

    /**
     * Handle any type of expression
     * @param string $method
     * @param string $obj
     * @return mixed 
     */
    public function __call($method, $obj)
    {
        $obj = array_shift($obj);
        $method = str_replace('-','_',$method);
        if(isset($method[0]) && is_numeric($method[0]))
            throw new \Exception('Repeater expression must not start with an integer');
        $method = substr_replace($method,'',0,1);
        if(isset($obj->$method))
            return $obj->$method;
        return false;
    }

    /**
     * Render the replaced expression string
     * @return string 
     */
    public function render()
    {
        $html = array();
        foreach($this->obj as $obj){
            $obj = (object)$obj;
            $repeater = $this->repeaterString;
            foreach($this->callers as $caller => $replace){
                $method = "_$caller";
                if (array_key_exists($caller, $this->handlers) && is_callable($this->handlers[$caller]))
                $res = $this->handlers[$caller]($obj);
                else $res = $this->{$method}($obj);
              
                $repeater = str_replace($replace,$res,$repeater);
            }
            $html[] = $repeater;
        }
        $this->callers = array();
        return implode('',$html);
    }
    
    /**
     * Split expressions
     * @return object $this
     */
    private function split()
    {
        preg_match_all('/{{[a-z_]+}}/',$this->repeaterString,$matches);
        $matches = array_shift($matches);
        foreach($matches as $match)
        {
            $caller  = str_replace('{{','',$match);
            $caller  = str_replace('}}','',$caller);
            $this->callers[$caller] = $match;
        }
        return $this;
    }
}