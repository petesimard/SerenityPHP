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
    public $paramDefinition = null;
    private $value = null;
    public $formError = "";
    private $isDirty = false;

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
    	$this->value = $value;
    	$this->isDirty = true;
    }
    
    public function getValue()
    {
    	return $this->__toString();
    }    
    
    public function __toString()
    {
    	return $this->value;
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
        
        $tableName = strtolower(substr($className, 0, strlen($className) - 5));
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
	    	throw new SerenityException("Undefined field '$name' in class " . get_class($this));
	    	
        $fields[$name]->setValue($value);
    }    

    /**
     * Returns the HTML <form> start tag and associated hidden elements
     * @return string
     */
    public function getFormStart()
    {
        $currentPage = sp::app()->getCurrentPage();

        $action = getPageUrl($currentPage->pageName,  $currentPage->currentAction);

        $html = "<form method=\"post\" action=\"" . $action . "\">";
        $html .= "<input type=\"hidden\" name=\"model_name\" value=\"" . $this->tableName . "\">";

        return $html;
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

        if($field->isPassword)
        {
            $html = "<input type=\"password\" name=\"" . $this->tableName . "_" . $fieldName . "\" value=\"" . $field->getValue() . "\">";
        }
        else if($field->type == "text" || $field->type == "tinytext" || $field->type == "bigtext"  || $field->type == "mediumtext")
        {
            $html = "<textarea name=\"" .  $this->tableName . "_" . $fieldName . "\">". $field->getValue() . "<textarea>";
        }
        else
        {
            $html = "<input type=\"text\" name=\"" . $this->tableName . "_" . $fieldName . "\" value=\"" . $field->getValue() . "\">";
        }

        return $html;
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

                $paramDefinitions[$field->name] = new ParamDefinition($validator, $this);
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
        	
            if($field->name != $primaryKey && $field->type != "form")
            {
                $values[] = $field->getValue();
                $query .= "" . $field->name . ",";
            }
        }

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 1);

        $query .= ") VALUES(";

        foreach($values as $value)
        {
            $query .= "'" . mysql_escape_string($value) . "',";
        }

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 1);

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
    private function getPrimaryKey()
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
	            $query->addWhere($modelInfo->getPrimaryKey() . "='" . $where . "'");
	        }
	        else if($where != "")
	            $query->addWhere($where);
        }
        
        return $query;
    }
}
?>
