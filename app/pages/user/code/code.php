<?php
namespace Serenity;

class UserPage extends SerenityPage
{
    function register()
    {
        if($this->isFormValid(array("username", "password", "email")))
        {
            $user = $this->getForm();
            
            $user['salt'] = sp::app()->Auth()->generateSalt(16);
            $user['password'] = sp::app()->Auth()->getPasswordHash($user['password'], $user['salt']);
            $user->save();
            
            sp::app()->Auth()->onUserAuthenticated($user);

            $this->setTemplate("success");
        }
    }
    
    function register_registerParams()
    {
    	$this->addParam("user_password", array("minLen" => 3, "maxLen" => 16));    	
    }
    
    
    
	function logout()
    {
    	sp::app()->Auth()->logout();
    	
    	$this->setNotice('success', 'Successfully logged out');
    	
    	sp::app()->redirect('home');
    }    
    
    function login()
    {
        $username = $this->getParam('login_username');
        $password = $this->getParam('login_password');
		
        $user = sp::app()->Auth()->authenticateUser($username, $password);

        if($user)
            $this->setNotice('success', 'Successfully logged in');
        else
            $this->setNotice('error', 'Invalid username / password');

        sp::app()->redirect('home');
     }
}
?>
