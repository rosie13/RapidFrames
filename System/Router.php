<?php
namespace RapidFrames\System;

/**
 * The Router
 * - Resolve the request params
 * - Get the routes
 * - What kind of request is this? page|route|public?
 * - Resolve the request to a template
 * - Distpatch the view/layout
 * 
 * @author mwayi dzanjalimodzi
 * 
 * @method getRequestParams
 * @method getConfigs
 * @method getConfigRoutes
 * @method getSymSlug 
 * @method getSlug 
 * @method getBlock 
 * @method getBreadcrumb
 * @method getPage
 * @method setRequestType
 * @method resolveRequest
 * @method resolveRouteRequest 
 * @method resolve404 
 * @method isRequestPublic
 * @method isRequestRoute
 * @method utilityNormailiseDashes
 * @method dispatchTemplate 
 */

class Router
{
    /**
     * Public request
     * @var const
     */
    const PUBLIC_REQ = 'PUBLIC_REQUEST';

    /**
     * API request
     * @var const
     */
    const API_REQ = 'API_REQUEST';
 
    /**
     * Routes request
     * @var const
     */
    const ROUTES_REQ = 'ROUTES_REQUEST';

    /**
     * Page request
     * @var const
     */
    const PAGES_REQ = 'PAGES_REQUEST';

    /**
     * The type of request
     * @var string
     */
    private $requestType = self::PUBLIC_REQ;

    /**
     * The file name of the layout
     * @var string
     */
    private $layoutFile;

    /**
     * Layout Path to be loaded
     * @var string
     */
    private $layoutPath;

    /**
     * The matched route of the request
     * @var bool
     */
    private $route = false;

    /**
     * Custom Routes
     * @var array
     */
    private $routes = array();

    /**
     * Project configs
     * @var array
     */
    private $configs = array();

    /**
     * URL params
     * @var array
     */
    private $params = array();

    /**
     * Request
     * @var string
     */
    private $request;

    /**
     * If it is a public request, store paths in here
     * @var array
     */
    protected $allowPublicAccess = array('private','rf-api');
    
    /**
     * Slug of request
     * @var string
     */
    public $slug;

    /**
     * Symbolic Slug
     * @var string
     */
    protected $symSlug;

    /**
     * Should the project autoload the header?
     * @var bool
     */
    protected $autoload_header = true;

    /**
     * Should the project autoload the footer?
     * @var bool
     */
    protected $autoload_footer = true;

    public function __construct()
    {
        global $currentPage;
        $this->getRequestParams();
        $this->getConfigs();
        $this->resolveConfigs();
        $this->setRequestType();
        $this->resolveRequest();
        $currentPage = (object) array(
            'symSlug' => $this->symSlug,
            'slug' => $this->slug);
    }

    /**
     * Dispatch the layout
     * Grab symSlug specific xml content that can be pulled 
     * within layouts as objects 
     */
    public function __destruct()
    {
        // if request is public and has a valid path to load 
        // then don't try and find a 404
        if(in_array($this->requestType,array(self::PUBLIC_REQ,self::API_REQ)) && isset($this->includePath))
            die;
        global $pagesData;
        if($this->requestType===self::ROUTES_REQ)
            $this->resolveRouteDefaults();
        if(file_exists(RF_CONTENT))
            $content = new Load_XML(RF_CONTENT);
        if(file_exists(RF_FUNCTIONS))
            include_once RF_FUNCTIONS;
        $vars = array();
        $xmlPath = RF_PAGES_XML.DS.$this->slug.'.xml';
        if(
                in_array($this->requestType,array(self::PAGES_REQ,self::ROUTES_REQ)) 
                && isset($pagesData->{$this->slug}) 
                || $this->layoutFile==='404.php'
          ){
            $meta = (!isset($pagesData->{$this->slug}))?
                    array('title'=>'404','slug'=>'404')
                    :$pagesData->{$this->slug};  
            if(file_exists($xmlPath)){
                $content = new Load_XML($xmlPath);
                $vars = array_merge((array)$meta,(array)$content->xml);
            }else{
                $vars = isset($content->xml->{$this->symSlug})?
                    array_merge((array)$meta,(array)$content->xml->{$this->slug}):$meta;
            }
        }else{
            if(file_exists($xmlPath)){
                $content = new Load_XML($xmlPath);
                $vars = (array)$content->xml;
            }else{
                $vars = isset($content->xml->{$this->symSlug})?(array)$content->xml->{$this->slug}:array();
            }
        }
        $this->dispatchTemplate($this->layoutPath,$vars);
    }
    
