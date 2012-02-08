<?php
namespace Serenity;
?>
<b>Serenity Backend Manager</b>
<p>
<div style="{ width: 270px; border: 1px solid gray; float: left; padding: 5px}">
<b>Add new page</b>
<p>
<form method="post" action="<?=getPageUrl('backend', 'newPage')?>">
<b>Page Name:</b><br>
<input type="text" name="pageName">
<p>
<b>Base model:</b><br>
<select name = "pageModel">
<option value="0">-- None --</option>
<?php
foreach(sp::app()->getModels() as $model)
	echo "<option value=\"" . $model->getName(). "\">" . $model->getName() . "</option>";
?>
</select>
<p>
<input type="submit" value="Make new page" name="createPage">
</form>
</div>

<div style="{ width: 270px; border: 1px solid gray; float: left; margin: 0px 20px; padding: 5px;}" align="center"><b>Generate SQL</b>
<p>
<form method="post" action="<?=getPageUrl('backend', 'generateSQL')?>">
<input type="submit" value="Generate SQL from models" name="genSQL">
</form>
</div>
<div style="{ width: 270px; border: 1px solid gray; float: left; margin: 20px 20px; padding: 5px;}" align="center"><b>Generate Models</b>
<p>
<form method="post" action="<?=getPageUrl('backend', 'generateModels')?>">
<input type="submit" value="Generate models from database" name="genModels">
</form>
</div>
