<?php
namespace Serenity;

    spl_autoload_register(function($name) {
        $name = explode('\\', $name);
        $name = $name[sizeof($name)-1];

        $filname = sp::$baseAppDir . '/app/classes/' . $name . '.php';
        if (file_exists($filname) )
            include $filname;
        else
            throw new SerenityException("Unable to load class: '$name'. Place custom classes in app/classes to be auto loaded.");
    });

    function ctype_int($text)
    {
        return preg_match('/^-?[0-9]+$/', (string)$text) ? true : false;
    }

	function sendTo($url)
	{
		header('Location: ' . $url);
	    exit;
	}


	/**
     * Returns the URL of a page/action with no surrounding HTML
     * Params should be passed in a key=>value format
     * @param string $page
     * @param string $action
     * @param array $params
     * @return string
     */
    function getPageUrl($page, $action = "", $params = array())
    {
        $url = "/" .  $page;

        if($action == "")
        {
            $useDefaultAction = false;
            foreach($params as $paramName=>$paramVal)
            {
                if($paramName != "")
                {
                    $useDefaultAction = true;
                    break;
                }
            }

            if($useDefaultAction)
                $action = "index";
        }

        if($action != "")
        	$url .= "/" . $action;


        foreach($params as $paramName=>$paramVal)
        {
            $paramVal = urlencode($paramVal);
            $paramVal = str_replace(array('%2F','%5C'), array('%252F','%255C'), $paramVal);
            $url .= "/" . ($paramName ? $paramName . "/" : "")  . $paramVal;
        }

        $url = sp::router()->shortenUrl($url);

        return $url;
    }

    /**
     * Returns a full html hyperlink to a page/action
     * @param string $page
     * @param string $action
     * @param string $linkText
     * @param array $params
     * @return string
     */
    function getPageLink($page, $action, $linkText, $params = array())
    {
        $url = getPageUrl($page, $action, $params);
        $html = "<a href=\"" . $url . "\">" . $linkText . "</a>";

        return $html;
    }

    function getConfirmPageLink($page, $action, $linkText, $confirmText = 'Are you sure?', $params = null)
    {
        sp::app()->confirmLinkId++;

        $url = getPageUrl($page, $action, $params);
        $html = '<a href="javascript:void(0);" onclick="showConfirmLink(' . sp::app()->confirmLinkId . ');">' . $linkText .
        '</a> <span style="font-size : 12px; display : none" id="sp_confirm_' . sp::app()->confirmLinkId . '">' . $confirmText .
         ' <a href="' . $url . '">yes</a> / <a href="javascript:void(0);" onclick="hideConfirmLink(' . sp::app()->confirmLinkId . ');">no</a></span>';

        return $html;
    }
