<?php 
/**
 * Simple Api requests for grabbing social media stats concerning following
 * @author Mwayi Dzanjalimodzi
 * 
 */
namespace Resources\Api;

class Social
{
    /**
     * Fetch HTTP response data
     * 
     * @params string $url
     */
    private function fetchData($url)
    {
        // Check if curl installed
        if  (in_array('curl', get_loaded_extensions())){
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }
        
        return false;
    }
    
    /**
     * Count twitter followers
     * 
     * @params string $username
     * @params string $protocol
     * @return string $data
     */
    public function getTwitterFollowers($username, $protocol = 'https')
    {
        $url = sprintf('%s://api.twitter.com/1/users/show.json?screen_name=%s',$protocol, $username);
        $json = $this->fetchData($url);
        $data = json_decode($json);
        if(is_object($data) && isset($data->followers_count))
        return $this->prettyNumbers($data->followers_count);    
    }
    

    /**
     * Count Youtube subscribers
     * 
     * @params string $username
     * @params string $protocol
     * @return string $data
     */
    public function getYoutubeSubscribers($username, $protocol = 'https')
    {
        $url = sprintf('%s://gdata.youtube.com/feeds/api/users/%s?&alt=json',$protocol, strtolower($username));
        $json = $this->fetchData($url);
        $data = json_decode($json);
        $statistics = 'yt$statistics';
        if(is_object($data) && isset($data->entry->$statistics->subscriberCount))
        return $this->prettyNumbers($data->entry->$statistics->subscriberCount);
        return false;
    }
    
    /**
     * Count facebook page likes
     * 
     * @params string $username
     * @params string $protocol
     * @return string $data
     */
    public function getFacebookPageLikes($pageid, $protocol = 'https')
    {
        $url = sprintf('%s://graph.facebook.com/%s/',$protocol, $pageid);
        $json = $this->fetchData($url);
        $data = json_decode($json);
        if(is_object($data) && isset($data->likes))
        return $this->prettyNumbers($data->likes);    
    }
    
    /**
     * Render a pretty numbers up to millions
     * 
     * @params int $number
     * @return string $data
     */
    private function prettyNumbers($number)
    {   
        if($number/1000<0.5)
            $type = '<0.5k';
        elseif(($number/1000>=0.5) && ($number/1000000 < 0.5))
            $type = '>=0.5k&<0.5m';
        elseif(($number/1000000>=0.5) && ($number/1000000 > 0.5))
            $type = '>=0.5m';
        switch ($type)
        {
            // Lower than 0.5k
            case '<0.5k':
                $word = $number;
                break;
            // Greater than 0.5k but less than half a mill
            case '>=0.5k&<0.5m':
                $word = round($number/1000,1).'k';
                break;
            // Anything greater than a mill
            case '>=0.5m':
                $word = round($number/1000000,1).'m';
                break;
        }
        return $word;
    }
}

$arr = new Social;

echo $arr->getYoutubeSubscribers('bendeignan');
echo $arr->getFacebookPageLikes('225397695229');