<?
namespace Serenity;
?>
Passed from page: <?=$testVar?><br>
<?=sf::app()->getPageLink("home", "", "This link passes a var", array("myVar"=>"Half Dozen"));?>