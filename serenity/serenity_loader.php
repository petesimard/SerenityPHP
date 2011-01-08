<?php
///////////////////////
// Main Serenity loader
///////////////////////

// Base SF
include "modules/Base.php";

// Load lib files
include "lib/SerenityPage.php";
include "lib/SerenityModel.php";
include "lib/SerenityDb.php";
include "lib/SerenityPlugin.php";
include "lib/Functions.php";

// Load modules
include "modules/Validator.php";
include "modules/Router.php";
include "modules/Exception.php";
include "modules/AppController.php";
?>
