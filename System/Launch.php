<?php
/**
 * Lets Launch this prototype
 * Function wrapper for sitemap class
 * @package rapidframes
 * @author Mwayi Dzanjalimodzi 
 */
global $pagesData ,$pagesGrid, $currentPage;
$currentPage = (object)array('slug','symSlug');
ini_set('auto_detect_line_endings', true);
define('RF_PROJECT',dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'Project');
define('RF_SYSTEM' ,dirname(__FILE__));
// A little help
function pre_dump($a){ echo '<pre>'.print_r($a,true).'</pre>'; }
foreach(parse_ini_file("Global.ini", true) as $key => $val)
    define($key,$val);
// Load core files
require_once('API'.DS.'Interface.php');
require_once('Vendors'.DS.'DataGrid'.DS.'DataGrid.php');
require_once('Vendors'.DS.'Zend'.DS.'Config.php');
require_once('Utilities.php');
require_once('Router.php');
require_once('Tree.php');
require_once('Data.php');
require_once('Navigation.php');
// Fail if we have no Layout, DB, Blocks directory present
if(!file_exists(RF_DB))
    RapidFrames\System\Alert::fail('No Data directory');
if(!file_exists(RF_LAYOUTS))
    RapidFrames\System\Alert::fail('No layouts directory');
if(!file_exists(RF_BLOCKS))
    RapidFrames\System\Alert::fail('No blocks directory');
// Load up pagedata and then begin to route
$pagesGrid = \RapidFrames\System\DataCSV::getTreeGrid(RF_PAGES,true);
$pagesData = RapidFrames\System\DataCSV::getTreeData($pagesGrid);
new RapidFrames\System\Router;