<?php
namespace Serenity;
?>
<b>Editing user #<?=$user['id']?></b><p>
<?php
echo $user->getFormStart();
echo "<b>Username:</b><br>";
echo $user->getFormField("username");
echo "<p><b>email:</b><br>";
echo $user->getFormField("email");
echo "<p><b>Favorite blog post:</b><br>";
echo $user->getFormField("favoriteBlogPost");
?>
<br><br>
<input type="submit">
</form>