    /**
     * Get the request params and format them:
     * 1. Remove empty parameters
     * 2. Normalise -'s
     * @return object $this
     */
    private function getRequestParams()
    {
        if(isset($_GET['request_url'])){
            $this->request = trim($_GET['request_url']);
            $vars = explode('/',$this->request);
            $this->params = array_filter($vars); 
            $this->params = array_map('RapidFrames\System\Router::utilityNormailiseDashes',$this->params);
        }
        return $this;
    }
    
    /**
     * Resolve configs
     * @return object $this
     */
    private function resolveConfigs()
    {
        // Resolve routes
        $this->getConfigRoutes();
        if(isset($this->configs['autoload']) && isset($this->configs['autoload']['headers']))
            $this->autoload_header = (bool)$this->configs['autoload']['headers'];
        if(isset($this->configs['autoload']) && isset($this->configs['autoload']['footers']))
            $this->autoload_footer = (bool)$this->configs['autoload']['footers'];
        return $this;
    }

    /**
     * Get the configs
     * @return object $this
     */
    private function getConfigs()
    {
        if(file_exists(RF_CONFIGS)){
            $configs = new \Zend_Config_Ini(RF_CONFIGS);
            $this->configs = $configs->toArray();
        }
        return $this;
    }

    /**
     * Get Routes but only if the route.ini file is present
     * @return object $this
     */
    private function getConfigRoutes()
    {   
        if(isset($this->configs['routes'])){
            $routes = $this->configs['routes'];
            if(is_array($routes) && count($routes)>0){
                foreach($routes as $layout => $regex){
                    $regex = str_replace('/','/',trim($regex,'/ '));
                    $this->routes[$regex] = str_replace('/','--',trim($layout,'/ '));
                }
            }
            return $this;
        }
    }

    /**
     * Check if current requests is valid by our regex
     * Loops throu user defined regex and breaks when true
     * @return object $this
     */
    private function resolveRequest()
    {   
        $this->slug = $this->getSlug();
        $this->symSlug = $this->getSymSlug();
        $this->layoutFile = $this->symSlug.'.php';
        switch($this->requestType){
            case self::API_REQ:
                $this->resolveApiRequest();
            case self::PAGES_REQ:
                $this->resolvePageRequest();
                break;
            case self::ROUTES_REQ:
                $this->resolveRouteRequest();
                break;
            case self::PUBLIC_REQ:
                $this->resolvePublicRequest();
                break;
        }
    }

    /**
     * Set Request Type
     * Loops throu user defined regex and breaks when true
     * @return object $this
     */
    private function setRequestType()
    {
        if($this->isRequestApi())
            $this->requestType = self::API_REQ;
        elseif($this->isRequestPublic())
            $this->requestType = self::PUBLIC_REQ;
        elseif($this->route = $this->isRequestRoute())
            $this->requestType = self::ROUTES_REQ;
        else $this->requestType = self::PAGES_REQ;
    }

    /**
     * Is the request public?
     * Folders that are not in the /Public are unreachable by request
     * Instead we need to virtually load them
     * @return bool true|false
     */
    private function isRequestPublic()
    {
        if(isset($this->params[0])&&in_array($this->params[0],$this->allowPublicAccess))
            return true;
        return false;
    }

    /**
     * Is this an api request?
     * @return bool true|false
     */
    private function isRequestApi()
    {
        if(isset($this->params[0])&&strtolower($this->params[0])==='rf-api')
            return true;
        return false;
    }

    /**
     * Is this a request route?
     * Check if current requests is valid by our regex
     * Loops throu user defined regex and breaks when true
     * @return mixed string|bool 
     */
    private function isRequestRoute()
    {
        foreach($this->routes as $match => $layout){
            if(preg_match("#^$match#",$this->request))
                return $layout;
        }
        return false;
    }

    /**
     * Utility Normailise Dashes
     * format slugs to convert --etc to just -
     * @param string $item
     * @return string the normalised string
     */
    private static function utilityNormailiseDashes($item)
    {    
        return preg_replace('/[\-]+/','-',trim($item,'/'));
    }

