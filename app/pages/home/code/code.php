<?php
namespace Serenity;

class HomePage extends SerenityAuthPage
{
    function index()
    {
    	// example query
        $this->user = UserModel::query(21)->orderBy('username DESC')->addWhere('id > 5')->fetchOne();
        
        // test page parameter
        $this->testVar = $this->getParam('testInt', 42);
    }

    function index_registerParams()
    {
        $this->addParam("myVar", array("type" => "string", "minLen" => 2, "maxLen" => 50, "required" => false, "errorMessage"=>"Test String must be between 2 and 50 chars"));
        $this->addParam("testInt", array("type" => "int", "minValue" => 0, "maxValue" => 500, "required" => false, "errorMessage"=>"Test Int must be between 2 and 500"));
    }
}
?>
