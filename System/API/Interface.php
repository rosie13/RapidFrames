<?php
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
        $url = sprintf('%s%s/rf-api?%s',$protocol,$_SERVER['SERVER_NAME'],implode('&',self::$params));
        if($printRequest)
            echo $url.'<br/>';
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
        // Reset the params to ommit chaining
        self::$params = array();
	return $data;
    }
    
    /**
     * Fetch the data
     * 
     * @return object $this;
     */
    public function __call($method, $args)
    { 
        if(in_array($method,$this->allowedMethods))
        self::$params[$method] = $method.'='.$args[0];
        return $this;
    }
}