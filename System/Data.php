<?php
namespace RapidFrames\System;

/**
 * Get Tree Grid
 * @author Mwayi Dzanjalimodzi 
 * @uses Smrtr_DataGrid
 * @param string $path
 * @return Object Smrtr_DataGrid
 */
class DataCSV
{
    /**
     * Access 
     */
    public static $hasOrder;

    public static function getTreeGrid($path)
    {
        if(!file_exists($path))
            return false;
        try{
            $grid = new \Smrtr_DataGrid;
            $grid->loadCSV($path, false,true);
        }  catch (Exception $e){
            echo $e;
            return false;
        }
        //$grid = new \Smrtr_DataGrid;
        //$grid->loadCSV($path, false,true);
        if(!$grid->hasLabel('column','slug'))
            return false;
        self::$hasOrder = $grid->hasLabel('column','order');
        if(!self::$hasOrder)
            $grid->orderRows('slug');
        $grid->eachRow(function($key, $row){
            // If order is null then give row high number
            static $pos = 999999999;
            $row['slug'] = trim($row['slug'],'/-');
            $row['slug'] = preg_replace('#([-]{2,})|([/]{2,})#','/',$row['slug']);
            if(DataCSV::$hasOrder){
                if(!$row['order'])
                $row['order'] = $pos++;
            }
        },true);
        if(self::$hasOrder)
            $grid->orderRows('order');
        return $grid;
    }
    
    /**
     * Get Tree data
     * @uses RapidFrames\System\DataTree
     * @uses RapidFrames\System\Tree
     * @param \Smrtr_DataGrid object
     * @return Object | bool RapidFrames\System\Tree
     */
    public static function getTreeData(\Smrtr_DataGrid $grid)
    {
        $dataTree = new \RapidFrames\System\DataTree(
                $grid->getArray(), 
                $grid->getColumnLabels());
        return new \RapidFrames\System\Tree($dataTree);
    }
    
    /**
     * Get Tree data
     * @uses RapidFrames\System\DataTree
     * @uses RapidFrames\System\Tree
     * @param string $path
     * @return Object RapidFrames\System\Tree | bool
     */
    public static function getData($path)
    {
        $grid = self::getTreeGrid($path);
        $dataTree = new \RapidFrames\System\DataTree(
                $grid->getArray(), 
                $grid->getColumnLabels());
        return new \RapidFrames\System\Tree($dataTree);
    }
    
    /**
     * Get Mime Types
     * @uses Smrtr_DataGrid
     * @param string $path
     * @return Object RapidFrames\System\Tree | bool
     */
    public static function getMimeTypes()
    {
        $grid = new \Smrtr_DataGrid;
        $grid->loadCSV(RF_SYSTEM.DS.'MIME_Types.csv',false,true);
        $data = array();
        foreach($grid->getArray() as $row){
            $extensions = explode(',',$row[0]);        
            if(is_array($extensions) && count($extensions)>1){
                foreach($extensions as $ext)
                    $data[trim($ext)] = $row[1];
            }else $data[trim($row[0])] = $row[1];
        }
        return $data;
    }

    /**
     * Get pages
     * @return Object RapidFrames\System\Tree | bool
     */
    public static function getPages()
    {
        return self::getData(RF_PAGES);
    }
    
    /**
     * Get menus
     * @param string $menu
     * @return Object RapidFrames\System\Tree | bool
     */
    public static function getMenu($menu)
    {
        return self::getData(RF_MENUS.DS.$menu.'.csv');
    }
}