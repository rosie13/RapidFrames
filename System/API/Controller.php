<?php
if(!isset($_GET['get']))
    die;

class API_Controller
{
    /**
     * The current method to get
     * @var string
     */
    public $get;
    
    /**
     * The current format
     * @var string
     */
    public $format;
    
    /**
     * Allowed API formats
     * @var array
     */
    private $allowedFormats = array('json','array','xml','csv');
    
    /**
     * Order of data
     * @var string
     */
    public $order = 'asc';
    
    /**
     * Menu exists?
     * @var bool
     */
    private $menuExists = false;
    
    /**
     * Level
     * @var bool
     */
    static $level;
    
    /**
     * Count request
     * @var bool
     */
    private $count = false;
    
    /**
     * Data Columns
     * @var array
     */
    static $dataColumns = array();
    
    public function __construct()
    {
        global $pagesGrid;
        $this->params = $_GET;
        $this->get = strtolower($_GET['get']);
        $this->format = isset($_GET['format']) && in_array(strtolower($_GET['format']),$this->allowedFormats)?strtolower($_GET['format']):'json';
        $this->configureCount();
        $this->method = $this->formatMethod($this->get);
        $this->grid = $pagesGrid; 
        if(count($_GET)===0){
            header("Status: 404 Not Found");
            die;
        }
        if(!$pagesGrid instanceof \Smrtr_DataGrid){
            header("Status: 404 Not Found");
            die;
        } 
        self::$dataColumns = array_flip($this->grid->getColumnLabels());
        $this->configureMenuRequest();
        array_walk($this->params, function(&$val){
            $val = strtolower($val);
        });
        $this->configureOrdering();
        $this->configureLevel();
    }
    
    /**
     * Configure Count
     * @return object $this
     */
    private function configureCount()
    {
        if(substr($this->get,0,5)==='count'){
            $this->get = $method = substr_replace($this->get,'',0, 6);
            $this->count = true;
        }
        return $this;
    }
    
    /**
     * Resolve the count
     * @param string $content
     * @return array
     */
    private function resolveCount($content)
    {
        $params = $this->params;
        unset($params['request_url']);
        return array('count'=>count((array)$content),'request'=>$params);
    }
    
    /**
     * Configure Menu settings
     * If we are getting menus we need an additional setup. Pick up any get value
     * that starts with 'menu'
     * @return object $this
     */
    private function configureMenuRequest()
    {
        if(substr($this->get,0,4)==='menu' && isset($this->params['name'])){
            $menuCsv = RF_MENUS.DS.$this->params['name'].'.csv';
            if(file_exists($menuCsv)){
                $this->grid = \RapidFrames\System\DataCSV::getTreeGrid(RF_MENUS.DS.$this->params['name'].'.csv',true);
                $this->menuExists = true;
            }
        }
        return $this;
    }
    
    /**
     * Configure Ordering
     * Automatically work out the request ordering
     * @return object $this
     */
    private function configureOrdering()
    {
        if(isset($this->params['order']))
            $this->order = $this->params['order'];
        if(isset($this->params['orderby'])){
            try {
                $this->grid->orderRows($this->params['orderby'],$this->order);
            } catch (Exception $e){
                // Fine. don't order rows
            }
        }
        return $this;
    }
    
    /**
     * Configure level
     * Automatically work out the request level
     * @return object $this
     */
    private function configureLevel()
    {
        if(isset($this->params['level'])){
            self::$level = $this->params['level'];
            $this->grid = $this->grid->filterRows(function($key, $label, $row){
                if(isset(API_Controller::$dataColumns['slug'])){
                    $slugKey = API_Controller::$dataColumns['slug'];
                    if(substr_count($row[$slugKey],'/')===(int)API_Controller::$level)
                        return true;
                }
                return false;
            });
        }
        return $this;
    }
    
    /**
     * Dispatch the results
     * Prints out the results with the correct headers
     */
    public function distpatch()
    {
        $content = false;
        try{ $content = $this->{$this->method}($this); } 
        catch (Exception $e){}
        // Its a count request
        if($this->count)
            $content = $this->resolveCount($content);
        $data = \RapidFrames\System\DataCSV::getMimeTypes();
        $mime = isset($data['.'.$this->format])?$data['.'.$this->format]:'';
        if(!$content){
            header("Status: 404 Not Found");
            die;
        }
        if($this->format==='array')
            print_r($content);
        elseif($mime)
        {
            header("Accept-Ranges: bytes");
            header(sprintf("Content-type: %s",$mime));
            switch($this->format){
                case 'json':
                    echo json_encode($content);
                    break;
                case 'xml':
                    echo $this->object2XML($content, new SimpleXMLElement('<tree/>'))->asXML();
                    break;
                case 'csv':
                    header("Content-Disposition: attachment; filename=get-$this->get.csv");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    $this->object2CSV($content);
                    break;
            }
        }
        die;
    }

