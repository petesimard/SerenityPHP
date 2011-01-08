<?php
namespace Serenity;

class UserPage extends SerenityPage
{
    function register()
    {
        if($this->isFormValid(array("username", "password", "email")))
        {
            $user = $this->getForm();
            
            $user['salt'] = sf::app()->Auth()->generateSalt(16);
            $user['password'] = sf::app()->Auth()->getPasswordHash($user['password'], $user['salt']);
            $user->save();
            
            sf::app()->Auth()->onUserAuthenticated($user);

            $this->setTemplate("success");
        }
    }
    
	function logout()
    {
    	sf::app()->Auth()->logout();
    	
    	$this->setPageNotice('success', 'Successfully logged out');
    	
    	sf::app()->redirect('home');
    }    
    
    function login()
    {
        $username = $this->getParam('login_username');
        $password = $this->getParam('login_password');
		
        $user = sf::app()->Auth()->authenticateUser($username, $password);

        if($user)
            $this->setPageNotice('success', 'Successfully logged in');
        else
            $this->setPageNotice('error', 'Invalid username / password');

        sf::app()->redirect('home');
     }
}
?>
