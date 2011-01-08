<?php
namespace Serenity;

function debugSnippet($params)
{
    ob_start();
    ?>
    <script type="text/javascript">
    var isExpanded = false;
    function debugShowMysql()
    {
        if(isExpanded)
        {
            $('#debugDiv').animate({"width": "-=400px", "height": "-=500px"}, "slow");
            $('#debug_mysql').hide();
        }
        else
        {
        	$('#debug_mysql').show();
            $('#debugDiv').animate({"width": "+=400px", "height": "+=500px"}, "slow");
        }

        isExpanded = !isExpanded;
    }
    </script>
    <div id="debugDiv"
    style="width:300px;
    height:23px;
    position:fixed;
    right:0;
    top:0;
    border:1px solid silver;
    background-color: #D2CDCA;
    color:#E0E0E0;
    "><table width="100%" height=100%>
    <tr>
    <td align="center"><a href="javascript:debugShowLog()"><font color="#E6F10E">Log</font></a></td><td align="center"><a href="javascript:debugShowMysql()"><font color="#E6F10E">MySQL</font></a></td>
    </tr>
    <tr>
        <td bgcolor="white" id="debugOutput" colspan=2 height=100% valign="top">
        <div id="debug_mysql" style="overflow:auto;display:none;vertical-align:top"><?
        $db = sf::db();
        foreach($db->queryLog AS $query)
        {
            $x++;
            echo "<b>" . $x . ":</b> " . htmlentities($query) . "<br><br>";
        }
        ?></div></td>
    </tr>
    </table>
    </div>
    <?
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}
