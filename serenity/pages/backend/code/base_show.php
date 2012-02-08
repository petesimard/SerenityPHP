[phpStart]
namespace Serenity;
[phpEnd]
<b>Show Entry</b>
<p>
<table width=100%>
[fieldList]
</table>
<P>
[phpStart] echo getPageLink('[pageName]', 'edit', 'Edit', array('[modelName]_[modelPrimaryKey]' => $[modelName]->getPrimaryKeyValue()));[phpEnd]
<p>
[phpStart] echo getPageLink('[pageName]', 'index', 'Back to list');[phpEnd]