<?php
namespace Serenity;

class SerenityAuthPage extends SerenityPage
{
	public $auth_pageAccessLevel = -1;
    public $auth_actionAccessLevels = array();

    public function auth()
    {
        return sp::app()->auth();
    }

    public function authUser()
    {
        return sp::app()->auth()->getUser();
    }
}