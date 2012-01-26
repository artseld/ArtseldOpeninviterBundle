<?php

/*
 * This file is part of the Artseld\OpeninviterBundle package.
 *
 * (c) Dmitry Kozlovich <artseld@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * ====================================================
 * Based on original yandex plugin code
 * ====================================================
 */

$_pluginInfo=array(
	'name'=>'Yandex',
	'version'=>'1.1.2',
	'description'=>"Get the contacts from a Yandex account",
	'base_version'=>'1.8.0',
	'type'=>'email',
	'check_url'=>'http://yandex.ru',
	'requirement'=>'email',
	'allowed_domains'=>array('/(yandex.ru)/i'),
	'imported_details'=>array('first_name','middle_name','last_name','phone_home','email_1'),
	);
/**
 * Yandex Plugin
 * 
 * Imports user's contacts from his Yandex
 * AddressBook.
 * 
 * @author OpenInviter
 * @version 1.0.5
 */
class yandex extends openinviter_base
{
	private $login_ok=false;
	public $showContacts=true;
	protected $timeout=30;
	public $debug_array=array(
			  'login_post'=>'window.location.replace',	
			  'contacts_file'=>'Name',			  			  
	);
	
	/**
	 * Login function
	 * 
	 * Makes all the necessary requests to authenticate
	 * the current user to the server.
	 * 
	 * @param string $user The current user.
	 * @param string $pass The password for the current user.
	 * @return bool TRUE if the current user was authenticated successfully, FALSE otherwise.
	 */
	public function login($user, $pass)
		{
		$this->resetDebugger();
		$this->service='yandex';
		$this->service_user=$user;
		$this->service_password=$pass;
		if (!$this->init()) return false;		
		$form_action="https://passport.yandex.ru/passport?mode=auth&retpath=http://mail.yandex.ru";
		$post_elements=array("login"=>$user,"passwd"=>$pass);
		$res=$this->post($form_action, $post_elements,true);		
		if ($this->checkResponse("login_post",$res))
			$this->updateDebugBuffer('login_post',"{$form_action}",'POST',true,$post_elements);
		else
			{
			$this->updateDebugBuffer('login_post',"{$form_action}",'POST',false,$post_elements);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}	
		//$linkToAddressBook="http://mail.yandex.ru/neo/ajax/action_abook_export";
        $linkToAddressBook="http://mail.yandex.ru/neo2/handlers/abook-export.jsx";
		$this->login_ok=$linkToAddressBook;
		return true;
	}

	/**
	 * Get the current user's contacts
	 * 
	 * Makes all the necesarry requests to import
	 * the current user's contacts
	 * 
	 * @return mixed The array if contacts if importing was successful, FALSE otherwise.
	 */	
	public function getMyContacts()
	{
		if (!$this->login_ok)
			{
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		else $url = $this->login_ok;
		$contacts=array();
		$post_elements=array("tp"=>1,"lang"=>'en');
		$res=$this->post($this->login_ok,$post_elements);
		$temp=$this->parseCSV($res);
		if ($this->checkResponse("contacts_file",$res))
			$this->updateDebugBuffer('contacts_file',$this->login_ok,'POST',true,$post_elements);
		else
			{
			$this->updateDebugBuffer('contacts_file',$this->login_ok,'POST',false,$post_elements);	
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		$contacts=array();
		foreach ($temp as $values)
			{
			if (!empty($values[14]))
				$contacts[$values[14]]=array('first_name'=>(!empty($values[0])?$values[0]:false),
											 'middle_name'=>(!empty($values[1])?$values[1]:false),
											 'last_name'=>(!empty($values[2])?$values[2]:false),
												'nickname'=>false,
												'email_1'=>(!empty($values[14])?$values[14]:false),
												'email_2'=>(!empty($values[15])?$values[15]:false),
												'email_3'=>(!empty($values[16])?$values[16]:false),
												'organization'=>false,
												'phone_mobile'=>(!empty($values[21])?$values[21]:false),
												'phone_home'=>(!empty($values[19])?$values[19]:false),
												'pager'=>(!empty($values[22])?$values[22]:false),
												'address_home'=>(!empty($values[24])?$values[24]:false),
												'address_city'=>(!empty($values[29])?$values[29]:false),
												'address_state'=>(!empty($values[30])?$values[30]:false),
												'address_country'=>(!empty($values[32])?$values[32]:false),
												'postcode_home'=>(!empty($values[31])?$values[31]:false),
												'company_work'=>(!empty($values[43])?$values[43]:false),
												'address_work'=>(!empty($values[50])?$values[50]:false),
												'address_work_city'=>(!empty($values[55])?$values[55]:false),
												'address_work_country'=>(!empty($values[58])?$values[58]:false),
												'address_work_state'=>(!empty($values[56])?$values[56]:false),
												'address_work_postcode'=>(!empty($values[57])?$values[57]:false),
												'fax_work'=>(!empty($values[41])?$values[41]:false),
												'phone_work'=>(!empty($values[39])?$values[39]:false),
												'website'=>(!empty($values[6])?$values[6]:false),
												'isq_messenger'=>false,
												'skype_essenger'=>false,
												'yahoo_essenger'=>false,
												'msn_messenger'=>false,
												'aol_messenger'=>false,
												'other_messenger'=>false,
											   );
			}
		foreach ($contacts as $email=>$name) if (!$this->isEmail($email)) unset($contacts[$email]);
		return $this->returnContacts($contacts);
		}

	/**
	 * Terminate session
	 * 
	 * Terminates the current user's session,
	 * debugs the request and reset's the internal 
	 * debudder.
	 * 
	 * @return bool TRUE if the session was terminated successfully, FALSE otherwise.
	 */	
	public function logout()
		{
		if (!$this->checkSession()) return false;
		$res=$this->get(urldecode("http://passport.yandex.ru/passport?mode=logout"));
		$this->debugRequest();
		$this->resetDebugger();
		$this->stopPlugin();
		}
}
?>