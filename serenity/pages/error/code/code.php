<?php
namespace Serenity;

class ErrorPage extends SerenityPage
{
    function error()
    {
        $this->errorMessage =  $this->getParam("errorMessage");
    }
}
?>
