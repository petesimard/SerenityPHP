<?php
namespace Serenity;

/**
 * Field Class of a model. Relates a database field to the model field.
 * @author Pete
 *
 */
class SerenityField
{
    public $name = "";
    public $index = "";
    public $type = "";
    public $length = 0;
    public $validator = null;
    public $model = null;
    public $paramDefinition = null;
    private $value = null;
    public $formError = "";
    public $isDirty = false;
    public $foreignModel = "";
    public $foreignKey = "";
    public $foreignRelationship = "";
    public $associatedModels = null;
	public $localKey = "";
    
    /**
     * Returns the fiendly name of the field if set in the validator. If none returns the raw database field name.
     * @return string
     */
    public function getFriendlyName()
    {
        $name = $this->name;
        if($this->paramDefinition != null && $this->paramDefinition->friendlyName != "")
            $name = $this->paramDefinition->friendlyName;

        return $name;
    }
    
    public function setValue($value)
    {
    	$origValue = $this->value; 
    	$this->value = $value;
    	
    	if($origValue != $value)
    		$this->isDirty = true;
    }
    
    public function getValue()
    {
    	if($this->foreignRelationship == "hasOne" || $this->foreignRelationship == "hasMany")
    	{
    		$assoc =  $this->getAssociatedModels();
    		return $assoc;
    	}
    	
    	return $this->value;
    }   
    
    /**
     * Get the raw value of a field, even if it is a relational field
     * @return mixed
     */
    public function getRawValue()
    {
    	return $this->value;
    }

    public function isDatabaseField()
    {
    	if($this->type == "form")
    		return false;
    	else
    		return true;
    }
    
    public function __toString()
    {
    	if($this->foreignRelationship == "hasOne")
    	{
    		$assoc =  $this->getAssociatedModels();
    		return $assoc->__toString();
    	}
    	
    	if($this->foreignRelationship == "hasMany")
    	{
    		return "Array";
    	}
    	
    	return $this->getValue();
    }
    
    private function getAssociatedModels()
    {
    	$foreignModel = sp::app()->getModel($this->foreignModel);
    	if($foreignModel == null)
    		throw new SerenityException("Invalid associated model '" . $this->foreignModel . "'");
    	
    	$localKey = $this->localKey;
    	if($localKey == "")
    	{
    		// No local key specified
    		if($this->isDatabaseField())
    		{
    			$localKeyValue = $this->value;
    			if(!$localKeyValue)
    				return null;
    		}
    		else
    		{
    			$localKeyValue = $this->model->getPrimaryKeyValue();	
    		}
    		
    	}
    	else 
    		$localKeyValue = $this->model->getField($localKey)->value;
    		
		$foreignKey = $this->foreignKey;
		if($foreignKey == "")
			$foreignKey = $foreignModel->getPrimaryKey();
    	
    	$this->associatedModels = $foreignModel->query()->addWhere($foreignKey . "='" . $localKeyValue . "'")->fetch();
    	
    	if($this->foreignRelationship == "hasOne")
    	{
    		if(count($this->associatedModels) > 0)
    		{
    			$this->associatedModels = $this->associatedModels[0];
    		}
    	}
    	
    	return $this->associatedModels;
    }
}

/**
 * Main model class.
 * @author Pete
 *
 */
abstract class SerenityModel implements \arrayaccess
{
    static private $paramDefinitions = array();
    private $fields;
    public $dir = "";
    public $tableName = "";
    private $primaryKey = "";

    public function __construct()
    {
    	$this->fields = new \ArrayObject();
        $class = get_called_class();

    	$className = explode('\\', get_called_class());
        $className = $className[count($className) - 1];
        
        $tableName = lcfirst(substr($className, 0, strlen($className) - 5));
        $this->tableName = $tableName;
        
		$this->init();
        
        if (!array_key_exists($class, self::$paramDefinitions))    	
        {
        	self::$paramDefinitions[$class] = new \ArrayObject();
        	$this->validatorInit();
        }    	

        $this->postInit();
    }
    
    /**
     * Returns an ArrayObject containing an associative array of fields
     * @return ArrayObject
     */
    public function getFields()
    {
    	return $this->fields;
    }
        
    /**
     * Returns the static validators for the model fields
     * @return ArrayObject:
     */
    public function getParamDefinitions()
    {
    	$class = get_called_class();
    	return self::$paramDefinitions[$class];
    }
    

