[phpStart]
namespace Serenity;
[phpEnd]
<b>New Entry</b>
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
[phpStart] echo getPageLink('[pageName]', 'index', 'Back to list');[phpEnd]