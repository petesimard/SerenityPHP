<?php
namespace Serenity;

include "SerenityAuthPage.php";

/**
 * User authorization plugin
 * @author Pete
 */
class AuthPlugin extends SerenityPlugin
{
	protected $authenticatedUser = null;
	protected $model;
	protected $loginNameField;
	protected $passwordField;
	protected $saltField;
	protected $accessLevelField;
	protected $passwordHash = "sha1";
	protected $siteSalt = "SerenitySalt2011";
	protected $authErrorPage = "error";
    protected $appAccessLevel = -1;

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
		$this->accessLevelField = $params['accessLevelField'];

		if(isset($params['passwordHashFunction']))
			$this->passwordHash = $params['passwordHashFunction'];

		if(isset($params['siteSalt']))
			$this->siteSalt = $params['siteSalt'];

		if(isset($params['authErrorPage']))
			$this->authErrorPage = $params['authErrorPage'];

		if(isset($_SESSION['Auth_UserId']) && $_SESSION['Auth_UserId'] != "")
		{
			$this->loadAuthenticatedUserData($_SESSION['Auth_UserId']);
		}
	}

	/* (non-PHPdoc)
	 * @see Serenity.SerenityPlugin::onActionEnd()
	 */
	public function onActionEnd($page)
	{
	}

    public function parseAppConfig($config)
    {
        if($config['auth']['global'] != '')
            $this->appAccessLevel = $config['auth']['global'];
    }

    public function parsePageConfig($page, $config)
    {
        if(!isset($config['auth']))
            return;

        $auth_actionAccessLevels = array();

        if(!is_null($config['auth']['global']))
            $page->auth_pageAccessLevel = $config['auth']['global'];

        if(isset($config['auth']['actions']) && is_array($config['auth']['actions']))
        foreach($config['auth']['actions'] as $actionName => $level)
        {
            $auth_actionAccessLevels[$actionName] = $level;
        }

        $page->auth_actionAccessLevels = $auth_actionAccessLevels;

    }

	/* (non-PHPdoc)
	 * @see Serenity.SerenityPlugin::getTemplateVariables()
	 */
	public function getTemplateVariables()
	{
		if($this->authenticatedUser)
        {
			$vars = array('authUser' => $this->authenticatedUser, 'userId' => $this->authenticatedUser->getPrimaryKeyValue(), 'adminLevel' => $this->authenticatedUser['adminLevelField']);
        }
		else
			$vars = array();

		return $vars;
	}

	/**
	 * Retrive the model from the database
	 * @param int $userId
	 */
	public function loadAuthenticatedUserData($userId)
	{
		$this->authenticatedUser = $this->model->query($userId)->fetchOne();
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
		$authUser = $this->model->query()->addWhere($this->loginNameField . "='" . mysql_escape_string($username) . "'")->fetchOne();

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
        $string = SerenityModel::getRandomHash($length);

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

	/**
	 * Returns the current access level of the current auth user
	 * @return number
	 */
	public function getUserAcessLevel()
	{
		return ($this->authenticatedUser ? $this->authenticatedUser[$this->accessLevelField] : -1);
	}

    public function getPageAccessLevel()
    {
        $page = sp::app()->getCurrentPage();

        // App access level
        $accessLevel = $this->appAccessLevel;

        // Page access level
        if(!is_null($page->auth_pageAccessLevel))
            $accessLevel = $page->auth_pageAccessLevel;

        // Action access level
        $actionAccessLevel = (isset($page->auth_actionAccessLevels[$page->getCurrentAction()]) ? $page->auth_actionAccessLevels[$page->getCurrentAction()] : null);
        if(!is_null($actionAccessLevel))
            $accessLevel = $actionAccessLevel;

        return $accessLevel;
    }

	/* (non-PHPdoc)
	 * @see Serenity.SerenityPlugin::onActionStart()
	 */
	public function onActionStart($page)
	{
		if($this->getPageAccessLevel() != -1)
		{
			$this->checkAccess($this->getPageAccessLevel());
		}
	}


	/**
	 * Check the access level of the action with the current user
	 * @param number $accessLevel
	 */
	public function checkAccess($accessLevel)
	{
		if($accessLevel >= 0 && !$this->authenticatedUser)
		{
			$this->onFailedAccessLevel();
			return;
		}

		if($this->authenticatedUser[$this->accessLevelField] < $accessLevel)
		{
			$this->onFailedAccessLevel();
			return;
		}
	}

	/**
	 * Bounce them to the error page
	 */
	public function onFailedAccessLevel()
	{
		sp::app()->getCurrentPage()->setNotice('error', 'You are not authorized to view this page.');
		sp::app()->redirect($this->authErrorPage);
	}
}
?>
