<?php 
/**
 * Data sync from Joomla Contacts to SuiteCRM / SugarCRM Leads and Contacts
 * @version 1.0
 * @package DTSuiteSugarCRM
 * @copyright (C) 2017-2018 by Deligence Technologies Pvt Ltd.
 * @license Released under the terms of the GNU General Public License
**/
defined('_JEXEC') or die('Restricted access');
jimport( 'joomla.application.component.controller' );

class PlgSystemDTSuiteSugarCRM extends JPlugin {
	
	public $url = '';
	public $username = '';
	public $password = '';
	public $lead = '';
	public $contact = '';
	public $data = '';
	public $session_id = '';
	public $first_name = '';
	public $last_name = '';
	

      public function __construct(&$subject, $config = array()) {
         parent::__construct($subject, $config);
     }
	
	 public function onSubmitContact($contact, $data){
		 $this->url = $this->params->get('url', '');
		 $this->username = $this->params->get('username', '');
		 $this->password = $this->params->get('password', '');
		 $this->lead = $this->params->get('lead', '');
		 $this->contact = $this->params->get('contact', '');
		 $this->data = $data;

		 if($this->url) $this->url .='service/v4_1/rest.php';
		 
		 $this->login();
		 
		 if($this->lead && $this->session_id){
			$this->create('Leads');
		 }
		 
		 
		 if($contact && $this->session_id){
			$this->create('Contacts');
		 }
	 }
	 
	 public function nameFormating(){
	   $this->first_name='';
	   $this->last_name=''; 
	   $name_arr = explode(" ",$this->data['contact_name']);
		
       for($i = 0; $i < count($name_arr)-1; $i++){
			$this->first_name .=$name_arr[$i]." ";
		}
		
		$last_index = count($name_arr)-1;
		
		$this->last_name = $name_arr[$last_index];
		 
	 }
	 
	 
	 public function create($module){
		$this->nameFormating() ;
		$set_entry_parameters = array(
		
			"session" => $this->session_id,
	
			"module_name" => $module,
	
			"name_value_list" => array(
				array("name" => "first_name", "value" => $this->first_name),
				array("name" => "last_name", "value" => $this->last_name),
				array("name" => "title", "value" => $this->data['contact_subject']),
				array("name" => "email1", "value" => $this->data['contact_email']),
				array("name" => "description", "value" => $this->data['contact_message']),
			),
		);

   		 $set_entry_result = $this->call("set_entry", $set_entry_parameters, $this->url); 
		 
		 //print_r($set_entry_result);
		 
	 }
	 
	 
	 
	 public function login(){
		 
		$login_parameters = array(
         "user_auth" => array(
              "user_name" => $this->username,
              "password" => md5($this->password),
              "version" => "1"
         ),
         "application_name" => "RestTest",
         "name_value_list" => array(),
    	);

   		 $login_result = $this->call("login", $login_parameters, $this->url); 
		 $this->session_id = $login_result->id;
	 }
	 
	 
	 public function call($method, $parameters, $url){
        ob_start();
        $curl_request = curl_init();
        curl_setopt($curl_request, CURLOPT_URL, $url);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        $jsonEncodedData = json_encode($parameters);

        $post = array(
             "method" => $method,
             "input_type" => "JSON",
             "response_type" => "JSON",
             "rest_data" => $jsonEncodedData
        );

        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($curl_request);
        curl_close($curl_request);

        $result = explode("\r\n\r\n", $result, 2);
        $response = json_decode($result[1]);
        ob_end_flush();
        return $response;
    }

}