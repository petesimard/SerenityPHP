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
    public $context = null;
    public $isAjax = false;
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

    private $pageRoutes = array();

    function shortenUrl($url)
    {
        $urlArray = explode("/", $url);

        if(count($urlArray) < 2)
            return $url;

        $pageName = $urlArray[1];
        if(strlen($pageName) == 0)
            return $url;

        if(isset($this->pageRoutes[$pageName]))
            $pageRoutes = $this->pageRoutes[$pageName];
        else
            return $url;

        if(count($pageRoutes) == 0)
            return $url
            ;
        $matchedRoute = null;
        $actionName = $urlArray[2];

        $paramName = '';
        $paramsOriginal = array();

        for($x=3; $x < sizeof($urlArray); $x++)
        {
            $urlComponent = $urlArray[$x];

            if($paramName == '')
            {
                $paramName = $urlComponent;
                continue;
            }

            $paramsOriginal[$paramName] = $urlComponent;
            $paramName = '';
        }

        foreach($pageRoutes as $matchedRoute)
        {
            if($matchedRoute['action'] != $actionName)
            {
                continue;
            }

            $newParams = '';
            $params = $paramsOriginal;
            $failed = false;
            foreach($matchedRoute['url'] as $routeUrlComponent)
            {
                    preg_match("/\(\+(.*)\)/", $routeUrlComponent, $intMatches);

                    // Match for String values
                    preg_match("/\((.*)\)/", $routeUrlComponent, $stringMatches);

                    if($intMatches)
                    {
                        $paramName = $intMatches[1];
                        if(isset($params[$paramName]) && ctype_int($params[$paramName]))
                        {
                            $newParams .= '/' . $params[$paramName];
                            unset($params[$paramName]);
                        }
                        else
                        {
                            $failed = true;
                            break;
                        }
                    }
                    else if(!$intMatches && $stringMatches)
                    {
                        $paramName = $stringMatches[1];
                        if(isset($params[$paramName]))
                        {
                            $newParams .= '/' . $params[$paramName];
                            unset($params[$paramName]);
                        }
                        else
                        {
                            $failed = true;
                            break;
                        }
                    }
                    else
                    {
                        $newParams .= '/' . $routeUrlComponent;
                    }
            }

            if($failed)
                continue;

            // there was an extra parameter that wasn't matched
            if(count($params) != 0)
                continue;

            $url = '/' . $pageName . $newParams;
            return $url;
        }

        return $url;
    }

    function matchRoute($url)
    {
        $urlArray = explode("/", $url);

        if(count($urlArray) < 2)
        	return null;

        $pageName = $urlArray[1];
        if(strlen($pageName) == 0)
            return null;

        $pageRoutes = array();
        if(isset($this->pageRoutes[$pageName]))
        	$pageRoutes = $this->pageRoutes[$pageName];

        if(count($pageRoutes) == 0)
            return null;

        $matchedRoute = null;

        foreach($pageRoutes as $routeInfo)
        {
            $urlComponentIdx = 2;
            $params = array();
            $matchError = false;

            foreach($routeInfo['url'] as $routeUrlComponent)
            {
                if(!isset($urlArray[$urlComponentIdx]))
                {
                    $matchError = true;
                    break;
                }


                // Match for INT values
                preg_match("/\(\+(.*)\)/", $routeUrlComponent, $intMatches);

                // Match for String values
                preg_match("/\((.*)\)/", $routeUrlComponent, $stringMatches);

                if($intMatches && ctype_int($urlArray[$urlComponentIdx]))
                {
                    $params[$intMatches[1]] = str_replace(array('%2F','%5C'), array('/','\\'), urldecode($urlArray[$urlComponentIdx]));
                }
                else if(!$intMatches && $stringMatches)
                {
                    $params[$stringMatches[1]] = str_replace(array('%2F','%5C'), array('/','\\'), urldecode($urlArray[$urlComponentIdx]));
                }
                else if($routeUrlComponent != $urlArray[$urlComponentIdx])
                {
                    $matchError = true;
                    break;
                }

                $urlComponentIdx++;
            }

            if($matchError == false && !isset($urlArray[$urlComponentIdx]))
            {
                $matchedRoute = $routeInfo;
                break;
            }
        }

        if($matchedRoute)
        {
            $page = sp::app()->getPage($pageName);

            if($page == null)
                throw new SerenityException("Page '" . $pageName . "' not found");

            $pageRequest = new SerenityPageRequest();
            $pageRequest->action = $matchedRoute['action'];
            $pageRequest->page = $pageName;
            $pageRequest->params = $params;

            return $pageRequest;
        }

        return null;
    }

    function getDefaultRoute($url)
    {
        $params = array();
        $urlArray = explode("/", $url);
        $pageName = "";
        $actionName = "";
        $paramName = "";

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


            $params[$paramName] = str_replace(array('%2F','%5C'), array('/','\\'), urldecode($urlComponent));
            $paramName = "";
        }

        if($pageName == "")
            $pageName = "home";

        if($actionName == "")
            $actionName = "index";

        $page = sp::app()->getPage($pageName);

        if($page == null)
            throw new SerenityException("Page '" . $pageName . "' not found");

        $pageRequest = new SerenityPageRequest();
        $pageRequest->action = $actionName;
        $pageRequest->page = $pageName;
        $pageRequest->params = $params;

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
        $params = array();

        // Strip traling slash
        $url = rtrim($url, '/');

        // Check if the URL matches a page route
        $pageRequest = $this->matchRoute($url);
        if(is_null($pageRequest))
            $pageRequest = $this->getDefaultRoute($url);

        // Register the POST vars
        foreach($_POST as $postVarName=>$postVarVal)
        {
            if(is_array($postVarVal))
            {
            	$params[$postVarName] = $postVarVal;
            	foreach($params[$postVarName] as $idx=>$val)
            	{
            		$params[$postVarName][$idx] = str_replace(array('%2F','%5C'), array('/','\\'), urldecode($val));
            	}
            }
            else
            	$params[$postVarName] = str_replace(array('%2F','%5C'), array('/','\\'), urldecode($postVarVal));
        }

        // Append the POST vars to the request
        $pageRequest->params = array_merge($pageRequest->params, $params);

        /* AJAX check  */
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $pageRequest->isAjax = true;
        }

        return $pageRequest;
    }

    public function parsePageConfig($page, $config)
    {
        if(!isset($config['routes']) || is_null($config['routes']))
            return;

        foreach($config['routes'] as $routeInfo)
        {
            if($routeInfo['url'] != '' && $routeInfo['action'] != '')
            {
                $urlPieces = explode('/', $routeInfo['url']);
                $route = array('action' => $routeInfo['action'], 'url' => $urlPieces);
                $this->pageRoutes[$page->getName()][] = $route;
            }
        }
    }

    public function __construct()
    {

    }

}

sp::$router = new SerenityRouter();;
?>
