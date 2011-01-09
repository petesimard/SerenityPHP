<?php
namespace Serenity;

class ErrorPage extends SerenityPage
{
    function index()
    {
        $this->errorMessage =  $this->getParam("errorMessage");
    }
}
?>
