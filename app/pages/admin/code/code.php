<?php
namespace Serenity;

class AdminPage extends SerenityPage
{
    function index()
    {
    	$this->users = UserModel::query()->orderBy('id ASC')->fetch();
    	
    	$this->setTemplate("listUsers");    
    }
    
    function editUser()
    {    	
    	$this->user = UserModel::query($this->getParam('id'))->fetchOne();
    	
    	$this->errorIf(!$this->user, "Invalid ID passed");    	
    }    
    
    
    function editUser_registerParams()
    {    
    	$this->addParam("user_id", array("type" => "int", "minValue" => 1, "required" => true, "errorMessage" => "Invalid user ID"));
    }    
}