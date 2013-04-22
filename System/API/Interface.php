<?php
//error_reporting(E_ALL);
class API
{
    private $allowedMethods = array('get','symslug','orderby','order','format','name','level','count');
    static $params;
    /**
     * Fetch the data
     * @params bool $printRequest
     * @return string $data
     */
    public function fetchData($printRequest=false)
    {
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
        $query = http_build_query(self::$params);
        $url = sprintf('%s%s/rf-api?%s',$protocol,$_SERVER['SERVER_NAME'],$query);
        if($printRequest)
            echo $url.'<br/>';
        // Check if curl installed
        if  (!in_array  ('curl', get_loaded_extensions())){
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            curl_close($ch);
            // Reset the params to ommit chaining on same instance
            self::$params = array();
            return $data;
        }
        else{
            require_once(RF_SYSTEM.DS.'API'.DS.'Controller.php');
            $api = new API_Controller(self::$params,false);
            if(file_exists(RF_INC.DS.'api.php'))
                include RF_INC.DS.'api.php';
            $content = $api->dispatch(self::$params,false);
            self::$params = array();
            return $content;
        }
    }
    
    /**
     * Build params
     * @return object $this;
     */
    public function __call($method, $args)
    { 
        if(in_array($method,$this->allowedMethods))
        self::$params[$method] = array_shift($args);
        return $this;
    }
}