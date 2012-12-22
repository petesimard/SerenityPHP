<?php
namespace Serenity;

/**
 * Main Page class. Impliment action functions inside
 * child class definitions.
 * @author Pete
 *
 */
abstract class SerenityPage
{
	// Note: all class vars should be declared private or protected
	// to ensure there is no collision with use data

    protected $data = array();
    protected $templateName = "";
    protected $pageName = "";
    protected $currentAction = "";
    protected $dir;
    protected $paramDefinitions = array();
    protected $params = array();
    protected $formModel = null;
    protected $isFormValid = false;
    private   $noticeMessage = "";
    private   $noticeType = "";
    private   $errorPage = "error";
    private   $errorAction = "index";
    private   $pageTitle = '';
    private   $json = array();
    private   $noTemplates = false;
    private   $noLayout = false;

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return (isset($this->data[$name]) ? $this->data[$name] : null);
    }

    public function getPageVariables()
    {
        return $this->data;
    }

    /**
     * Set the template to render after the page action is finished
     * @param string $templateName
     */
    public function setTemplate($templateName)
    {
    	sp::app()->addLogMessage("Set template '" . $templateName . "'");
        $this->templateName = $templateName;
    }

    /**
     * Assigned automaticly from the router
     * @param array $params
     * @return string
     */
    public function setParams($params)
    {
        $this->params = $params;

        $errorMsg = $this->validateParams();
        if($errorMsg != "")
            return $errorMsg;

        $this->bindForm();

        return "";
    }

    /**
     * Return the form bound to the page from an HTTP post
     * @return SerenityModel
     */
    public function getForm()
    {
        return $this->formModel;
    }

    /**
     * Binds the HTTP post data to a model
     * @throws SerenityException
     */
    public function bindForm()
    {
    	$formModel = null;

        foreach($this->params as $paramName => $paramValue)
        {
            if($paramName == "model_name")
            {
                $model = sp::app()->getModel($paramValue);
                if($model == null)
                    throw new SerenityException("Invalid model passed: " . $paramName);

                $className = get_class($model);
                $formModel = $model;
                break;
            }
        }

        if($formModel == null)
            return;

        sp::app()->addLogMessage("Bound form to page '" . get_class($formModel) . "'");

        // Check for a primary key to see if it's an update or an insert
        $primaryKey = $formModel->getPrimaryKey();
        $primaryKeyField = $formModel->tableName . "_" . $primaryKey;
        $primaryKeyParam = (isset($this->params[$primaryKeyField]) ? $this->params[$primaryKeyField] : null);

        if($primaryKeyParam != null)
        {
        	$formModel = $formModel->query($primaryKeyParam)->fetchOne();
        	if($formModel == null)
        	{
	        	$this->isFormValid = false;
	        	$this->setNotice('error', "Invalid record");
	        	return;
        	}

        	$formModel->undirtyFields();
        }

        foreach($this->params as $paramName => $paramValue)
        {
            if(substr($paramName, 0, strlen($formModel->tableName)+1) == ($formModel->tableName . "_"))
            {
                $fieldName = substr($paramName, strlen($formModel->tableName)+1);
                $modelField = $formModel->getRawField($fieldName);
                if($modelField != null)
                {
                    $formModel->setField($fieldName, $paramValue);
                }
            }
        }

        $this->formModel = $formModel;
        $this->validateForm();

        if(!$this->isFormValid())
        {
            $errorMsg = "";
            foreach($this->formModel->getFields() as $field)
            {
                if($field->formError != "")
                {
                    if($errorMsg != "")
                        $errorMsg .= "<br>";

                    $errorMsg .= $field->formError;
                }
            }

            $this->setNotice('error', $errorMsg);
        }
    }

    /**
     * Check each field of the bound model with its associated validator
     */
    private function validateForm()
    {
        $this->isFormValid = true;

        foreach($this->formModel->getFields() as $field)
        {
            $errorMessage = sp::validator()->validate($field->getRawValue(), $field->paramDefinition, $this->formModel);
            if($errorMessage != "")
            {
                $field->formError = $errorMessage;
                $this->isFormValid = false;
            }
        }
    }

    /**
     * Returns true/false if the form passed validation
     * Pass an optional list of field names to limit fields
     * to only those specified
     * @param array $limitFields
     * @return boolean
     */
    public function isFormValid($limitFields = null)
    {
    	if($limitFields != null && $this->formModel != null)
    	{
    		foreach($this->formModel->getFields() as $field)
    		{
    			if($field->getRawValue() != "" && $field->type != "form")
    			{
    				if(!in_array($field->name, $limitFields))
    				{
    					$this->isFormValid = false;
    					$this->setNotice('error', "Invalid field passed to form: '" . $field->name . "'");
    					return false;
    				}
    			}
    		}
    	}

        return $this->isFormValid;
    }

    /**
     * Set a notice (error, status, notice, etc message). Notice will
     * be saved to the session and will be cleared when it is displayed
     * @param string $type
     * @param string $noticeMessage
     */
    public function setNotice($type, $noticeMessage)
    {
        $_SESSION['noticeMessage'] = $noticeMessage;
        $_SESSION['noticeType'] = $type;
    }

    /**
     * Returns true/false if the session has a notice message
     * @return boolean
     */
    public function hasNotice()
    {
        return ($_SESSION['noticeMessage'] != "" ? true : false);
    }

    /**
     * Returns an array containing the notice information. Will clear
     * any existing notice message.
     * "message", "type"
     * @return array
     */
    public function getNotice()
    {
    	if(isset($_SESSION['noticeMessage']))
    	{
    		$ret = array('message' => $_SESSION['noticeMessage'], 'type' => $_SESSION['noticeType']);
    	}
    	else
    	{
    		$ret = array('message' => '', 'type' => '');
    	}

    	// Clear the session notice once it's retrieced
    	$this->setNotice('', '');

    	return $ret;
    }

    public function parseConfig($config)
    {
        if(isset($config['title']) && $config['title'] != "")
        {
            $this->setPageTitle($config['title']);
        }

        if(isset($config['noTemplates']) && $config['noTemplates'] == "true")
        {
            $this->noTemplates = true;
        }
        
        if(isset($config['noLayout']) && $config['noLayout'] == "true")
        {
            $this->noLayout = true;
        }        

        // Allow plugins to parse the config
        foreach(sp::app()->getPlugins() as $plugin)
            $plugin->parsePageConfig($this, $config);
    }

    public function setNoTempltes($bool)
    {
        $this->noTemplates = $bool;
    }

    public function isNoTempltes()
    {
        return $this->noTemplates;
    }
    
    public function setNoLayout($bool)
    {
        $this->noLayout = $bool;
    }

    public function isNoLayout()
    {
        return $this->noLayout;
    }

    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
    }

    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    public function validateParams()
    {
        foreach($this->paramDefinitions as $paramName => $paramDefinition)
        {
            $paramValue = (isset($this->params[$paramName]) ? $this->params[$paramName] : null);

            $errorMessage = sp::validator()->validate($paramValue, $paramDefinition);
            if($errorMessage != "")
                return $errorMessage;
        }

        return "";
    }

    /**
     * Get page POST or GET parameter
     * @param string $name
     * @return multitype:
     */
    public function getParam($name, $default = null)
    {
        return (isset($this->params[$name]) ? $this->params[$name] : $default);
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     *  Used to add a parameter definition during page setup ([actionname]_registerParams)
     *
     *  Example for Page named "home":
     *
     *  function home_registerParams()
     *  {
     *  	addParam("pageNumber",  array("type" => "string", "minLen" => 2, "maxLen" => 50));
     *  }
     * @param string $paramName
     * @param array $validator
     * @throws SerenityException
     */
    public function addParam($paramName, $validator)
    {
        $newParam = new ParamDefinition($validator);
        $newParam->name = $paramName;

        if($newParam->name == "")
                    throw new SerenityException("Missing required parameter field: 'name'");

        $this->paramDefinitions[$newParam->name] = $newParam;
    }

    /**
     * Called during initial page setup
     */
    public function parseActionParams()
    {
    	$this->paramDefinitions = array();

        $methods = get_class_methods($this);
        foreach ($methods as $method_name)
        {
        	if(substr($method_name, 0, strlen($this->currentAction)) == $this->currentAction)
        	{
	            if($method_name == $this->currentAction . "_registerParams")
	            {
	                $this->{$method_name}();
	            }
        	}
        }
    }

    /**
     * Execute the current action on the current page
     */
    public function executeCurrentAction()
    {
        $actionName = $this->currentAction;
        sp::app()->addLogMessage("Executing action '" . $actionName . "'");

        foreach(sp::app()->getPlugins() as $plugin)
        {
        	$plugin->onActionStart($this);
        }

        onActionStart($this, $actionName);

        $this->{$actionName}();

		foreach(sp::app()->getPlugins() as $plugin)
        {
        	$plugin->onActionEnd($this);
        }

    }

    /**
     * Set the action to run
     * @param string $actionName
     * @throws SerenityException
     */
    public function setCurrentAction($actionName)
    {
        if(!method_exists($this, $actionName))
        {
            throw new SerenityException("Missing action: " . get_class($this) . "->" . $actionName . "()");
        }

        $this->currentAction = $actionName;
        $this->parseActionParams();
    }

    /**
     * Get the name of the current action
     * @return string
     */
    public function getCurrentAction()
    {
    	return $this->currentAction;
    }

    /**
     * Render the current template
     * @return string
     */
    public function render()
    {
    	sp::app()->addLogMessage("Rendering template '" . $this->templateName . "'");

        // If there is a form bound to this page, check if it's valid or return the errors
        if($this->formModel != null && !$this->isFormValid())
        {
            foreach($this->formModel->getFields() as $field)
            {
                $fieldName = $field->name;

                if($field->formError != "")
                {
                    $formErrors[$fieldName] = "<span class=\"formError\">" . $field->formError . "</span>";
                }
                else
                    $formErrors[$fieldName] = "";
            }
        }
        unset($fieldName);


        // Alow plugins to inject their template variables
        foreach(sp::app()->getPlugins() as $plugin)
        {
        	foreach($plugin->getTemplateVariables() as $pageVarName=>$pageVarVal)
        	{
        		$$pageVarName = $pageVarVal;
        	}
        }

        // Set the page data as a regular variable for easy access
        foreach($this->data as $pageVarName=>$pageVarVal)
        {
            $$pageVarName = $pageVarVal;
        }

        unset($pageVarName);
		unset($pageVarVal);

        // Output buffer the output of the template
        ob_start();
        include $this->dir . "/templates/" . $this->templateName . ".php";
        $body_html = ob_get_contents();
        ob_end_clean();

        return $body_html;
    }

    public function errorIf($condition, $errorMessage = "")
    {
    	if($condition)
    	{
    		sp::app()->redirectToError($errorMessage);
    		throw new SerenityStopException();
    	}
    }

    public function error($errorMessage = "")
    {
		sp::app()->redirectToError($errorMessage);
    	throw new SerenityStopException();
    }

    /**
     * Set page name
     * @param string $name
     */
    public function setPageName($name)
    {
    	$this->pageName = $name;
    }

    /**
     * Get page name
     * @param string $name
     */
    public function getPageName()
    {
    	return $this->pageName;
    }

    /**
     * Set the base directory of the page
     * @param string $dir
     */
    public function setDir($dir)
    {
    	$this->dir = $dir;
    }

    /**
     * If the page fails parameter validation, redirect to this URL
     * @param string $url
     */
    public function setErrorUrl($page, $action)
    {
    	$this->errorPage = $page;
    	$this->errorAction = $action;
    }

    /**
     * Get the error Page
     * @return string
     */
    public function getErrorPage()
    {
    	return $this->errorPage;
    }

    /**
     * Get the error Action
     * @return string
     */
    public function getErrorAction()
    {
    	return $this->errorAction;
    }

    /**
     * Redirect to another page/action without causing a browser refresh
     * Will cause any existing action to exit out
     *
     * Example
     *
     * $this->redirect("home", "index");
     *
     * will change the current page to home and use the index action
     * @param string $pageName
     * @param string $action
     * @param array $params
     * @throws SerenityException
     */
    public function redirect($pageName, $action = "", $params = array())
    {
    	sp::app()->redirect($pageName, $action, $params);
    }

    public function getName()
    {
        return $this->pageName;
    }

    public function json($key, $data)
    {
        $this->json[$key] = $data;
    }
    
    public function jsonCount()
    {
        return count($this->json);
    }

    public function getJSON()
    {
        if(sp::app()->isDebugMode())
        {
            $json = $this->json;
            $json['serenity_queries'] = sp::db()->queryLog;
        }
        else
            $json = $this->json;

        return $json;
    }
}
?>
