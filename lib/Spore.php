<?php

/**
 * TODO
 *
 * manage exception :
 * include_once 'Zend/Service/Spore/Exception.php';
 *
 *
 *
 */
require_once '../lib/RESTHttpClient.php';

class Spore_Exception extends Exception {

}

class Spore
{

    protected $_specs;
    protected $_client;
    protected $_methods;
    protected $_method_spec;
    protected $_format;
    protected $_host;
    protected $_base_url;
    protected $_request_path;
    protected $_request_params;
    protected $_request_raw_params;
    protected $_request_method;
    protected $_middlewares;
    protected $_httpClient = null;


    protected $_response;

    /**
     * Constructor
     *
     * @param  string $username
     * @param  string $password
     * @return void
     */
    public function __construct($spec_file = '')
    {
        $this->init($spec_file);
        $this->_request_params = array();
        $this->_middlewares = array();
    }

    /**
     * Initialize Spore with spec file
     *
     * @return void
     */
    public function init($spec_file = '')
    {
        if (empty($spec_file))
          throw new Spore_Exception('Initialization failed: spec file is not defined.');

        // load the spec file
        $this->_load_spec($spec_file);



        $this->_init_client($this->_specs['base_url']);

    }

    /**
     * Enable middleware
     * 
     * @param unknown_type $middleware
     * @param unknown_type $args
     */
    public function enable($middleware, $args)
    {
        // create middleware obj
        $m = new $middleware($args);

        // add to middleware array
        array_push($this->_middlewares, $m);
    }


    /**
     * Load spec file
     *
     * @param   string  $spec_file
     * @return  array   $specs
     */
    protected function _load_spec($spec_file)
    {
        // load file and parse/decode
        if (preg_match("/\.(json|yaml)$/i", $spec_file, $matches))
        {
            $spec_format = $matches[1];
            $specs_array = $this->_parse_spec_file($spec_file, $spec_format);

            if (!isset($specs_array['methods']))
              throw new Spore_Exception('No method has been defined in the spec file: ' . $spec_file);

            // save the specs
            $this->_specs = $specs_array;

        } else {
            throw new Spore_Exception('Unsupported spec file: ' . $spec_file);
        }

    }

    protected function _parse_spec_file($spec_file, $spec_format)
    {
        if (file_exists($spec_file))
        {
            switch($spec_format)
            {
                case 'json':
                    // read the spec file
                    $fp = fopen($spec_file, 'r');
                    if (!$fp)
                        throw new Spore_Exception('Unable to open file: ' . $spec_file);

                    $specs_text = '';
                    while (!feof($fp))
                    {
                        $specs_text .= fgets($fp, 1024);
                    }
                    fclose($fp);

                    // decode the json text
                    $specs_obj = json_decode($specs_text);
                    $specs_array = object_to_array($specs_obj);
                    return $specs_array;
                    break;

                case 'yaml':
                    $specs_array = yaml_parse_file($spec_file);
                    return $specs_array;
                    break;

                default:
                    throw new Spore_Exception('Unsupported spec file: ' . $spec_file);
            }
        } else {
            throw new Spore_Exception('File not found: ' . $spec_file);
        }
    }

    /**
     * initialize REST Http Client
     *
     * @param   string  $spec_file
     * @return  array   $specs
     */
    protected function _init_client()
    {
        $base_url = $this->_specs['base_url'];
        $this->_base_url = $base_url;
        $client = RESTHttpClient::connect($base_url);
        $client->setHeaders('Accept-Charset', 'ISO-8859-1,utf-8');
        #TODO: manage exception
        $this->_client = $client;
    }

    /**
     * Method overloading
     *
     * @param  string $method
     * @param  array $params
     * @return object
     * @throws Zend_Service_Spore_Exception if unable to find method
     */
    public function __call($method, $params)
    {
        // check if method exists
        if (!isset($this->_specs['methods'][$method])) {
            throw new Spore_Exception('Invalid method "' . $method . '"');
        }

        // create the method on request / on the fly
        $this->_exec_method($method, $params);

        return $this->_response;
    }


    /**
     * Execute a client method
     * 
     * @param object $method
     * @return void
     */
    protected function _exec_method($method, $params)
    {
      // set method spec
      $this->_setMethodSpec($this->_specs['methods'][$method]);

      // set request method
      $this->_setRequestMethod($this->_specs['methods'][$method]['method']);

      // prepare the params
      $this->_prepareParams($method, $params);

      // execute all middlewares
      foreach ($this->_middlewares as $middleware)
      {
          $middleware->execute($this);
      }

      // send request
      $rest_response = null;
      switch(strtoupper($this->_request_method))
      {
      case 'POST':
        $rest_response = $this->_performPost('POST', $this->_request_path, $this->_request_raw_params);
        break;
      case 'PUT':
        $rest_response = $this->_performPost('PUT', $this->_request_path, $this->_request_raw_params);
        break;
      case 'DELETE':
        $rest_response = $this->_performDelete($this->_request_path, $this->_request_params);
        break;
      case 'GET':
        $rest_response = $this->_performGet($this->_request_path, $this->_request_params);
        break;
        
      default:
        $rest_response = $this->restGet($this->_request_path, $this->_request_params);
      }

      // set response
      $this->setResponse($rest_response);

      $this->_request_params = '';
    }

