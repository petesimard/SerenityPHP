[phpStart]
namespace Serenity;
[phpEnd]
<b>Edit Entry</b>
<p>
[phpStart]
echo $[modelName]->getFormStart('[pageName]', 'save');
[phpEnd]
<table width=100%>
[fieldList]
<tr>
<td>
	<input type="submit" name="submit" value="Submit">
</td>
</tr>
</table>
<P>
[phpStart] echo getPageLink('[pageName]', 'show', 'Back to view', array('[modelName]_[modelPrimaryKey]' => $[modelName]->getPrimaryKeyValue()));[phpEnd]
<p>
[phpStart] echo getPageLink('[pageName]', 'index', 'Back to list');[phpEnd]