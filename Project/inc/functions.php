<?php 

global $usersDataGrid;

$grid = new \Smrtr_DataGrid(); 
$usersDataGrid = $grid->loadCSV(RF_DB.DS.'users.csv',true,true);

$this->bar = function($var)
{ 
    echo "Hello, $var this function is added at runtime"; 
};

$this->getUsers = function()
{ 
    $grid = new \Smrtr_DataGrid(); 
    $grid->loadCSV(RF_DB.DS.'users.csv',true,true);
};