    protected function _setMethodSpec($spec)
    {
        $this->_method_spec = $spec;
    }

    protected function _setRequestMethod($request_method)
    {
        $this->_request_method = $request_method;
    }

    protected function _prepareParams($method, $params)
    {
      // get path
      $this->_request_path = $this->_base_url . $this->_specs['methods'][$method]['path'];

      // add required params into the path
      $required_params = array();
      if (isset($this->_specs['methods'][$method]['required_params'])) {
        foreach ($this->_specs['methods'][$method]['required_params'] as $param)
          {
            if (!isset($params[0][$param]))
              throw new Spore_Exception('Expected parameter "' . $param . '" is not found.');

            $this->_insertParam($param, $params[0][$param]);
            array_push($required_params, $param);
          }
      }

      // add the rest of the params into the path
      foreach ($params[0] as $param => $value)
      {
        if (!in_array($param, $required_params))
        {
          $this->_insertParam($param, $value);
        }
      }

      // format
      $this->_format = (isset($params[0]['format'])) ? $params[0]['format'] : 'json';

      // also generate raw params from the request params array
      $this->_setRawParams($this->_request_params);
    }

    protected function _insertParam($param, $value)
    {
      if (empty($value))
        return;

      if (strstr($this->_request_path, ":$param"))
      {
        $this->_request_path  = str_replace(":$param", $value, $this->_request_path);
      } else {
        $this->_request_params[$param] = $value;
      }

    }

    protected function _setRawParams($params = array())
    {
      $raw_params = '';
      foreach ($params as $key => $value)
      {
        $raw_params .= empty($raw_params) ? '' : '&';
        $raw_params .= "$key=$value";
      }
      $this->_request_raw_params = $raw_params;
    }

	/*
	 * Use our own performPost() for PUT/POST method, since Zend_Rest_Client's restPut() always reset the
	 * content-type header that we have set before.
	 */
 	protected function _performPost($method, $path, $data = null)
    {
    	// set content-type
    	$content_type = 'application/x-www-form-urlencoded; charset=utf-8';
		$this->_setContentType($content_type);
				
    	// set path
    	$this->getUri()->setPath($path);
    	
    	// set uri
        $client = RESTHttpClient::getHttpClient();
        $client->setUri($this->getUri());
        //$client = RESTHttpClient::connect($this->getUri());
        
        // set data
        if (is_string($data)) {
            $client->setRawData($data);
        } elseif (is_array($data) || is_object($data)) {
            $client->setParameterPost((array) $data);
        }
        return $client->request($method);
    }





    protected function _performGet($path, $data = null)
    {
      $content_type = 'application/x-www-form-urlencoded; charset=utf-8';
      $this->_setContentType($content_type);
      
      $client = RESTHttpClient::getHttpClient();
      return $client->doGet($path,$data);
    }



    /*
     * Use our own performDelete() for DELETE method, since restDelete() doesn't have any $query parameter
     */
	protected function _performDelete($path, array $query = null)
    {
    	$this->getUri()->setPath($path);
        $client = RESTHttpClient::getHttpClient();
        $client->setUri($this->getUri());
        //$client = RESTHttpClient::connect($this->getUri());
        $client->setParameterGet($query);
        return $client->request('DELETE');
    }

        /**
         * Return the result as an object
         */
        public function setResponse($rest_response)
        {
          $client = RESTHttpClient::getHttpClient();
          $this->_response->status  = $client->getStatus();
          $this->_response->headers = $client->getHeaders();
          $this->_response->body    = $this->_parseBody($client->getContent());

        }

        private function _parseBody($body)
        {
          switch (strtolower($this->_format))
          {
          case 'xml':
            return "TODO : parse xml response";			
          case 'json':
            return json_decode($body);
          case 'yml':
          default:
            return $body;
          }
        }

        /*
         * Set the Content-Type header
         */
        private function _setContentType($content_type)
        {
          $client = RESTHttpClient::getHttpClient();
          //$client = RESTHttpClient::connect("");
          $client->setHeaders('Content-Type', $content_type);
        }

        /**
         * Return the specification array.
         * 
         * @return array	$specs
         */
        public function getSpecs()
        {
          return $this->_specs;
        }

        /**
         * Return available methods in the spec file.
         * 
         * @return array  $methods
         */
        public function getMethods()
        {
          if (isset($this->_methods))
            return $this->_methods;

          $methods = array();
          foreach ($this->_specs['methods'] as $method => $param)
          {
            array_push($methods, $method);
          }
          $this->_methods = $methods;
          return $methods;
        }

        public function getFormat()
        {
          return $this->_format;
        }

        public function getMethodSpec()
        {
          return $this->_method_spec;
        }

        public function getRequestPath()
        {
          return $this->_request_path;
        }

        public function getRequestParams()
        {
          return $this->_request_params;
        }

        public function getRequestMethod()
        {
          return $this->_request_method;
        }

        public function getMiddlewares()
        {
          return $this->_middlewares;
        }

}
