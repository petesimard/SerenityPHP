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
	public  $route = null;
	private $currentPage = null;
	private $loadedSnippets = array();
	private $appTitle = 'SerenityPHP Framework';
    public  $config = array();

	private $appLog = array();

	private $noLayout = false;

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
	function __construct($debugMode)
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
	 * Return an array of loaded plugins
	 * @return Ambiguous
	 */
	public function getPlugins()
	{
		return $this->plugins;
	}

	/**
	 * Returns the page identified by name
	 * @param string $name
	 * @return SerenityPage
	 */
	function getPage($name)
	{
		$name = ucfirst($name);
        if(isset($this->pages[$name . "Page"]))
		    return $this->pages[$name . "Page"];
        else
            return null;
	}

	/**
	 * Returns an array of all loaded pages
	 * @param string $name
	 * @return SerenityPage
	 */
	function getPages()
	{
		return $this->pages;
	}

	/**
	 * Returns a reference model. The fields of this model should not be
	 * written to
	 * @param string $name
	 * @return SerenityModel
	 */
	function getModel($name)
	{
		$name = strtolower($name);
		if(!is_object($this->models[$name]))
			return null;

		$className = get_class($this->models[$name]);
		$model = new $className;
        $model->modelName = $this->models[$name]->modelName;

		return $model;
	}

	/**
	 * Returns an array of all loaded models
	 * @param string $name
	 * @return SerenityModel
	 */
	function getModels()
	{
		return $this->models;
	}

	/**
	 * Load a plugin. Plugins should be located in the /plugins or /serenity/plugins folder
	 * @param string $pluginName
	 * @param array $params
	 * @throws SerenityException
	 */
	function loadPlugin($pluginName, $params)
	{
		$pluginName = $pluginName;

		$className = ucfirst($pluginName) . "Plugin";
		$fqClassName = __NAMESPACE__ . "\\" . $className;

		$pluginFile = sp::$baseAppDir .  self::APP_DIRECTORY . "/" . self::PLUGIN_DIRECTORY . "/" . $pluginName . "/" . $pluginName . ".php";
		if(!file_exists($pluginFile))
		    $pluginFile = sp::$baseDir . self::SERENITY_DIRECTORY . "/" . self::PLUGIN_DIRECTORY . "/" . $pluginName . "/" . $pluginName . ".php";

		if(!file_exists($pluginFile))
			throw new SerenityException("Missing plugin file: '" . $pluginName . ".php'");

		include $pluginFile;

		if(!class_exists($fqClassName))
			throw new SerenityException("Missing Plugin Class '" . $className . "'");

		$this->plugins[ucfirst($pluginName)] = new $fqClassName();

		call_user_func(array($this->plugins[ucfirst($pluginName)], 'onAppLoad'), $params);
	}

	/**
	 * Auto load all pages, models, and config files
	 */
	function setupApp()
	{
        // Connect to the database
		include sp::$baseAppDir . self::APP_DIRECTORY . "/" . self::CONFIG_DIRECTORY . "/database.php";
		sp::db()->connect();

        // Load models
		$this->loadModels(sp::$baseAppDir . self::APP_DIRECTORY . "/" . self::MODEL_DIRECTORY);

        // Load app plugins
        include sp::$baseAppDir . self::APP_DIRECTORY . "/" . self::CONFIG_DIRECTORY . "/plugins.php";

        // Load serenity base pages
        $this->loadPages(sp::$baseDir . self::SERENITY_DIRECTORY . "/" . self::PAGE_DIRECTORY);

        // Load app pages
        $this->loadPages(sp::$baseAppDir . self::APP_DIRECTORY . "/" . self::PAGE_DIRECTORY);

        // Global action start page
        include sp::$baseAppDir . self::APP_DIRECTORY . '/global/onActionStart.php';

        // Page app config file
		$this->parseConfig(sp::yaml()->parse(file_get_contents(sp::$baseAppDir . self::APP_DIRECTORY . '/config/config.yaml')));
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
				include($baseDir . "/base/Base" . $fileName);
				include($baseDir . "/" . $fileName);

				$baseFileName = basename($fileName, ".php");

				$className = ucfirst($baseFileName) . "Model";
				$fqClassName = __NAMESPACE__ . "\\" . $className;

				if(!class_exists($fqClassName))
				throw new SerenityException("File " . $fileName . " is missing class definition '" . $className . "'");

				$newModelClass = new $fqClassName;
				$this->models[strtolower($baseFileName)] = $newModelClass;
				$newModelClass::$dir = $baseDir;
				$newModelClass->modelName = ucfirst($baseFileName);
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
						$this->pages[$className]->setDir($baseDir . "/" . $dir);
						$this->pages[$className]->setPageName($dir);

						// Pass the page's config file to the different modules to be parsed
						$pageConfig = sp::yaml()->parse(file_get_contents(  $baseDir . '/' . $dir . '/code/config.yaml' ));
						$this->pages[$className]->parseConfig($pageConfig);
						sp::router()->parsePageConfig($newPageClass, $pageConfig);
					}
				}
			}
		}
	}

	public function disableLayout()
	{
		$this->noLayout = true;
	}

	private function parseConfig($config)
	{
        $this->config = $config;

		if($config['title'])
            $this->appTitle = $config['title'];

		// Allow plugins to parse the config
		foreach($this->plugins as $plugin)
			$plugin->parseAppConfig($config);
	}

	public function getAppTile()
	{
		return $this->appTitle;
	}

	public function getPageTitle()
	{
		if($this->currentPage->getPageTitle() != '')
			$title = $this->currentPage->getPageTitle();
		else
			$title = $this->getAppTile();

		return $title;
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
			$snippetFile = sp::$baseAppDir . self::APP_DIRECTORY . "/" . self::GLOBAL_DIRECTORY . "/" . $snippetName . "_snippet.php";
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
	 * @return SerenityPageRequest
	 */
	function getRoute()
	{
		return $this->route;
	}

	/**
	 * Return the current page
	 * @return SernityPage
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
	 * Will cause any existing action to exit out
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
	public function redirect($pageName, $action = "", $params = array())
	{
		$this->addLogMessage("Redirecting to page '$pageName' : action '$action'");

		$page = $this->getPage($pageName);
		if($page == null)
			throw new SerenityException ("Unable to locate page '" . $pageName . "'");

		// Execute the action
		if($action == "")
		$action = "index";

		$this->setPage($page);
		$this->getCurrentPage()->setCurrentAction($action);

		$paramErrorMsg = $this->getCurrentPage()->setParams($params);

		// If the params had an error, change the route to the error page
		if($paramErrorMsg != "")
		{
			$this->redirectToError($paramErrorMsg);
			return;
		}


		// Set default template as action name
		$page->setTemplate($action);

		$page->executeCurrentAction();

		// Stop any existing action
		throw new SerenityStopException();
	}

	/**
	 * Redirect to the standard error page. Error page can be overwritten in
	 * the pages directory.
	 * @param unknown_type $errorMessage
	 */
	public function redirectToError($errorMessage)
	{
        if($this->route->isAjax)
        {
            $json = array('error' => $errorMessage);
            echo json_encode($json);
            exit;
        }
        else
        {
		    $this->getCurrentPage()->setNotice('error', $errorMessage);
		    $this->redirect($this->getCurrentPage()->getErrorPage(), $this->getCurrentPage()->getErrorAction());
        }
	}

	/**
	 * Start app process
	 */
	function startApp()
	{
		$this->addLogMessage("App start");
		$uri = $_SERVER['REQUEST_URI'];
		$this->route = sp::router()->parseUrl($uri);
        $body_html = "";

		try
		{
			$this->redirect($this->route->page, $this->route->action, $this->route->params);
		}
		catch (SerenityStopException $e){
			// Expected
		}

		// Render the page
        if($this->route->isAjax)
        {
            // Ajax request, only include the JSON array
            $body_html = json_encode($this->getCurrentPage()->getJSON());
        }
        else
        {
            // Render template
            if(!$this->getCurrentPage()->isNoTempltes())
		        $body_html = $this->getCurrentPage()->render();
            else if($this->getCurrentPage()->jsonCount() > 0)
                $body_html = json_encode($this->getCurrentPage()->getJSON());
        }

		// Render the layout with the included body_html
		if(!$this->route->isAjax && !$this->noLayout && !$this->getCurrentPage()->isNoLayout())
		{
			$this->renderLayout($body_html);

			// Display debug bar
			if($this->isDebugMode())
			{
				echo $this->getSnippet("debug");
			}
		}
		else
		{
			echo $body_html;
		}
	}

	/**
	 * Render the layout after the template has been rendered
	 * @param string $body_html
	 */
	private function renderLayout($body_html)
	{
		foreach(sp::app()->getPlugins() as $plugin)
		{
			foreach($plugin->getTemplateVariables() as $pageVarName=>$pageVarVal)
			{
				$$pageVarName = $pageVarVal;
			}
		}

        foreach( $this->getCurrentPage()->getPageVariables() as $pageVarName=>$pageVarVal)
        {
            $$pageVarName = $pageVarVal;
        }


		$this->addLogMessage("Rendering layout");
		include sp::$baseAppDir . self::APP_DIRECTORY . "/global/layout.php";
	}

    public function config()
    {
        return $this->configArray;
    }

	public function __call($pluginName, $args)
	{
		$pluginName = ucfirst($pluginName);
		// make sure the function exists
		if(array_key_exists($pluginName, $this->plugins))
		{
			return $this->plugins[$pluginName];
		}

		throw new SerenityException ('Call to undefined plugin: ' . $pluginName);
	}
}
?>
