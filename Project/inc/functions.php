<?php 

$this->bar = function($var)
{ 
    echo "You just extended the router!"; 
};
$this->getUsers = function()
{ 
    $grid = new \Smrtr_DataGrid(); 
    $grid->loadCSV(RF_DB.DS.'users.csv',true,true);
    return  $grid->loadCSV(RF_DB.DS.'users.csv',true,true);
};
