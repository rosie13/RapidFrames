<?php
/**
 * @todo this is too tightly coupled with querying the $pagesGrid
 */
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
    
    public function __construct(array $params, $http = true)
    {
        global $pagesGrid;
        $this->params = $params;
        $this->http = $http;
        if(!isset($params['get'])){
            header("Status: 404 Not Found");
            die;
        }
        $this->get = strtolower($params['get']);
        $this->format = isset($params['format']) && in_array(strtolower($params['format']),$this->allowedFormats)?strtolower($params['format']):'json';
        $this->configureCount();
        $this->method = $this->formatMethod($this->get);
        $this->grid = $pagesGrid; 
        if(count($params)===0){
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
     * Prepare content to dispatch
     * @return mixed string|array|object
     */
    public function prepareDispatch()
    {
        $content = false;
        try{ $content = $this->{$this->method}($this); } 
        catch (Exception $e){}
        // Its a count request
        if($this->count)
            $content = $this->resolveCount($content);
        if(!isset($content) && !$content)
            return false;
        
        switch($this->format)
        {
            case 'array':
                return (array)$content;
                break;
            case 'json':
                return json_encode($content);
                break;
            case 'xml':
                return $this->object2XML($content, new SimpleXMLElement('<tree/>'))->asXML();
                break;
            case 'csv':
                ob_start();
                $this->object2CSV($content);
                return ob_get_clean();
                break;
        }
    }
    
    /**
     * Dispatch the results
     * Prints out the results with the correct headers
     */
    public function dispatch()
    {
        if(!$content=$this->prepareDispatch())
            return false;
        
        if(!$this->http)
            return $content;
        else{
            switch($this->format){
                case 'array':
                    echo $content;
                    break;
                case 'json':
                    header("Accept-Ranges: bytes");
                    header("Content-type: application/json");
                    echo $content;
                    break;
                case 'xml':
                    header("Accept-Ranges: bytes");
                    header("Content-type: application/xml");
                    echo $content;
                    break;
                case 'csv':
                    header("Accept-Ranges: bytes");
                    header("Content-type: text/csv");
                    header("Content-Disposition: attachment; filename=get-$this->get.csv");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    echo $content;
                    break;
            }
            die;
        }
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
            if(is_array($v))
            $this->object2XML($v, $xml->addChild($k));
            else{
                $v = preg_replace('([\&])','&amp;',$v);
                $xml->addChild($k,$v);
            }
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
     * @return closure
     * @throws 
     */
    public function __call($method, $args)
    {
        if(isset($this->$method))
            return call_user_func_array($this->$method, $args);
        else throw new \Exception("$method does not exist");
    }
}