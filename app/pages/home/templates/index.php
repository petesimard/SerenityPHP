<?
namespace Serenity;
?>
Passed from page: <?=$testVar?><br>
Current UserId: <?=$userId?><br>
<?=getPageLink("home", "", "This link passes a var", array("myVar"=>"Half Dozen"));?>
<br><p>
<?php 
foreach($user['posts'] as $post)
	echo $post['id'] . " " . $post['title'] . "<br>";
?>