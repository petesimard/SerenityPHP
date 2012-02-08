<?php
namespace Serenity;

function debugSnippet($params)
{
    ob_start();
    ?>
    <script type="text/javascript">
    var isExpanded = false;
    var shownPanel = '';
    function showDebugPanel(panel)
    {
        if(isExpanded && shownPanel==panel)
        {
            $('#debugDiv').animate({"width": "-=400px", "height": "-=420px"}, "slow");
            $('.serenity_debugPanel').hide();
            isExpanded = false;
            return;
        }
        else if(!isExpanded)
        {
            $('#debugDiv').animate({"width": "+=400px", "height": "+=420px"}, "slow");
            isExpanded = true;
        }

        $('.serenity_debugPanel').hide();
    	$('#debug_' + panel).show();
        shownPanel = panel;
    }
    </script>

    <style type="text/css">
        .serenity_debugPanel {
            overflow:auto;width:100%;display:none;vertical-align:top;max-height: 400px;
            color: black;
        }
    </style>

    <div id="debugDiv"
    style="width:300px;
    height:24px;
    position:fixed;
    right:0;
    top:0;
    border:1px solid silver;
    background-color: #D2CDCA;
    color:#E0E0E0;">
    <table width="100%" height=100%>
    <tr>
    <td align="center"><a href="javascript:showDebugPanel('app')"><font color="#FFFFFF">App Log</font></a></td><td align="center"><a href="javascript:showDebugPanel('mysql')"><font color="#FFFFFF">MySQL</font></a></td><td align="center"><a href="javascript:showDebugPanel('ajax')"><font color="#FFFFFF">AJAX</font></a> <a href="#" onclick="$('#debug_ajax').html('')"><font color="#FFFFFF"> (clear)</font></a></td>
    </tr>
    <tr>
        <td bgcolor="white" id="serenity_debugOutput" colspan=3 height=100% valign="top">
            <div id="debug_mysql" class="serenity_debugPanel"><?
            $db = sp::db();
            $x = 0;
            foreach($db->queryLog AS $query)
            {
                $x++;
                echo "<b>" . $x . ":</b> " . htmlentities($query) . "<br><br>";
            }
            ?></div>
            <div id="debug_app" class="serenity_debugPanel">
            <?
            $x = 0;
            foreach(sp::app()->getLog() as $log)
            {
                $x++;
                echo "<b>" . $x . ":</b> " . htmlentities($log) . "<br><br>";
            }
            ?></div>

            <div id="debug_ajax" class="serenity_debugPanel">
            </div>
        </td>
    </tr>
    </table>
    </div>
    <?
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}