    /* (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
    	$fields = $this->getFields();
        return isset($fields[$offset]) ? $fields[$offset]->getValue() : null;
    }

    /* (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
    	$fields = $this->getFields();
    	
        if (is_null($offset)) {
            throw new SerenityException("Cannot set field value without a field name");
        } else {
            $fields[$offset]->setValue($value);
        }
    }
    /* (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
    	$fields = $this->getFields();
    	
        return isset($fields[$offset]);
    }
    
    /* (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
    	$fields = $this->getFields();
    	
        unset($fields[$offset]);
    }
    
    /**
     * Allows PDO to hydrate the class
     */
    function __set($name, $value)
    {
    	$fields = $this->getFields();
    	
    	if($fields[$name] == null)
    	{
    		return;
    		//throw new SerenityException("Undefined field '$name' in class " . get_class($this));
    	}	
	    	
        $fields[$name]->setValue($value);
    }    

    /**
     * Returns the HTML <form> start tag and associated hidden elements
     * @return string
     */
    public function getFormStart($page = "", $actionName = "")
    {
        $currentPage = sp::app()->getCurrentPage();
        
        if($page == "")
        	$page = $currentPage->getPageName(); 

        if($actionName == "")
        	$actionName = $currentPage->getCurrentAction(); 

        $action = getPageUrl($page,  $actionName);

        $html = "<form method=\"post\" action=\"" . $action . "\">\n";
        $html .= "<input type=\"hidden\" name=\"model_name\" value=\"" . $this->tableName . "\">\n";
        
        if($this->getPrimaryKeyValue() != "")
			$html .= "<input type=\"hidden\" name=\"" . $this->tableName . "_" . $this->getPrimaryKey() . "\" value=\"" . $this->getPrimaryKeyValue() ."\">\n";
			
        return $html;
    }


    /**
     * Returns the HTML form element corosponding to the field type (textbox, dropdown, texarea, etc)
     * @param string $fieldName
     * @throws SerenityException
     * @return string
     */
    public function getFormField($fieldName)
    {
    	$fields = $this->getFields();
    	
        $field = $fields[$fieldName];
        if($field == null)
        {
            throw new SerenityException("Invalid form field in class " . get_class($this) . ": '" . $fieldName . "'");
        }
        
        $formFieldName = $this->tableName . "_" . $fieldName;

        if($field->foreignRelationship == "hasOne")
        {
        	$html = "<select name=\"$formFieldName\">\n";
        	$foreignModel = sp::app()->getModel($field->foreignModel);
        	if($foreignModel == null)
        		throw new SerenityException("Invalid foreign model '" . $field->foreignModel . "'");
        		
        	foreach($foreignModel->query()->fetch() as $model)
        	{
        		$html .= "<option value=\"" . $model->getPrimaryKeyValue() . "\"";
        		
        		if($field->getRawValue() == $model->getPrimaryKeyValue())
        			$html .= " selected=\"selected\"";
        		
        		$html .= ">" . htmlentities($model) . "</option>\n";
        	}
        	$html .= "</select>\n";
        }
        else if($field->isPassword)
        {
            $html = "<input type=\"password\" name=\"$formFieldName\" value=\"" . htmlentities($field->getValue()) . "\">\n";
        }
        else if($field->type == "text" || $field->type == "tinytext" || $field->type == "bigtext"  || $field->type == "mediumtext")
        {
            $html = "<textarea name=\"" .  $this->tableName . "_" . $fieldName . "\">". htmlentities($field->getValue()) . "</textarea>\n";
        }
        else
        {
            $html = "<input type=\"text\" name=\"$formFieldName\" value=\"" . htmlentities($field->getValue()) . "\">\n";
        }

        return $html;
    }    
    
    public function undirtyFields()
    {
    	foreach($this->getFields() as $field)
        {
        	$field->isDirty = false;
        }
    }
    
    /**
     * Get a SerenityField field
     * @param string $fieldName
     * @return SerenityField
     */
    public function getField($fieldName)
    {
    	$fields = $this->getFields();
    	
        $field = $fields[$fieldName];

        return $field;
    }

    /**
     * Set the value of the model field
     * @param string $fieldName
     * @param string $value
     */
    public function setField($fieldName, $value)
    {
    	$fields = $this->getFields();
    	
        $fields[$fieldName]->setValue($value);
    }

    /**
     * Add a field to the model. Call during model->init()
     * @param string $name
     * @return SerenityField
     */
    protected function addField($name)
    {
    	$fields = $this->getFields();
        $field = new SerenityField();

        $field->name = $name;
        $field->model = $this;
        $fields[$name] = $field;
        
        return $field;
    }
    
