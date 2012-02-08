<?php
///////////////////////
// Main Serenity loader
///////////////////////
date_default_timezone_set('UTC') ;

// Base SF
include "lib/yaml/sfYamlParser.php";
include "modules/Base.php";

// Load lib files
include "lib/SerenityPage.php";
include "lib/SerenityBackendPage.php";
include "lib/SerenityModel.php";
include "lib/SerenityPlugin.php";
include "lib/Functions.php";

// Load modules
include "modules/Database.php";
include "modules/Validator.php";
include "modules/Router.php";
include "modules/Exception.php";
include "modules/AppController.php";
?>
