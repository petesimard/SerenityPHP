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
    public $data = array();
    public $templateName = "";
    public $pageName = "";
    public $currentAction = "";
    public $dir;
    public $paramDefinitions = array();
    public $params = array();
    private $formModel = null;
    private $isFormValid = false;
    private $noticeMessage = "";
    private $noticeType = "";

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
    
    public function __get($name)
    {
        return $this->data[$name];
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
        foreach($this->params as $paramName => $paramValue)
        {
            if($paramName == "model_name")
            {
                $model = sp::app()->getModel($paramValue);
                if($model == null)
                    throw new SerenityException("Invalid model passed: " . $paramName);

                $className = get_class($model);
                $formModel = clone $model;
                break;
            }
        }

        if($formModel == null)
            return;
            
        sp::app()->addLogMessage("Bound form to page '" . get_class($formModel) . "'");
        
        // Check for a primary key to see if it's an update or an insert
        $primaryKey = $formModel->getPrimaryKey();
        $primaryKeyParam = $this->params[$formModel->tableName . "_" . $primaryKey];

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
                $modelField = $formModel->getField($fieldName);
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
            $errorMessage = sp::validator()->validate($field->getValue(), $field->paramDefinition, $this->formModel);
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
    			if($field->value != "" && $field->type != "form")
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
    	$ret = array("message" => $_SESSION['noticeMessage'], "type" => $_SESSION['noticeType']);
    	$this->setNotice("", "");

    	return $ret;        
    }

    /**
     * Validate GET and POST paremeters passed in the request
     * Returns an empty string if no errors
     * @return string
     */
    public function validateParams()
    {
        foreach($this->paramDefinitions as $paramName => $paramDefinition)
        {
            $paramValue = $this->params[$paramName];

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
    public function getParam($name)
    {
        return $this->params[$name];
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
    	$this->params = array();
    	    	
        $methods = get_class_methods($this);
        foreach ($methods as $method_name)
        {
        	if(substr($method_name, 0, strlen($this->currentAction)) == $this->currentAction)
        	{
	            if(substr($method_name, -15) == "_registerParams")
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
        $this->{$actionName}();
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
     * Render the current template
     * @return string
     */
    public function render()
    {
    	sp::app()->addLogMessage("Rendering template '" . $this->templateName . "'");
    	
        if($this->formModel != null && !$this->isFormValid())
        {
            foreach($this->formModel->getFields() as $field)
            {
                if($field->formError != "")
                {
                    $fieldName = $field->name;
                    $formErrors[$fieldName] = "<span class=\"formError\"> *" . $field->formError . "</span>";
                }
            }
        }

        
        foreach(sp::app()->getPlugins() as $plugin)
        {
        	foreach($plugin->getTemplateVariables() as $pageVarName=>$pageVarVal)
        	{
        		$$pageVarName = $pageVarVal;
        	}
        }

        foreach($this->data as $pageVarName=>$pageVarVal)
        {
            $$pageVarName = $pageVarVal;
        }
        
		unset($fieldName);        
        unset($pageVarName);        
		unset($pageVarVal);        
        
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
    
}
?>
