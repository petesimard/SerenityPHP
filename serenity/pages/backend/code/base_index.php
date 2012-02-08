[phpStart]
namespace Serenity;
[phpEnd]
<b>Viewing [ModelName] entries</b>
<p>
<table>
<tr>
[phpStart]
$baseModel = sp::app()->getModel('[modelName]');

foreach($baseModel->getFields() as $field)
{
	[phpEnd]
	<td><b>[phpStart] echo $field->name[phpEnd]</b></td>	
	[phpStart]
}
[phpEnd]
</tr>
[phpStart]
foreach($[modelName]s as $[modelName])
{
	echo "<tr>";
	foreach($[modelName]->getFields() as $field)
	{	
		echo "<td>";

		if($field->name == $baseModel->getPrimaryKey())
			echo getPageLink('[pageName]', 'show', $field->getValue(), array('[modelName]_[modelPrimaryKey]' => $field->getRawValue()));
		else
			echo $field;
					
		echo "</td>";
	}
	
	echo "</tr>";
}
[phpEnd]
</table>
<p>
[phpStart] echo getPageLink('[pageName]', 'create', 'Create new');[phpEnd]