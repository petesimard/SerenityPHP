<?
namespace Serenity;

class UserModel extends BaseUserModel
{
    function init()
    {
        $field = $this->getRawField("adminLevel");
        $field->defaultValue = 1;

        $field = $this->getRawField("username");
        $field->validator = array("minLen" => 3, "maxLen" => 16, "required" => true, "unique" => true, "friendlyName" => "Username");

        $field = $this->getRawField("password");
        $field->isPassword = true;
        $field->validator = array("required" => true, "friendlyName" => "Password");

        $field = $this->addField("password_confirm");
        $field->type = "form";
        $field->isPassword = true;
        $field->validator = array("matchField" => 'password', "required" => true, "friendlyName" => "Password Confirm");

        $field = $this->getRawField("email");
        $field->validator = array("type" => "email", "required" => false, "unique" => true, "friendlyName" => "Email Address");
    }
}