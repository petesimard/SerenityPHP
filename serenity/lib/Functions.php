<?php
    /**
     * Returns the URL of a page/action with no surrounding HTML
     * Params should be passed in a key=>value format
     * @param string $page
     * @param string $action
     * @param array $params
     * @return string
     */
    function getPageUrl($page, $action = "", $params = null)
    {
        $url = "/" .  $page;

        if($action == "")
            $action = "index";

        $url .= "/" . $action;

        if($params != null)
        {
            foreach($params as $paramName=>$paramVal)
            {
                $url .= "/" . $paramName . "/" . $paramVal;
            }
        }

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
    function getPageLink($page, $action, $linkText, $params = null)
    {
        $url = getPageUrl($page, $action, $params);
        $html = "<a href=\"" . $url . "\">" . $linkText . "</a>";

        return $html;
    }
