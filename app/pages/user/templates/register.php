<?
namespace Serenity;
?>
<div align="left">
<b>New user registration</b><br><br>
<?
$model = sp::app()->getModel("user");
echo $model->getFormStart();
?>
Choose Username: <?=$formErrors['username']?><br>
<?=$model->getFormField("username");?>
<br>
Choose Password: <?=$formErrors['password']?><br>
<?=$model->getFormField("password");?>
<br>
Re-enter Password: <?=$formErrors['password_confirm']?><br>
<?=$model->getFormField("password_confirm");?>
<br>
Enter Your E-Mail Address: <?=$formErrors['email']?><br>
<?=$model->getFormField("email");?>
<br>
<br>
<input type="submit" value="Register">
</form>
</div>