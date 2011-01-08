<?php
namespace Serenity;
?>
<b>Editing user #<?=$user['id']?></b><br>
<?php
echo $user->getFormStart();
echo "<b>Username:</b>";
echo $user->getFormField("username");
echo "<br><b>email:</b>";
echo $user->getFormField("email");
?>
<br><br>
<input type="submit">
</form>