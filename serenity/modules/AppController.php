<?php
namespace Serenity;

/**
 * App Controller. Provides access to other modules and controls
 * coordination between modules
 * @author Pete
 *
 */
class SerenityAppController
{
    private $debugMode = true;

    private $pages = array();
    private $models = array();
    private $plugins = array();
    public $route = null;
    private $currentPage = null;  
    private $loadedSnippets = array();

    private $appLog = array(); 
    
    const APP_DIRECTORY = '/app';
    const SERENITY_DIRECTORY = '/serenity';
    
    const PAGE_DIRECTORY = 'pages';
    const MODEL_DIRECTORY = 'models';
    const PLUGIN_DIRECTORY = 'plugins';
    const CONFIG_DIRECTORY = 'config';
    const GLOBAL_DIRECTORY = 'global';
     
    /**
     * Constructor
     * @param boolean $debugMode
     */
    function SerenityAppController($debugMode)
    {
        $this->debugMode = $debugMode;

        sp::$currentApp = $this;

		$this->startSession();
        
        $this->setupApp();
        $this->startApp();
    }
    
    /**
     * Start the PHP Session
     */
    private function startSession()
    {
    	session_start();
    }
    
    /**
     * Add a log message to be added to the app log
     * @param unknown_type $message
     */
    public function addLogMessage($message)
    {
    	if($this->isDebugMode())
    		$this->appLog[] = $message;
    }
    
    /**
     * Get the app log
     * @return unknown
     */
    public function getLog()
    {
    	return $this->appLog;
    }

    /**
     * Returns the page identified by name
     * @param string $name
     * @return SerenityPage
     */
    function getPage($name)
    {
        $name = ucfirst($name);
        return $this->pages[$name . "Page"];
    }

    /**
     * Returns a reference model. The fields of this model should not be
     * written to
     * @param string $name
     * @return SerenityModel
     */
    function getModel($name)
    {
        $name = ucfirst($name);
        return $this->models[$name . "Model"];
    }
    
    /**
     * Load a plugin. Plugins should be located in the /plugins or /serenity/plugins folder
     * @param string $pluginName
     * @param array $params
     * @throws SerenityException
     */
    function loadPlugin($pluginName, $params)
    {
    	$pluginName = ucfirst($pluginName);
    	
    	$className = $pluginName . "Plugin";
		$fqClassName = __NAMESPACE__ . "\\" . $className;

        $pluginFile = sp::$baseDir .  self::APP_DIRECTORY . "/" . self::PLUGIN_DIRECTORY . "/" . $pluginName . ".php";
        if(!file_exists($snippetFile))
			$pluginFile = sp::$baseDir . self::SERENITY_DIRECTORY . "/" . self::PLUGIN_DIRECTORY . "/" . $pluginName . ".php";

        if(!file_exists($pluginFile))
            throw new SerenityException("Missing plugin file: '" . $pluginName . ".php'");
        
        include $pluginFile;
		
		if(!class_exists($fqClassName))
        	throw new SerenityException("Missing Plugin Class '" . $className . "'");
                		
    	$this->plugins[$pluginName] = new $fqClassName();
    	
    	call_user_func(array($this->plugins[$pluginName], 'onAppLoad'), $params);
    }

    /**
     * Auto load all pages, models, and config files
     */
    function setupApp()
    {
        // Create a list of pages
        $this->loadPages(sp::$baseDir . self::SERENITY_DIRECTORY . "/" . self::PAGE_DIRECTORY);
        $this->loadPages(sp::$baseDir . self::APP_DIRECTORY . "/" . self::PAGE_DIRECTORY);

        $this->loadModels(sp::$baseDir . self::APP_DIRECTORY . "/" . self::MODEL_DIRECTORY);

        include sp::$baseDir . self::APP_DIRECTORY . "/" . self::CONFIG_DIRECTORY . "/database.php";
        sp::db()->connect();
        
        include sp::$baseDir . self::APP_DIRECTORY . "/" . self::CONFIG_DIRECTORY . "/plugins.php";
    }

    /**
     * Load all models in specified directory
     * @param string $baseDir
     * @throws SerenityException
     */
    function loadModels($baseDir)
    {
        $handle = opendir($baseDir);
        while (false !== ($fileName = readdir($handle)))
        {
            if($fileName == "." || $fileName == "..")
                continue;

            if(!is_dir($baseDir . "/" . $fileName))
            {
                include($baseDir . "/" . $fileName);

                $className = ucfirst(basename($fileName, ".php")) . "Model";
                $fqClassName = __NAMESPACE__ . "\\" . $className;
                
                if(!class_exists($fqClassName))
                    throw new SerenityException("File " . $fileName . " is missing class definition '" . $className . "'");
                
                $newModelClass = new $fqClassName;
                $this->models[$className] = $newModelClass;
                $this->models[$className]->dir = $baseDir;
            }
        }
    }

    /**
     * Load all pages in specified directory
     * @param string $baseDir
     * @throws SerenityException
     */
    
