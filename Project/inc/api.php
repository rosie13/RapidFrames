<?php 


$api->getBar = function()
{
    return "Hello, this function is added at runtime"; 
};


$api->getJoe = function($this)
{
    //$this->grid 
    return "Joe"; 
};
