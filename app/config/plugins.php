<?php
namespace Serenity;

// Load Plugins
// sp::app()->loadPlugin([plugin name], [array of parameters]); 

sp::app()->loadPlugin("auth", array(
"model" => sp::app()->getModel("user"),
"loginNameField" => "username",
"passwordField" => "password",
"passwordHashFunction"=> "sha1",
"accessLevelField"=> "adminLevel",
"saltField" => "salt",
"siteSalt" => "mysitesalt"
));
