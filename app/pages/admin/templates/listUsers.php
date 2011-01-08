<?php
namespace Serenity;

?>
<b>Users:</b>
<table>
<tr>
<td><b>ID</b></td><td><b>Username</b></td><td><b>Password</b></td><td><b>Email</b></td>
</tr>
<?php 
foreach($users as $user)
{
?>
<tr>
<td><?=getPageLink('admin', 'editUser', $user['id'], array('user_id' => $user['id']))?></td><td><?=$user['username']?></td><td><?=$user['password']?></td><td><?=$user['email']?></td>
</tr>
<?php
}
?>
</table>