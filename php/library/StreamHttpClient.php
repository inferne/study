<?php 

/**
 * 
 * @author liyunfei
 * @version 1.0
 * @desc this class support http long connect
 */
 
class StreamHttpClient 
{
    private $port       = 80;
    private $timeout       = 1;
    private $length = 8196;
    private $url;
    private $user_agent = "driver_settlement";

    public function __construct($url){
        $this->url = parse_url($url);
    }

    public function get($params = array()){
        $query = (isset($this->url['query']) ? $this->url['query']."&" : "?") . http_build_query($params);
        $context  = "GET ".$this->url['path'].$query." HTTP/1.1\r\n";
        $context .= $this->build_header();
        $context .= "\r\n";
        
        $response = $this->request($context);
        $result = $this->parse($response);
        return $result;
    }

    public function post($params = array()){
        $data = http_build_query($params);
        $query = $this->url['query'];
        $context  = "POST ".$this->url['path'].$query." HTTP/1.1\r\n";
        $context .= $this->build_header();
        $context .= "Content-Length: ".strlen($data)."\r\n\r\n";
        
        if(strlen($data) > 0){
            $context .= $data."\r\n\r\n";
        }
        //echo $context;
        $response = $this->request($context);
        $result = $this->parse($response);
        return $result;
    }
    
    public function build_header(){
        $context  = "Accept: */*\r\n";
        $context .= "Host: ".$this->url['host']."\r\n";
        $context .= "User-Agent: ".$this->user_agent."\r\n";
        $context .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $context .= "Connection: Keep-Alive\r\n";
        return $context;
    }
    
    /**
     * send http request
     * @param unknown $context
     */
    public function request($context){
        $fp = stream_socket_client("tcp://".$this->url['host'].":".$this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT);
        //send context to server
        fwrite($fp, $context);
        //read server response
        $response = '';
        while (!feof($fp)){
            $response .= fread($fp, $this->length);
        }
        //when service close Connection
        if(strpos("Connection: close", $response)){
            fclose($fp);
        }
        
        return $response;
    }
    
    public function parse($response){
        $response = explode("\r\n\r\n", $response);

        $header = $response[0];
        $data = $response[1];
        
        $http_code = substr($header, 9, 3);
        
        return $data;
    }
}