    function loadPages($baseDir)
    {
        $handle = opendir($baseDir);
        while (false !== ($dir = readdir($handle)))
        {
            if($dir == "." || $dir == "..")
                continue;

            if(is_dir($baseDir . "/" . $dir))
            {
                $codePage = $baseDir . "/" . $dir . "/code/code.php";

                if(file_exists($codePage))
                {
                    include($codePage);

                    $className = ucfirst($dir) . "Page";
                    $fqClassName = __NAMESPACE__ . "\\" . $className;
                    if(!class_exists($fqClassName))
                    {
                        throw new SerenityException("File " . $codePage . " is missing class definition '" . $className . "'");
                    }
                    else
                    {
                        $newPageClass = new $fqClassName;
                        $this->pages[$className] = $newPageClass;
                        $this->pages[$className]->dir = $baseDir . "/" . $dir;
                        $this->pages[$className]->pageName = $dir;
                    }
                }
            }
        }
    }

    /**
     * Returns html from a snippet. Snippets are located in the /global or
     * /serenity/global directories. Snippets should contain a function named
     * [snippetName]_snippet.php and should contain a function named
     * [snippetName]Snippet($params)
     * @param string $snippetName
     * @param array $params
     * @throws SerenityException
     * @return string
     */
    function getSnippet($snippetName, $params = null)
    {
    	if(!array_key_exists($snippetName, $this->loadedSnippets))
    	{
	        $snippetFile = sp::$baseDir . self::APP_DIRECTORY . "/" . self::GLOBAL_DIRECTORY . "/" . $snippetName . "_snippet.php";
	        if(!file_exists($snippetFile))
	            $snippetFile = sp::$baseDir . self::SERENITY_DIRECTORY . "/" . self::GLOBAL_DIRECTORY . "/" . $snippetName . "_snippet.php";
	
	        if(!file_exists($snippetFile))
	        {
	            throw new SerenityException("Missing snippet file: '" . $snippetName . "_snippet.php'");
	        }
	
	        include $snippetFile;
    	}
    	
    	$this->loadedSnippets[$snippetName] = true;

        $func = __NAMESPACE__ . "\\" . $snippetName . "Snippet";
        $html = call_user_func($func, $params);

        return $html;
    }


    /**
     * Returns true/false if the app is in debug mode
     * @return boolean
     */
    function isDebugMode()
    {
        return $this->debugMode;
    }

    
    /**
     * Return the current route
     * @return Ambiguous
     */
    function getRoute()
    {
        return $this->route;
    }

    /**
     * Return the current page
     * @return Ambiguous
     */
    function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Set the current page
     * @param unknown_type $page
     */
    protected function setPage($page)
    {
    	$this->currentPage = $page;
    }
    
    /**
     * Redirect to another page/action without causing a browser refresh
     * 
     * Example
     * 
     * sp::app()->redirect("home", "index");
     * 
     * will change the current page to home and use the index action
     * @param string $pageName
     * @param string $action
     * @throws SerenityException
     */
    public function redirect($pageName, $action = "")
    {
    	$this->addLogMessage("Redirecting to page '$pageName' : action '$action'");
    	
    	$page = $this->getPage($pageName);
    	if($page == null)
    		throw new SerenityException ("Unable to locate page '" . $pageName . "'");
    	
    	$this->setPage($page);
    	$this->getCurrentPage()->setCurrentAction($action);
    	
    	$paramErrorMsg = $this->getCurrentPage()->setParams($this->route->params);

    	// If the params had an error, change the route to the error page
        if($paramErrorMsg != "")
        {
        	$this->redirectToError($paramErrorMsg);
        	return;
        }
        
        // Execute the action
        if($action == "")
        	$action = "index";
        	        
        // Set default template as action name
		$page->setTemplate($action);        
        	
		$page->executeCurrentAction();		
    }
    
    /**
     * Redirect to the standard error page. Error page can be overwritten in
     * the pages directory.
     * @param unknown_type $errorMessage
     */
    public function redirectToError($errorMessage)
    {
		$this->getCurrentPage()->setPageNotice('error', $errorMessage);
    	
		$this->route = sp::$router->getErrorPage();
        $this->redirect($this->route->page, $this->route->action);
    }
    
    /**
     * Start app process
     */
    function startApp()
    {
    	$this->addLogMessage("App start");
        $uri = $_SERVER['REQUEST_URI'];
        $this->route = sp::router()->parseUrl($uri);
        
        try
        {
        	$this->redirect($this->route->page, $this->route->action);	
        }
        catch (SerenityStopException $e){
        	// Expected
        }        

        // Render the page with its template
        $body_html = $this->getCurrentPage()->render();

        // Render the layout with the included body_html
        $this->renderLayout($body_html);

        // Display debug bar
        if($this->isDebugMode())
        {
            echo $this->getSnippet("debug");
        }
    }

    /**
     * Render the layout after the template has been rendered
     * @param string $body_html
     */
    private function renderLayout($body_html)
    {
    	$this->addLogMessage("Rendering layout");
        include sp::$baseDir . self::APP_DIRECTORY . "/global/layout.php";
    }
    
     public function __call($pluginName, $args)  
     {  
         // make sure the function exists  
         if(array_key_exists($pluginName, $this->plugins))
         {
             return $this->plugins[$pluginName];  
         }  
   
         throw new SerenityException ('Call to undefined plugin: ' . $pluginName);  
     }     
}
?>