    /**
     * Get Symbolic Slug of current request
     * @return string $page
     */
    public function getSymSlug()
    {
        return str_replace('/','--',$this->getSlug());
    }

    /**
     * Get Slug of current request
     * @return string $page
     */
    public function getSlug()
    {
        $page = implode('/',$this->params);
        return $page&&$page!=='index.php'?strtolower($page):'home';
    }

    /**
     * Load Public Request
     * Load up resources that are within the project folder virtually
     * @return mixed 
     */
    private function resolveApiRequest()
    {
        $path = RF_SYSTEM.DS.'API'.DS.'Serve.php';
        if(!is_dir($path) && file_exists($path)){
            $this->includePath = $path;
            require_once($this->includePath);
            die;
        }
        return false;
    }

    /**
     * Load Public Request
     * Load up resources that are within the project folder virtually
     * @return mixed 
     */
    private function resolvePublicRequest()
    {
        $path = RF_PROJECT.DS.$this->request;
        if(!is_dir($path) && file_exists($path)){
            $this->includePath = $path;
            $file = pathinfo($this->includePath);
            $data = \RapidFrames\System\DataCSV::getMimeTypes();
            $mime = isset($data['.'.$file['extension']])?$data['.'.$file['extension']]:'';
            if($mime){
                header("Accept-Ranges: bytes");
                header(sprintf("Content-type: %s",$mime));
                header(sprintf("Content-Length: %s",filesize($this->includePath)));
                echo file_get_contents($this->includePath);
            }else require_once($this->includePath);
            die;
        }
        // If no public path found then we are dealing with a 404
        $this->resolve404();
        return false;
    }

    /**
     * Resolve Route Request
     * The page reference must exist along side the route
     * @return object $this
     */
    private function resolveRouteRequest()
    {
        if($this->route){
            $this->layoutFile = $this->route.'.php';
            $this->setLayoutPath();
        }
        $this->resolve404();
        return $this;
    }

    /**
     * Resolve Route Request
     * The page reference must exist along side the route
     * @global object $pagesData
     * @return object $this
     */
    private function resolveRouteDefaults()
    {
        global $pagesData;
        if(!isset($pagesData->{$this->slug})){
            $this->title = $this->titleize(end($this->params));
        }
        return $this;
    }
    
    /**
     * Resolve The 404
     * Request is a route but 
     * 1. does not have a template to route to and or
     * 2. does not have a page reference
     * @global object $pagesData
     * @return object $this
     */
    private function resolve404()
    {
        global $pagesData;
        if(!$this->layoutPath || ($this->requestType!==self::ROUTES_REQ && !isset($pagesData->{$this->slug}))){
            $this->slug = '404';
            $this->symSlug = '404';
            $this->layoutFile = '404.php';
            $this->layoutPath = RF_LAYOUTS.DS.'404.php';
        }
        return $this;
    }

    /**
     * Resolve Page Request
     * The page reference must exists
     * @global object $pagesData 
     * @return object $this
     */
    private function resolvePageRequest()
    {
        global $pagesData;
        if(isset($pagesData->{$this->slug})){   
            $page = $pagesData->{$this->slug};
            if(isset($page->template) && $page->template)
                $this->layoutFile = 'tpl.'.$page->template.'.php';
            $this->setLayoutPath();
        }
        $this->resolve404();    
        return $this;
    }
 
    /**
     * Dispatch Defaults
     * Projects should usually contains
     * - Home 
     * - Sitemap
     * - 404, 403, 500 error splash pages
     */
    private function dispatchDefaults()
    {
        if(isset($this->params[0])){
            switch($this->params[0]){
                case 'home':
                case 'index.php':
                    $this->title = 'Home';
                    $this->slug = 'home';
                    $this->symSlug = 'home';
                    break;
                case 'sitemap':
                case 'style-guide':
                case '500':
                case '403':
                case '404':
                    $this->title = $this->titleize($this->params[0]);
                    $this->slug = $this->params[0];
                    $this->symSlug = $this->params[0];
                    break;
                default:
                    $this->title = '404';
                    $this->slug = '404';
                    $this->symSlug = '404';
                    break;
            }
            $this->layoutPath = RF_SYSTEM.DS.'Defaults.php';
            $this->layoutFile = 'Default.php'; 
        }
        require_once($this->layoutPath);
        die;
    }

