<?php
namespace Serenity;

/**
 * User authorization plugin
 * @author Pete
 * @todo add a system to allow plugins to register global variables to be used in a template (such as $userid)
 */
class AuthPlugin extends SerenityPlugin
{
	protected $authenticatedUser = null; 
	protected $model;
	protected $loginNameField;
	protected $passwordField;
	protected $saltField;
	protected $passwordHash;
	protected $siteSalt;

	/**
	 * Setup initial plugin parameters
	 */
	public function onAppLoad($params)
	{
		$this->model = $params['model'];
		if(!is_object($this->model))
			throw new SerenityException("Invalid model supplied to Authenticator: " . $params['model']);
			
		$this->loginNameField = $params['loginNameField'];
		$this->passwordField = $params['passwordField'];
		$this->passwordHash = $params['passwordHashFunction'];
		if($this->passwordHash == "")
			$this->passwordHash = "sha1";
		
		$this->siteSalt = $params['siteSalt'];			
		if($this->siteSalt == "")
			$this->siteSalt = "SerenitySalt2011";

		if($_SESSION['Auth_UserId'])
		{
			$this->loadAuthenticatedUserData($_SESSION['Auth_UserId']);
		}
	}
	
	public function onPageStart($params)
	{	
	}	
	
	public function onPageEnd($params)
	{	
	}
	
	/**
	 * Retrive the model from the database
	 * @param int $userId
	 */
	public function loadAuthenticatedUserData($userId)
	{
		$this->authenticatedUser = $this->model->fetchOne($userId);
	}	
	
	/**
	 * Check if the current auth user exists
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return ($this->authenticatedUser ? true : false);
	}	
	
	/**
	 * Helper function to quickly get the current UserId
	 * @return number
	 */
	public function userId()
	{
		return ($this->authenticatedUser ? $this->authenticatedUser->getPrimaryKeyValue() : 0);
	}
	
	/**
	 * Attempts to login a user based on the given username / password combo
	 * @param string $username
	 * @param string $password
	 * @return SerenityModel|null
	 */
	public function authenticateUser($username, $password)
	{
		$authUser = $this->model->fetchOne($this->loginNameField . "='" . mysql_escape_string($username) . "'");
		
		if($authUser == null)
			return null;

		$hashedPassword = $this->getPasswordHash($password, $authUser['salt']);
		if($authUser[$this->passwordField] == $hashedPassword)
		{
			$this->onUserAuthenticated($authUser);
			return $authUser;
		}
		else
			return null;
	}
	
	/**
	 * User auth successfull, register session variables
	 * @param unknown_type $authUser
	 * @throws SerenityException
	 */
	public function onUserAuthenticated($authUser)
	{
		if(!is_object($authUser))
			throw new SerenityException("Invalid Auth User");
			
		$this->authenticatedUser = $authUser;
		
		$_SESSION['Auth_UserId'] = $this->authenticatedUser->getPrimaryKeyValue();
	}
	
	/**
	 * Logout function
	 */
	public function logout()
	{
		if(is_object($this->authenticatedUser))
		{
			$this->authenticatedUser = null;
			$_SESSION['Auth_UserId'] = "";
		}
	}
	/**
	 * Returns the hashed version of a password
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	public function getPasswordHash($password, $salt)
	{
		// 1000 cycles to make rainbow table generation more expensive
		for($x=0; $x<1000; $x++)
			$password = hash_hmac($this->passwordHash, $password . $salt, $this->siteSalt);
		
		return $password;
	}
	
	/**
	 * Generates a random salt
	 * @param number $length
	 * @return string
	 */
	public function generateSalt($length)
	{
		$possible = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
	
		$maxPossibleChar = strlen($possible)-1;
		for($i=0;$i < $length; $i++) {
			$char = $possible[mt_rand(0, $maxPossibleChar)];
			$string .= $char;
		}
	
		return $string;
	}		
	
	/**
	 * Returns the current auth user
	 * @return SerenityModel|null
	 */
	public function getUser()
	{
		return $this->authenticatedUser;
	}
}
?>