    /**
     * Convert object to CSV
     * @param mixed $data
     */
    private function object2CSV($data)
    {   
        if($this->get==='page'){
            $head = $data = (array)$data;
            $head = array_keys((array)$head);
            $data = array_merge(array($head),array($data));
        }else{
            $head = $data = (array)$data;
            $head = array_shift($head);
            $head = array_keys((array)$head);
            $data = array($head)+$data;
        }
        $outputBuffer = fopen("php://output", 'w');
        foreach($data as $val){
            $val = (array)$val;
            if(is_array($val))
            @fputcsv($outputBuffer,$val);
        }
        fclose($outputBuffer);
    }
    
    /**
     * Convert and array to an xml
     * Prints out the results with the correct headers
     * @param array|object $tree
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement $xml
     */
    private function object2XML($tree, SimpleXMLElement $xml)
    {
        $tree = (array)$tree;
        foreach ($tree as $k => $v){
            $v = is_object($v)?(array)$v:$v;
            $k = str_replace('/','--',$k);
            if(is_numeric($k) || is_numeric(substr($k,0,1)))
                $k = 'int'.$k;
            is_array($v)
                ? $this->object2XML($v, $xml->addChild($k))
                : $xml->addChild($k, $v);
        }
        return $xml;
    }
    
    /**
     * Get all pages
     * @return mixed \RapidFrames\System\DataCSV::getTreeData|false
     */
    public function getMenu()
    {
        if($this->menuExists)
        return \RapidFrames\System\DataCSV::getTreeData($this->grid);
        
        return false;
    }
    
    /**
     * Get all pages
     * @return object \RapidFrames\System\DataCSV::getTreeData
     */
    public function getPages()
    {
        return \RapidFrames\System\DataCSV::getTreeData($this->grid);
    }

    /**
     * Get Specific Page
     * @return object \RapidFrames\System\DataCSV::getTreeData
     */
    public function getPage()
    {   
        $treeData = \RapidFrames\System\DataCSV::getTreeData($this->grid);
        if(isset($this->params['symslug']))
            return $treeData->{str_replace('--','/',$this->params['symslug'])};
        else return false;
    }

    /**
     * Get Decendants of a slug
     * @return object \RapidFrames\System\DataCSV::getTreeData
     */
    public function getSlugs()
    {
        $slugs = $this->grid->getColumn('slug');
        $slugs = array_filter($slugs);
        $slugs = array_flip($slugs);
        array_walk($slugs, function(&$var,$key){
             $var = str_replace('/','--',$key);
        });
        $slugs = array_flip($slugs);
        return $slugs;
    }
    
    /**
     * Get Decendants of a slug
     * @return object \RapidFrames\System\DataCSV::getTreeData
     */
    public function getMenuSlugs()
    {
        $slugs = $this->grid->getColumn('slug');
        $slugs = array_filter($slugs);
        $slugs = array_flip($slugs);
        array_walk($slugs, function(&$var,$key){
             $var = str_replace('/','--',$key);
        });
        $slugs = array_flip($slugs);
        return $slugs;
    }
    
    /**
     * Get Decendants of a slug
     * @return object \RapidFrames\System\DataCSV::getTreeData
     */
    public function getDescendents()
    {
        if(isset($this->params['symslug']))
        $children = $this->grid->searchRows(sprintf('slug^="%s/"',str_replace('--','/',$this->params['symslug'])));
        else return false;

        return \RapidFrames\System\DataCSV::getTreeData($children);
    }
    
    /**
     * Helper:: camelCase
     * @param string $string
     * @param bool $first
     * @return string $string
     */
    private function camelCase($string, $first=true)
    { 
        $string = trim(ucwords(preg_replace("#[\-\_]+#",' ',$string)));
        if(!$first){
            $parts = explode(' ',$string);
            if(is_array($parts)){
                if(isset($parts[0]))
                    $parts[0] = strtolower($parts[0]);
                $string = implode(' ',$parts);
            }
        }
        return preg_replace("#[\s]+#",'',$string);
    }
    
    /**
     * Helper:: Format Method
     * @param string $string
     * @return string $string
     */
    private function formatMethod($string)
    { 
        return preg_replace("/[^A-Za-z0-9 ]/",'','get'.$this->camelCase($string));
    }
    
    /**
     * Add Methods on the fly
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        if(isset($this->$method))
            return call_user_func_array($this->$method, $args);
    }
}
$api = new API_Controller;
if(file_exists(RF_INC.DS.'api.php'))
    include RF_INC.DS.'api.php';
$api->distpatch();