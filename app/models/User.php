<?php
namespace Serenity;

class UserModel extends SerenityModel
{
    function init()
    {
        $field = $this->addField("id");
        $field->type = "int";
        $field->index = "primary";

        $field = $this->addField("adminLevel");
        $field->type = "int";
        $field->defaultValue = 1;
        
        $field = $this->addField("username");
        $field->type = "varchar";
        $field->length = "16";
        $field->validator = array("minLen" => 3, "maxLen" => 16, "required" => true, "unique" => true, "friendlyName" => "Username");
                
        $field = $this->addField("salt");
        $field->type = "varchar";
        $field->length = "32";
        
        $field = $this->addField("password");
        $field->type = "varchar";
        $field->length = "32";
        $field->isPassword = true;
        $field->validator = array("required" => true, "friendlyName" => "Password");

        $field = $this->addField("password_confirm");
        $field->type = "form";
        $field->isPassword = true;
        $field->validator = array("matchField" => $this->getField('password'), "required" => true, "friendlyName" => "Password Confirm");

        $field = $this->addField("email");
        $field->type = "varchar";
        $field->length = "64";
        $field->validator = array("type" => "email", "required" => true, "unique" => true, "friendlyName" => "Email Address");
        $field->index = "index";
    }
}
?>
