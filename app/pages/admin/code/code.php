<?php
namespace Serenity;

class AdminPage extends SerenityAuthPage
{
	public $accessLevel = 2;
	
    function index()
    {
    	$this->users = UserModel::query()->orderBy('id ASC')->fetch();
    	
    	$this->setTemplate("listUsers");    
    }
    
    function editUser()
    {   
    	$this->auth()->checkAccess(3);
    	
    	if($this->isFormValid())
    	{
    		$this->user = $this->getForm();
    		$this->user->save();
    		$this->setNotice('success', 'User information saved');
    	}
    	else
    	{    	
    		$this->user = UserModel::query($this->getParam('user_id'))->fetchOne();
    	}
    	
    	$this->errorIf(!$this->user, "Invalid ID passed");    	
    }    
    
    
    function editUser_registerParams()
    {    
    	$this->addParam("user_id", array("type" => "int", "minValue" => 1, "required" => true, "errorMessage" => "Invalid user ID"));
    }    
}