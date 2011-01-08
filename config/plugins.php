<?php
namespace Serenity;

// Load Plugins
// sf::app()->loadPlugin([plugin name], [array of parameters]); 

sf::app()->loadPlugin("auth", array(
"model" => sf::app()->getModel("user"),
"loginNameField" => "username",
"passwordField" => "password",
"passwordHashFunction"=> "sha1",
"accessLevelField"=> "adminLevel",
"saltField" => "salt",
"siteSalt" => "mysitesalt"
));
