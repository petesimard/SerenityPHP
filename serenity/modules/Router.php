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
    public $url = '';
    
    public function getUrl()
    {
        return $this->url;
    }
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
            return $url;
            
        $matchedRoute = null;
        
        if(count($urlArray) > 2)
            $actionName = $urlArray[2];
        else
            $actionName = '';

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

        foreach($pageRoutes as $routeName => $routeInfo)
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

            // Route was matched
            if($matchError == false)
            {
                if(isset($urlArray[$urlComponentIdx]))
                {
                    // There is more to the route.. assume it's extra params
                    $paramName = '';                    
                    for($x=$urlComponentIdx; $x<count($urlArray); $x++)
                    {
                        $urlComponent = $urlArray[$x];
                        if($urlComponent == "")
                            continue;

                        if($paramName == "")
                        {
                            $paramName = $urlComponent;
                            continue;
                        }


                        $params[$paramName] = str_replace(array('%2F','%5C'), array('/','\\'), urldecode($urlComponent));                    
                        $paramName = "";        
                    }            
                }
                
                $matchedRoute = $routeInfo;
                $matchedRouteName = $routeName;
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
            $pageRequest->url = $url;
            
            sp::app()->addLogMessage('Matched Route: ' . $matchedRouteName . ' on Page: ' . $pageName);

            return $pageRequest;
        }

        // Failed to match a route
        return null;
    }

    /**
    *  If no user specified route was matched, create a genaric route with the URL components
    */
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
        {
            // Didn't match a route, try matching a route of the default page
           // $homeUrl = '/home' . $url;
//            $pageRequest = $this->matchRoute($homeUrl);
//            if(!is_null($pageRequest))         
//                return $pageRequest;
            
            throw new SerenityException("Page '" . $pageName . "' not found");        
        }

        $pageRequest = new SerenityPageRequest();
        $pageRequest->action = $actionName;
        $pageRequest->page = $pageName;
        $pageRequest->params = $params;
        $pageRequest->url = $url;

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
        {
            if(is_null($pageRequest))
            {
                // No routes matched, use default
                $pageRequest = $this->getDefaultRoute($url);
            }
        }

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

        foreach($config['routes'] as $routeName => $routeInfo)
        {
            if($routeInfo['url'] != '' && $routeInfo['action'] != '')
            {
                $urlPieces = explode('/', $routeInfo['url']);
                $route = array('action' => $routeInfo['action'], 'url' => $urlPieces);
                $this->pageRoutes[$page->getName()][$routeName] = $route;
            }
        }
    }

    public function __construct()
    {

    }

}

sp::$router = new SerenityRouter();;
?>
