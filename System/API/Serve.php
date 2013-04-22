<?php
if(!isset($_GET['get']))
    die;
require_once('Controller.php');
$api = new API_Controller($_GET);
if(file_exists(RF_INC.DS.'api.php'))
    include RF_INC.DS.'api.php';
$api->dispatch();