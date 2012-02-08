<?
namespace Serenity;

/**
/* Auto generated class. DO NOT EDIT.
/* All edits should be on the child class 'UserModel'
 */
abstract class BaseUserModel extends SerenityModel
{
    function baseInit()
    {
		$field = $this->addField("id");
		$field->type = "int";
		$field->size = 11;
		$field->index = "primary";

		$field = $this->addField("email");
		$field->type = "varchar";
		$field->size = 128;

		$field = $this->addField("username");
		$field->type = "varchar";
		$field->size = 64;

		$field = $this->addField("password");
		$field->type = "varchar";
		$field->size = 128;

		$field = $this->addField("createdOn");
		$field->type = "int";
		$field->size = 11;

		$field = $this->addField("salt");
		$field->type = "varchar";
		$field->size = 64;

		$field = $this->addField("adminLevel");
		$field->type = "tinyint";
		$field->size = 4;
	}
}