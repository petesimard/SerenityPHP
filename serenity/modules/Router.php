<?php
namespace Serenity;

/**
 * Contains the information about the current page request
 * @author Pete
 *
 */
class SerenityPageRequest
{
    public $page = null;
    public $action = "";
    public $params = array();
}

/**
 * Parses the current URL and request information and returns
 * a SerenityPageRequest
 * @author Pete
 *
 */
class SerenityRouter
{
    private static $mThis = null;

    /**
     * Returns the standard error page route
     * Error page can be overriden by creating a new page called error
     * inside the base /pages folder
     * @return SerenityPageRequest
     */
    function getErrorPage()
    {
        $pageRequest = new SerenityPageRequest();
        $pageRequest->action = "error";
        $pageRequest->page = "error";

        return $pageRequest;
    }

    /**
     * Parse a URL for the route
     * @param string $url
     * @throws SerenityException
     * @return SerenityPageRequest
     */
    function parseUrl($url)
    {
        $pageName = "";
        $actionName = "";
        $params = array();

        $paramName = "";

        $urlArray = explode("/", $url);
        foreach($urlArray as $urlComponent)
        {
            if($urlComponent == "")
                continue;

            if($pageName == "")
            {
                $pageName = $urlComponent;
                continue;
            }

            if($actionName == "")
            {
                $actionName = $urlComponent;
                continue;
            }

            if($paramName == "")
            {
                $paramName = $urlComponent;
                continue;
            }


            $params[$paramName] = urldecode($urlComponent);
            $paramName = "";
        }

        // Register the POST vars
        foreach($_POST as $postVarName=>$postVarVal)
        {
            $params[$postVarName] = urldecode($postVarVal);
        }

        if($pageName == "")
            $pageName = "home";

        if($actionName == "")
            $actionName = "index";

        $page = sf::app()->getPage($pageName);

        if($page == null)
            throw new SerenityException("Page '" . $pageName . "' not found");

        $pageRequest = new SerenityPageRequest();
        $pageRequest->action = $actionName;
        $pageRequest->page = $pageName;
        $pageRequest->params = $params;

        return $pageRequest;
    }

    public function __construct()
    {
        sf::$router = $this;
    }

    static function getInstance()
    {
        if(self::$mThis == null)
            self::$mThis = new SerenityRouter();

        return self::$mThis;
    }
}

// Create singleton
SerenityRouter::getInstance();
?>