    /**
     * Convert validator string to ParamDefinition object. Save in a static array
     */
    private function validatorInit()
    {
    	$paramDefinitions = $this->getParamDefinitions();
    	
        foreach($this->getFields() as $field)
        {
            $validator = $field->validator;
            if($validator != null)
            {
                if($validator['type'] == "")
                {
                    // Validator type isn't set, so we will set it to the same as the field type
                    if($field->type == "varchar" || $field->type == "char" || $field->type == "tinytext" || $field->type == "mediumtext" || $field->type == "text" || $field->type == "bigtext")
                        $validator['type'] = "string";
                    else if($field->type == "tinyint" || $field->type == "int" || $field->type == "mediumint" || $field->type == "bigint")
                        $validator['type'] = "int";
                    else if($field->type == "float")
                        $validator['type'] = "number";
                }

                $paramDefinitions[$field->name] = new ParamDefinition($validator, $this, $field);
                if($validator['name'] != "")
                    $paramDefinitions[$field->name]->name = $validator['name'];
                else
                    $paramDefinitions[$field->name]->name = $field->name;
            }
        }
    }

	/**
     * Assign primary key and parameter definitions
     */
    public function postInit()
    {
    	$paramDefinitions = $this->getParamDefinitions();
    	
        foreach($this->getFields() as $field)
        {
            if($field->index == "primary")
                $this->primaryKey = $field->name;
            
			$field->paramDefinition = $paramDefinitions[$field->name];
        }
    }

    /**
     * Save the model to the database. Will insert a new row if the primary
     * key is empty. Will update if the primary key exists.
     * @throws SerenityException
     */
    public function save()
    {
        $primaryKey = $this->getPrimaryKey();
        if($primaryKey == "")
        {
            throw new SerenityException("Unable to save form: Model " . get_class($this) . " is missing primary key.");
        }

        $primaryKeyField = $this->getField($primaryKey);
        if($primaryKeyField->getValue() == null)
        {
            $this->insertNew();
        }
        else
        {
            $this->updateRow();
        }
    }
    
    /**
     * Called from save() to update an existing
     */
    private function updateRow()
    {
		$primaryKey = $this->getPrimaryKey();

        $query = "UPDATE " . $this->tableName . " SET ";
        foreach($this->getFields() as $field)
        {
            if($field->name != $primaryKey && $field->isDatabaseField() && $field->isDirty)
            {
                $query .= $field->name . "='" . $field->getRawValue() . "', ";
                $hasDirtyField = true;
            }
        }       
        
        // There was nothing to update
        if(!$hasDirtyField)
        	return;        

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 2);

       
        $query .= " WHERE " . $primaryKey . "='" . $this->getPrimaryKeyValue() . "'";

        $stmt = sp::db()->query($query);
    }

    /**
     * Called from save() to insert a new row
     */
    private function insertNew()
    {
        $primaryKey = $this->getPrimaryKey();

        $query = "INSERT INTO " . $this->tableName . " (";
        foreach($this->getFields() as $field)
        {
        	// Set default values
        	if($field->defaultValue != null && $field->getValue() == "")
        		$field->setValue($field->defaultValue);
        	
            if($field->name != $primaryKey && $field->isDatabaseField())
            {
                $values[] = $field->getRawValue();
                $query .= "" . $field->name . ", ";
                
                $hasDirtyField = true;
            }
        }

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 2);

        $query .= ") VALUES(";

        foreach($values as $value)
        {
            $query .= "'" . mysql_escape_string($value) . "', ";
        }

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 2);

        $query .= ")";

        $stmt = sp::db()->query($query);
		
        $fields = $this->getFields();
        $primaryKeyField = $fields[$this->getPrimaryKey()];

        // Set the new primary key
        $newId = sp::db()->lastInsertId();
        if($newId)
        {
        	$primaryKeyField->setValue($newId);
        }
    }

    /**
     * Returns the name of the primary key of the model
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    
    /**
     * Returns the value of the model's primary key
     * @return string
     */
    public function getPrimaryKeyValue()
    {
    	return $this->getField($this->primaryKey)->getValue();
    }
    
    /**
     * Return a query object. Call fetch() or fetchOne() to execute your query
     * @param string $where
     * @return SerenityQuery
     */
    static function query($where = "")
    {
    	$query = new SerenityQuery();

    	$className = explode('\\', get_called_class());
        $className = $className[count($className) - 1];

        $fqClassName = __NAMESPACE__ . '\\' . $className;

        $modelName = substr($className, 0, strlen($className) - 5);
        $modelInfo = sp::app()->getModel($modelName);

        $tableName = $modelInfo->tableName;

    	$query->from = $tableName;
    	$query->modelClass = $fqClassName;

        if($where != "")
        {
	        if(is_numeric($where))
	        {
	            $query->addWhere($modelInfo->getPrimaryKey() . "='" . mysql_escape_string($where) . "'");
	        }
	        else
	        	throw new SerenityException("Invalid query parameter: '" . $where . "'. Consider using addWhere()");
        }
        
        return $query;
    }
    
    public function __toString()
    {
    	return $this->getPrimaryKeyValue();
    }
        
}
?>
