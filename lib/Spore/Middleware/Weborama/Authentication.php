<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Spore
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    0.1
 * @author	   Ismail Fahmi <ismail.fahmi@gmail.com>
 */



class Zend_Service_Spore_Middleware_Weborama_Authentication
{
	
	protected $_applicationKey;
	protected $_privateKey;
	protected $_signatureString;
	protected $_signatureSha1;
	
	/**
	 * Construct the authentication object
	 * 
	 * @param array $args
	 */
	public function __construct($args)
    {
    	if (isset($args['application_key']))
    		$this->setApplicationKey($args['application_key']);
    	else 
    		$this->setApplicationKey('');
    		
    	if (isset($args['private_key']))
    		$this->setPrivateKey($args['private_key']);
    	else
    		$this->setPrivateKey('');
    }	
    
    /*
     * Set application key
     */
    public function setApplicationKey($applicationKey)
    {
    	$this->_applicationKey = $applicationKey;
    }
    
    /*
     * set private key
     */
    public function setPrivateKey($privateKey)
    {
    	$this->_privateKey = $privateKey;
    }
    
    /**
     * Add the application_key and private_key into the client's headers
     * 
     * @param reference $client
     */
    public function execute(&$spore)
    {
    	// set signature string
    	$this->setSignatureString($spore);
    	$this->_signatureSha1 = sha1($this->_signatureString);
    	
    	// modify the request headers
    	$client = $spore->getHttpClient();
    	$client->setHeaders('X-Weborama-AppKey', $this->_applicationKey);
    	$client->setHeaders('X-Weborama-Signature', $this->_signatureSha1);
    	
    }
    
    /*
     * Generate signature string
     */
    public function setSignatureString($spore)
    {
    	// add request method and path
    	$this->_signatureString = strtolower($spore->getRequestMethod()) . $spore->getRequestPath() ;
    	
    	// add request params
    	$string_params = '';
    	$params = $spore->getRequestParams();
    	ksort($params);
    	foreach ($params as $key => $val)
    	{
    		$string_params .= "$key=$val";
    	}
    	$this->_signatureString .= $string_params;
    	
    	// add private key
    	$this->_signatureString .= $this->_privateKey;
    }
    
    public function getSignatureString()
    {
    	return $this->_signatureString;
    }
    
}