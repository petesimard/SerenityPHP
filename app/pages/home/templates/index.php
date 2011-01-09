<?
namespace Serenity;
?>
Passed from page: <?=$testVar?><br>
Current UserId: <?=$userId?><br>
<?=getPageLink("home", "", "This link passes a var", array("myVar"=>"Half Dozen"));?>