    /**
     * Get the layout path if it exists
     * This method supports loading nested sub directories:
     * /symSlug.php or /slug.php
     * /dir/symSlug.php or /dir/slug.php 
     * /dir/dir2/symSlug.php or /dir/dir2/slug.php
     * etc...
     * @param string $layout
     * @return mix, attempting to try and get the layou path if it exists
     */
    private function setLayoutPath()
    {
        $layoutPath = RF_LAYOUTS.DS.$this->layoutFile;
        if(file_exists($layoutPath)){
            $this->layoutPath = $layoutPath;
            return true;
        }
        // Try to discover which directories exist
        $layoutPath = RF_LAYOUTS;
        $i = 0;
        // Routes params are that of the layoutfile
        $params = $this->params;
        if($this->requestType===self::ROUTES_REQ){
            $route = str_replace('--','/',$this->route);
            $params = explode('/',$route);
        }
        foreach($params as $folder){
            $layoutPath.= DS.$folder;
            if(is_dir($layoutPath)){
                $i++;
                // All directories were found. 
                // We are looking for a {layout}.php file or {layout}/index.php file
                if($i===count($params)){
                    // We take preference in named php files...
                    if(file_exists($layoutPath.'.php')){
                        $this->layoutPath = $layoutPath.'.php';;
                        $this->layoutFile = end($params).'.php';
                        return true;
                    }
                    // ...But index.php will suffice. 
                    if(file_exists($layoutPath.DS.'index.php')){
                        $this->layoutPath = $layoutPath.DS.'index.php';;
                        $this->layoutFile = end($params).DS.'index.php';
                        return true;
                    }
                }else{
                    $_layoutFile = implode('--',array_slice($params,$i));
                    $_layoutPath = $layoutPath.DS.$_layoutFile.'.php';
                    if(file_exists($_layoutPath)){
                        $this->layoutPath = $_layoutPath;
                        $this->layoutFile = $_layoutFile;
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Dispatch the template
     * At this stage all checks have been made but we cannot be sure that the
     * basic templates dont exist. Rather than
     * @param string $layout
     * @return mix, attempting to try and get the layou path if it exists
     */
    public function dispatchTemplate($path, $vars = array())
    {   
        foreach($vars as $key => $var)
            $this->{$key} = $var;
        if(file_exists($path)){  
            ob_start();
            require_once $path;
            $contents = ob_get_clean();
            if(!isset($this->blocksCalled['header'])){
                if($this->autoload_header)
                $contents = $this->getBlock('header',false).$contents;
            }
            if(!isset($this->blocksCalled['footer'])){
                if($this->autoload_footer)
                $contents = $contents.$this->getBlock('footer',false);
            }
            echo $contents;
        }else $this->dispatchDefaults();
    }   

    /**
     * Get breadcrumb
     * @global object $pagesData
     * @param string $orderby needs to be the column name
     * @param string $order the asc,desc 
     */
    public function getBreadcrumb()
    {
        global $pagesData;
        $page = $this->getPage();
        if(!$page) 
            return false;
        if(!isset($page->slug)&&$page->slug==='home')
            return false;
        if(!isset($page->slug)&&!$page->slug)
            return false;
        $slugs = array_reverse($page->ancestors);
        $title = $this->getPage('home')->title?$this->getPage('home')->title:'Home';
        printf('<ul class="breadcrumb"><li><a href="/">%s</a><span class="divider">/</span></li>',$title);
        foreach($slugs as $slug)
            printf('<li><a href="%s">%s</a><span class="divider">/</span></li>',$pagesData->{$slug}->permalink,$pagesData->{$slug}->label);
        printf('<li>%s</li></ul>',$page->title);
    }

    /**
     * Get either the current page object or a specified page
     * @global object $pagesData
     * @param string $slug needs to be the column name
     * @return object
     */
    public function getPage($slug='')
    {
        global $pagesData;
        if(!$slug){
            if(isset($pagesData->{$this->symSlug}))
                return $pagesData->{$this->symSlug};
        }else{
            if(isset($pagesData->{$slug}))
                return $pagesData->{$slug};
        }
    }

    /**
     * Get a block of code 
     * @package router
     * @param string $name
     * @return mixed 
     */
    public function getBlock($name, $echo = true, $vars = array())
    {
        $path = RF_BLOCKS.DS.$name.'.php';
        // What blocks have been called?
        $this->trackBlockUsage($name);
        // load the block
        if(file_exists($path)){
            ob_start();
            include($path);
            $contents = ob_get_clean();
            if($echo)
                echo $contents;
            else return $contents;
        }else Alert::fatal("$name.php does not exist");
        return false;
    }

    /**
     * Track Block usage for this page
     * @param string $name
     * @return object $this
     */
    private function trackBlockUsage($name)
    {
        $root = explode('/',$name);
        $root = array_shift($root);
        if(isset($this->blocksCalled[$root]))
            $this->blocksCalled[$root]++;
        else $this->blocksCalled[$root] = 1;
        if(isset($this->blocksCalled[$name]))
            $this->blocksCalled[$name]++;
        else $this->blocksCalled[$name] = 1;
        return $this;
    }
    /**
     * Get a block of code and repeat it
     * @param string $name
     * @param string $handlers callbacks to handle epressions uniquely
     * @param mixed $collection array|object
     * @param string $echo 
     * @return mixed string|bool
     */
    public function getRepeaterBlock($name, $collection, $handlers = array(), $echo = true)
    {
        $path = RF_BLOCKS.DS.$name.'.php';
        $this->trackBlockUsage($name);
        // load the block
        if(file_exists($path)){
            ob_start();
            include($path);
            $contents = ob_get_clean();
            $contents = trim(preg_replace('/\s+/', ' ', $contents));
            // Match these tags
            $repeatOpeningTag = '<\!\-\-start\:rf\-repeat\-\-\>';
            $repeatClosingTag = '<\!\-\-end\:rf\-repeat\-\-\>';
            // Give them a unique ID
            $mktime = mktime();
            $repeatOpeningTagIDRegex = "(\<\!\-\-start\:rf\-repeatID$name-$mktime\-\-\>)";
            $repeatOpeningTagIDReplace = "<!--start:rf-repeatID$name-$mktime-->";
            $repeatClosingTagIDRegex = "(\<\!\-\-end\:rf\-repeatID$name-$mktime\-\-\>)";
            $repeatClosingTagIDReplace = "<!--end:rf-repeatID$name-$mktime-->";
            // Replace into unique ID's
            $contentsReTagged = preg_replace(
                    "/$repeatOpeningTag/", 
                    $repeatOpeningTagIDReplace, 
                    $contents); 
            $contentsReTagged = preg_replace(
                    "/$repeatClosingTag/", 
                    $repeatClosingTagIDReplace, 
                    $contentsReTagged); 
            $regex = "#$repeatOpeningTagIDRegex(.*\n?)$repeatClosingTagIDRegex#";
            if(!preg_match($regex,$contentsReTagged,$matches))
                    return false;
            // We have matched and retrieved string to replace
            $repeaterString = array_shift($matches);
            $repeaterInstance = new \RapidFrames\System\Repeater($collection, $repeaterString, $handlers);
            $rendered = $repeaterInstance->render();
            // Only match the first instance
            $contents = preg_replace($regex, $rendered, $contentsReTagged, 1);
            if($echo)
                echo $contents;
            else return $contents;
        }else Alert::fatal("$name.php does not exist");
        return false;
    }
    
    /**
     * Add Methods on the fly
     * @param string $method
     * @param array $args
     * @return closure 
     * @throws Exception if method is not set
     */
    public function __call($method, $args)
    {
        if(isset($this->$method))
            return call_user_func_array($this->$method, $args);
        else throw new \Exception("$method does not exist");
    }
    
    /**
     * Helpers::Slugify
     * @param string $string
     * @param string $separator
     * @return string
     */
    public function slugify($string, $separator='-')
    {
        if(!in_array($separator,array('-','_')))
                $separator = '-';
        $replace = $separator==='-'?'_':'-';
        $string = str_replace($replace,$separator,$string);
        $string = preg_replace('/[^A-Za-z0-9-]+/', $separator, strtolower($string)); 
        return trim($string,$separator);
    }
    
    /**
     * Helpers::Titleize
     * @param string $string
     * @return string
     */
    public function titleize($string) {
        
        $string = preg_replace('/[\_\-]/',' ', $string);
        $string = preg_replace('/[A-Z]/', ' $0', $string);
        $string = trim(str_replace('_', ' ', $string));
        $string = ucwords($string);
        return $string;
    }
}