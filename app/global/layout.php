<?
namespace Serenity;
?>
<html>
<head>
<link rel="stylesheet" href="/css/style.css" type="text/css" />
<script src="/js/jquery.min.js"></script>
<script src="/js/jquery-ui.min.js"></script>
</head>
<body>
<div id="container">
    <div id="header">
        <h1>
            Welcome to Nom Yai Industries
        </h1>
    </div>
    <div id="navigation">
        <ul>
            <li><a href="<?=getPageUrl("home")?>">Home</a></li>
            <li><a href="<?=getPageUrl("admin")?>">Admin</a></li>
            <li><a href="#">Services</a></li>
            <li><a href="#">Contact us</a></li>
            <div align="right"><font color="white"><?php 
            if(sp::app()->Auth()->isLoggedIn())
            	echo "Logged in as " . sp::app()->Auth()->getUser()->getField("username") . ". " . getPageLink("user", "logout", "<font color=white>Logout</font>") . ".";
            else
            	echo "Not logged in.";            	
            ?></font>
            </div>
        </ul>
    </div>
    <div id="content-container">
        <div id="section-navigation">
        <?php
        if(!sp::app()->Auth()->isLoggedIn())
        { ?>
            <form method="post" action="<?=getPageUrl("user", "login")?>">
            Username:<br>
            <input type="text" name="login_username">
            <br>
            Password:<br>
            <input type="password" name="login_password">
            <br><?=getPageLink("user", "register", "Register");?>
            <br>
            <input type="submit" value="Login">
            </form>
            <?php }
            else 
	            echo "Logged in" 
	        ?>
        </div>
        <div id="content">
        <?
        $pageNotice = sp::app()->getCurrentPage()->getNotice();

        if($pageNotice['message'] != "")
            echo sp::app()->getSnippet($pageNotice['type'], array("message" => $pageNotice['message']));

        echo $body_html;
?>
        </div>
        <div id="footer">
            Copyright � Nom Yai Industries, 2011
        </div>
    </div>
</div>
</body>
</html>