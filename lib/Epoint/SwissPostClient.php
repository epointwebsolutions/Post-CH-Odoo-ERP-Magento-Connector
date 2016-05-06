<?php
/**
 * Client SwissPost 
 *
 */
class Epoint_SwissPostClient{
  /**
   * User id get it from authentication method
   *
   * @var string
   */
  private $uid = '';  
  /**
   * Session id provided from json response.
   *
   * @var string
   */
  private $session_id = '';
  /**
   * md5 connection hash
   *
   * @var string
   */
  private $md5 = '';
  private $url = '';
  private $username = '';
  private $password = '';
  private $db = '';
  private $shop_ident = '';
  private $jsonrpc = '2.0';
  private $base_location = '';
  private $exceptionLogger ;
  private $Logger;
  private $curlResource;
  private $debug = array();
  private $authResult = array();
  /**
   * session cookie path
   *
   * @var string
   */
  private $cookie_file_path = '';
 
  /**
   * Implement constructor
   *
   */
  public function __construct($options){
  	// Service entry point
    if($options['url']){
      $this->url = $options['url'];
    }    
    // Connect username.
    if($options['username']){
      $this->username = $options['username'];
    }
    // Connect password.
    if($options['password']){
      $this->password = $options['password'];
    }
    if($options['db']){
      $this->db = $options['db'];
    }
    if($options['shop_ident']){
      $this->shop_ident = $options['shop_ident'];
    }
    if($options['jsonrpc']){
      $this->jsonrpc = $options['jsonrpc'];
    }
    if($options['exceptionLogger']){
      $this->exceptionLogger = $options['exceptionLogger'];
    }
    if($options['Logger']){
      $this->Logger = $options['Logger'];
    }
    if($options['base_location']){
      $this->base_location = $options['base_location'];
    }
    /**
     * Was not created a valid session, create new. 
     */
    if(!$this->cookie_file_path){
      $this->cookie_file_path = tempnam(sys_get_temp_dir(), 'epoint_swisspost').'.txt';
    }
  }
  /**
   * Close resource on destruct
   *
   */
  public function __destruct(){
    if($this->curlResource){
      curl_close($this->curlResource);
    }
  }
  /**
   * Call the service
   *
   */
  public function Call($method_url, $data = array()){
    // Curent auth is invalid ?
    if(!$this->session_id || !$this->md5 || !file_exists($this->cookie_file_path) || $this->md5!=md5($this->session_id, file_get_contents($this->cookie_file_path))){
      if(!$this->connect()){
      	$debug = $this->debug[sizeof($this->debug) -1]; 
      	$result_data = $this->authResult[sizeof($this->authResult) -1]; 
        return  new SwissPostResult($result_data, $debug);
      }
    }
    // Add global params
    $data['session_id'] = $this->session_id;
    $data['shop_ident'] = $this->shop_ident;
    $result_data = $this->__callService($method_url, $data);
    // Return result object.
    $debug = $this->debug[sizeof($this->debug) -1]; 
    return new SwissPostResult($result_data, $debug);
  }
  /**
   * API Call
   *
   * @param method $method
   * @param array $data
   * @return array
   */
  private function __callService($method_url, $data){
	  try {
		// Build base service data.
		$args = array(
			"jsonrpc" => $this->jsonrpc,
			"id"      => "".rand(0, 10000),
			"method"  => 'call',
			"params"  => $data,
		);
		$url = rtrim($this->url, '/').'/'.$method_url;
		$output = $this->curl($url, $args);
		$result = array();
		if($output){
		  $result = json_decode($output, TRUE);
		}
		if(!is_array($result)){
			$this->Logger->log('Error getting json content from SwissPost API: %s', $output);
		}
		return $result;
	  }catch (Exception $e){
	    if($this->exceptionLogger){
	     $this->exceptionLogger->logException($e);
	    }
	  }
	  return array();
   }
   /**
    * Enter description here...
    *
    * @return unknown
    */
   private function getCurlResource(){
      if(!$this->curlResource){
        $cookie = $this->cookie_file_path;
        $this->curlResource = curl_init();
        curl_setopt($this->curlResource, CURLOPT_POST, 1);
        curl_setopt($this->curlResource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curlResource, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curlResource, CURLOPT_ENCODING, 1);
        curl_setopt($this->curlResource, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->curlResource, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($this->curlResource, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($this->curlResource, CURLOPT_COOKIEFILE, $cookie); 
      }
      return $this->curlResource;
   } 
   /**
    * Curl service url
    *
    * @param string $url
    * @param array args
    * @return array
    */
   public function curl($url, $args = array()){
     try {
      $data = json_encode($args);
      $cookie = $this->cookie_file_path;
      $this->curlResource = $this->getCurlResource();
      curl_setopt($this->curlResource, CURLOPT_URL, $url);
      curl_setopt($this->curlResource, CURLOPT_POSTFIELDS, $data);
      $headers = array(
        'Content-type: application/json',
        'Content-Length: '.strlen($data),
      );
      curl_setopt($this->curlResource, CURLOPT_HTTPHEADER, $headers); 
      $result = curl_exec($this->curlResource);
      $information = curl_getinfo($this->curlResource);
      $this->debug[] = array(
        //'info'=>$information, 
        'url'=> $url, 
        'data'=>print_r(json_decode($data, TRUE), 1), 
        //'args'=>$args, 
        'result'=>$result, 
        //'cookie'=>$args,
      );
      
      if($result === FALSE){
        throw new Exception(sprintf("cURL error, no %s, error: %s, info: %s", curl_errno($this->curlResource), curl_error($this->curlResource), json_encode($information)));
      }
      return $result;
     }catch (Exception $e){
       if($this->exceptionLogger){
	       $this->exceptionLogger->logException($e);
	     }
     }
     return FALSE;
   }
   /**
    * Get result
    *
    * @param array $result
    * @return array
    */
   public function getResult($result){
     return isset($result['result']) ? $result['result'] : array() ;
   }
   /**
    * Connect to API set cookie and 
    *
    */
   public function connect(){
    $result = $this->__callService('web/session/get_session_info', array('session_id'=>null,'context'=>new stdClass()), 0);
    $this->authResult[] = $result;
    // Validate response.
    if(isset($result['error'])){
      	if($this->Logger){
	   		$this->Logger->log('Error connecting to SwissPost API: %s', $result['error']);
	    }
      	return false;
    }
    $data = $this->getResult($result);
    $this->session_id = $data['session_id'];
    $this->md5 = md5($this->session_id, @file_get_contents($this->cookie_file_path));
    // Add auth
    /**
     * curl -i -d '{"jsonrpc": "2.0",
           "id": 2,
           "method": "call",
           "params": {
               "db": "db_name",
               "login": "my_login",
               "password": "my_password",
               "base_location": "http://localhost:8069",
               "session_id": "1c14e4c48b4c4cb68e737b4a9dd8067d"
            }}' \
        -b sid=6b1dc65ccfb8cdd14e3a91fb843569a67e9182f5 \
        localhost:8069/web/session/authenticate
     */
    $result = $this->__callService('web/session/authenticate', array(
          'db'              => $this->db, 
          'login'           => $this->username,
          'password'        => $this->password,
          'base_location'   => $this->base_location,
          'session_id'      => $this->session_id, 
         ), 
      0
    );
    $this->authResult[] = $result;
    // Validate response.
    if(isset($result['error'])){
       if($this->Logger){
	   	$this->Logger->log('Error authenticate to SwissPost API: %s', $result['error']);
	   }
      return FALSE;
    }
    $data = $this->getResult($result);
    if($data && $data['uid']){
      $this->uid = $data['uid'];
      return TRUE;
    }
    return FALSE;
  }
  /**
 * Extract any cookies found from the cookie file. This function expects to get
 * a string containing the contents of the cookie file which it will then
 * attempt to extract and return any cookies found within.
 *
 * @param string $string The contents of the cookie file.
 * 
 * @return array The array of cookies as extracted from the string.
 *
 */
function extractCookies() {
    $string = @file_get_contents($this->cookie_file_path);
    $lines = explode(PHP_EOL, $string);
    foreach ($lines as $line) {

        $cookie = array();

        // detect httponly cookies and remove #HttpOnly prefix
        if (substr($line, 0, 10) == '#HttpOnly_') {
            $line = substr($line, 10);
            $cookie['httponly'] = true;
        } else {
            $cookie['httponly'] = false;
        } 

        // we only care for valid cookie def lines
        if( strlen( $line ) > 0 && $line[0] != '#' && substr_count($line, "\t") == 6) {
            // get tokens in an array
            $tokens = explode("\t", $line);

            // trim the tokens
            $tokens = array_map('trim', $tokens);

            // Extract the data
            $cookie['domain'] = $tokens[0]; // The domain that created AND can read the variable.
            $cookie['flag'] = $tokens[1];   // A TRUE/FALSE value indicating if all machines within a given domain can access the variable. 
            $cookie['path'] = $tokens[2];   // The path within the domain that the variable is valid for.
            $cookie['secure'] = $tokens[3]; // A TRUE/FALSE value indicating if a secure connection with the domain is needed to access the variable.

            $cookie['expiration-epoch'] = $tokens[4];  // The UNIX time that the variable will expire on.   
            $cookie['name'] = urldecode($tokens[5]);   // The name of the variable.
            $cookie['value'] = urldecode($tokens[6]);  // The value of the variable.

            // Convert date to a readable format
            $cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);

            // Record the cookie.
            $cookies[] = $cookie;
        }
    }
	 return $cookies;
	}
}
/**
 * Result wrapper
 *
 */
class SwissPostResult extends Varien_Object implements ApiResult {
	/**
	 * Result of Client call
	 *
	 * @var array
	 */
	private $result = array();
	private $error = array();
	private $debug = array();
	private $isOK = FALSE;
	/**
	 * Constructor
	 *
	 * @param array $result
	 */
	public function __construct($result = array(), $debug = array()){
		// Default, the result is a failure.
		$this->isOK = FALSE;
		if(isset($result['result'])){
			$this->result = $result['result'];
			if($this->result && is_array($this->result)){
				if(@strtolower($this->result['status']) == 'ok'){
					$this->isOK = TRUE;	
				}
			}
		}elseif (isset($result['error'])){
			$this->error[] = $result['error'];
			
		}else{
			//throw new Exception('Invalid result data:'.json_encode($result));
			$this->error[] = 'Invalid result data:'.json_encode($result);
		}
		// Add debug message.
		$this->debug = $debug;
	}
	
   /**
    * Check if the call response is ok
    *
    * @param array $result
    * @return boolean
    */
   public function isOK(){
   	return $this->isOK;
   }
   /**
    * Get result values
    *
    */
   public function getValues(){
   	if($this->isOK() && isset($this->result['values'])){
   		return $this->result['values'];
   	}
   	return array();
   }
   /**
    * Get result
    *
    */
   public function getResult($field = ''){
   	if($this->result){
   		if($field){
   			if(isset($this->result[$field])){
   				return $this->result[$field];
   			}
   		}else{
   			return $this->result;
   		}
   	}
   	return array();
   }
   /**
    * Get result error
    *
    */
   public function getError(){
   	return $this->error;
   }
   /**
    * Get result debug
    *
    */
   public function getDebug(){
   	return $this->debug;
   }
}
/**
 * Define a base APi result interface
 *
 */
interface ApiResult{
	function getError();
	function getDebug();
	function getResult();
	function isOK();
}