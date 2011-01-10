<?php
namespace Serenity;

function debugSnippet($params)
{
    ob_start();
    ?>
	<script src="/js/jquery.min.js"></script>
	<script src="/js/jquery-ui.min.js"></script>
    
    <script type="text/javascript">
    var isExpanded = false;
    var shownPage = '';
    function debugShowMysql()
    {
        if(isExpanded && shownPage=='mysql')
        {
            $('#debugDiv').animate({"width": "-=400px", "height": "-=500px"}, "slow");
            $('#debug_mysql').hide();
            $('#debug_applog').hide();
            isExpanded = false;
            return;
        }
        else if(!isExpanded)
        {
            $('#debugDiv').animate({"width": "+=400px", "height": "+=500px"}, "slow");
            isExpanded = true;
        }

    	$('#debug_mysql').show();
    	$('#debug_applog').hide();
        shownPage = 'mysql';
    }
    
    function debugShowLog()
    {
        if(isExpanded && shownPage=='appLog')
        {
            $('#debugDiv').animate({"width": "-=400px", "height": "-=500px"}, "slow");
            $('#debug_mysql').hide();
            $('#debug_applog').hide();
            isExpanded = false;
            return;
        }
        else if(!isExpanded)
        {
            $('#debugDiv').animate({"width": "+=400px", "height": "+=500px"}, "slow");
            isExpanded = true;
        }

    	$('#debug_applog').show();
    	$('#debug_mysql').hide();
        shownPage = 'appLog';
    }

    debug_applog
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
    <td align="center"><a href="javascript:debugShowLog()"><font color="#E6F10E">App Log</font></a></td><td align="center"><a href="javascript:debugShowMysql()"><font color="#E6F10E">MySQL</font></a></td>
    </tr>
    <tr>
        <td bgcolor="white" id="debugOutput" colspan=2 height=100% valign="top">
        <div id="debug_mysql" style="overflow:auto;display:none;vertical-align:top"><?
        $db = sp::db();
        foreach($db->queryLog AS $query)
        {
            $x++;
            echo "<b>" . $x . ":</b> " . htmlentities($query) . "<br><br>";
        }
        ?></div>
        <div id="debug_applog" style="overflow:auto;display:none;vertical-align:top">
        <?
        $x = 0;
        foreach(sp::app()->getLog() as $log)
        {
            $x++;
            echo "<b>" . $x . ":</b> " . htmlentities($log) . "<br><br>";
        }
        ?></div>        
        
        </td>
    </tr>
    </table>
    </div>
    <?
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
}
