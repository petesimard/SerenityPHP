<?php
namespace Serenity;

/**
 * Field Class of a model. Relates a database field to the model field.
 * @author Pete
 *
 */
class SerenityField
{
    public $name = '';
    public $index = '';
    public $type = '';
    public $length = 0;
    public $validator = null;
    public $model = null;
    public $paramDefinition = null;
    public $formError = "";
    public $isDirty = false;
    public $foreignModel = '';
    public $foreignKey = '';
    public $joinModel = '';
    public $enableCache = false;
    public $foreignRelationship = '';
    public $associatedModels = null;
    public $localKey = '';
    public $timestampFormat = 'M jS g:i a';
    public $foreignOrder = '';
    public $unsigned = false;
    public $autoSerialize = false;

    private $value = null;
    private static $modelCache = array();
    
    /**
     * Returns the fiendly name of the field if set in the validator. If none returns the raw database field name.
     * @return string
     */
    public function getFriendlyName()
    {
        $name = $this->name;
        if(!is_null($this->paramDefinition) && strlen($this->paramDefinition->friendlyName) > 0)
            $name = $this->paramDefinition->friendlyName;

        return $name;
    }

    /**
     * Returns weather the field has any contents
     * @return boolean
     */
    public function isEmpty()
    {
        if(is_array($this->value))
        {
            if($this->autoSerialize && count($this->value) > 0)
                return false;

            return true;
        }

        if(strlen($this->value) == 0)
            return true;

        return false;
    }

    /**
     * Returns a serialzed version of the field if it is an array
     * @return string
     */
    public function getSerialized()
    {
        if(!is_array($this->value))
            return '';

        $values = array();
        foreach($this->value as $valueKey => $value)
        {
            if(is_object($value))
                $values[$valueKey] = $value->getPrimaryKeyValue();
            else
                $values[$valueKey] = $value;
        }

        return serialize($values);
    }

    /**
     * Returns true if the primary key of the model
     * @return boolean
     */
    public function isPrimaryKey()
    {
        return ($this->index == "primary" ? true : false);
    }

    /**
     * Set the value of the field. If the value is differnt, sets the dirty flag
     * so it will be updated in the next save() call
     * @param unknown_type $value
     */
    public function setValue($value)
    {
        $origValue = $this->value;
        $this->value = $value;

        if($origValue !== $value)
        {
            $this->isDirty = true;
        }
    }

    /**
     * Gets the value of the field
     * @return Ambiguous
     */
    public function getValue()
    {
  
        if($this->isRelationalField())
        {
            $assoc = $this->getAssociatedModels();
            return $assoc;
        }

        /*
        if($this->isMagicField())
            return date($this->timestampFormat, $this->value);
        */

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

    /**
     * Returns true if the field will be saved to the database
     * on a save() call
     * @return boolean
     */
    public function isDatabaseField()
    {
        if($this->type == "form")
            return false;
        else
            return true;
    }

    /**
     * Returns true if the field will automaticly have the timestamp set
     * @return boolean
     */
    public function isMagicField()
    {
        if($this->name == 'updatedOn' || $this->name == 'createdOn')
            return true;
        else
            return false;
    }

    public function __toString()
    {
        if($this->foreignRelationship == 'hasOne')
        {
            $assoc =  $this->getAssociatedModels();
            return $assoc->__toString();
        }

        if($this->foreignRelationship == 'hasMany' || $this->foreignRelationship == 'serialized')
        {
            return 'Array';
        }

        $value = $this->getValue();

        if($value == null)
            $value = "";

        return $value;
    }

    /**
     * Returns true if the field is acutally a pointer to another models field(s)
     * @return boolean
     */
    public function isRelationalField()
    {
        if($this->foreignRelationship == 'hasOne' || $this->foreignRelationship == 'hasMany' || $this->foreignRelationship == 'serialized')
            return true;

        return false;
    }

    /**
     * Returns the associated models
     * @throws SerenityException
     * @return mixed
     */
    private function getAssociatedModels()
    {
        if(!is_null($this->associatedModels))
            return $this->associatedModels;

        $foreignModel = sp::app()->getModel($this->foreignModel);
        if($foreignModel == null)
            throw new SerenityException("Invalid associated model '" . $this->foreignModel . "'");

        if($this->foreignRelationship == 'serialized')
            return $this->getSerializedModels();

        $localKey = $this->localKey;
        if($localKey == "")
        {
            // No local key specified
            if(!$this->isDatabaseField())
            {
                $localKeyValue = $this->value;
                if(!$localKeyValue)
                {
                    return null;
                }
            }
            else
            {
                $localKeyValue = $this->model->getPrimaryKeyValue();
            }

        }
        else
            $localKeyValue = $this->model->getRawField($localKey)->value;

        $foreignKey = $this->foreignKey;
        if($foreignKey == "")
            $foreignKey = $foreignModel->getPrimaryKey();
            
        $this->associatedModels = $foreignModel->query()->addWhere($foreignKey . "='" . $localKeyValue . "'");

        if($this->foreignOrder != "")
            $this->associatedModels->orderBy($this->foreignOrder);



        if($this->foreignRelationship == 'hasOne')
        {
            if($this->enableCache)
            {
                $cachedModel = $this->getCachedModel($localKeyValue);
                
                if($cachedModel != null)
                {
                    $this->associatedModels = $cachedModel;
                    return $cachedModel;
                }
            }
                        
            $this->associatedModels = $this->associatedModels->fetchOne();
            
            if($this->enableCache)
            {
                $this->storeCachedModel($localKeyValue, $this->associatedModels);
            }            
        }
        else
            $this->associatedModels = $this->associatedModels->fetchAll();

        return $this->associatedModels;
    }

    /**
     * Returns the associated models based on the serialized values
     * @return mixed
     */
    private function getSerializedModels()
    {
        $retModels = array();

        if(count($this->value) == 0)
            return $retModels;

        $foreignModel = sp::app()->getModel($this->foreignModel);

        $foreignKey = $this->foreignKey;
        if($foreignKey == "")
            $foreignKey = $foreignModel->getPrimaryKey();

        foreach($this->value as $key)
        {
            $model = $foreignModel->query()->addWhere($foreignKey . "='" . mysql_escape_string($key) . "'")->fetchOne();
            if($model)
                $retModels[$model->getPrimaryKeyValue()] = $model;
        }

        $this->associatedModels = $retModels;
        return $this->associatedModels;
    }
    
    /**
    * Get the cached model if available
    * Returns null if not cached yet
    */
    private function getCachedModel($key)
    {
        if(isset(SerenityField::$modelCache[$this->model->tableName][$key]))
            return SerenityField::$modelCache[$this->model->tableName][$key];

        return null;
    }
    
    /**
    * Store the model in the cache for later use
    */
    private function storeCachedModel($key, $model)
    {
        SerenityField::$modelCache[$this->model->tableName][$key] = $model;
    }

    /**
     * Returns the HTML to display the field as hidden
     * @return string
     */
    public function getHiddenFormField()
    {
        $formFieldName = $this->model->tableName . "_" . $this->name;

        $html = "<input type=\"hidden\" name=\"$formFieldName\" value=\"" . $this->getRawValue() . "\">";

        return $html;
    }

    /**
     * Returns the HTML form element corosponding to the field type (textbox, dropdown, texarea, etc)
     * @param string $fieldName
     * @throws SerenityException
     * @return string
     */
    public function getFormField()
    {
        $formFieldName = $this->model->tableName . "_" . $this->name;

        if($this->foreignRelationship == "hasOne")
        {
            $html = "<select name=\"$formFieldName\">\n";
            $foreignModel = sp::app()->getModel($this->foreignModel);
            if($foreignModel == null)
                throw new SerenityException("Invalid foreign model '" . $this->foreignModel . "'");

            foreach($foreignModel->query()->fetchAll() as $model)
            {
                $html .= "<option value=\"" . $model->getPrimaryKeyValue() . "\"";

                if($this->getRawValue() == $model->getPrimaryKeyValue())
                    $html .= " selected=\"selected\"";

                $html .= ">" . htmlentities($model) . "</option>\n";
            }
            $html .= "</select>\n";
        }
        else if(isset($this->isPassword) && $this->isPassword)
        {
            $html = "<input class=\"textBox\" type=\"password\" name=\"$formFieldName\" value=\"" . htmlentities($this->getValue()) . "\">\n";
        }
        else if($this->type == "text" || $this->type == "tinytext" || $this->type == "bigtext"  || $this->type == "mediumtext")
        {
            $html = "<textarea name=\"" .  $formFieldName . "\">". htmlentities($this->getValue()) . "</textarea>\n";
        }
        else
        {
            $html = "<input class=\"textBox\" type=\"text\" name=\"$formFieldName\" value=\"" . htmlentities($this->getValue()) . "\">\n";
        }

        return $html;
    }

}

/**
 * Main model class. Models can be generated from SQL using the
 * built in Serenity backend
 * @author Pete
 *
 */
abstract class SerenityModel implements \arrayaccess
{
    static public $paramDefinitions = array();
    private $fields;
    static public $dir = "";
    public $modelName = "";
    public $tableName = "";
    private $primaryKey = "";

    public function __construct()
    {
        $this->fields = new \ArrayObject();
        $class = get_called_class();

        $className = explode('\\', get_called_class());
        $className = $className[count($className) - 1];

        $tableName = ucfirst(substr($className, 0, strlen($className) - 5));
        preg_match_all('/[A-Z][^A-Z]*/',$tableName, $tableParts);
        $newTableName = "";

        foreach($tableParts[0] as $tablePart)
        {
            if(strlen($newTableName) > 0)
                $newTableName .= '_';

            $newTableName .= lcfirst($tablePart);
        }

        $this->tableName = lcfirst($newTableName);

        $this->baseInit();
        $this->init();

        if(!array_key_exists($class, SerenityModel::$paramDefinitions))
        {
            SerenityModel::$paramDefinitions[$class] = new \ArrayObject();
            $this->validatorInit();
        }

        $this->postInit();
    }

    /**
     * Returns a new SernityModel that can later be saved to the database with save()
     * @return SernityModel
     */
    static public function getNew()
    {
        $className = get_called_class();
        $model = new $className;

        return $model;
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
     * Dump the contents of the model in human readable form
     */
    public function dump()
    {
        echo "<b>Model Name:</b> " . $this->modelName . "</b><br>";
        echo "<b>Table Name:</b> " . $this->tableName . "</b><br>";

        echo "<br>=<b>Fields</b>=<br>";
        foreach($this->getFields() as $field)
        {
            echo "<b>" . $field->name . ":</b> " . $field->getRawValue() . "</b> " . ($field->isDirty ? '*' : '') . "<br>";
        }
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


    /** (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {       
        $fields = $this->getFields();
        $ret = isset($fields[$offset]) ? $fields[$offset]->getValue() : null;

        if(is_string($ret))
            $ret = htmlentities($ret);

        return $ret;
    }
    
    function __get($offset)
    {
        $fields = $this->getFields();
        $ret = isset($fields[$offset]) ? $fields[$offset]->getValue() : null;

        if(is_string($ret))
            $ret = htmlentities($ret);

        return $ret;
    }    

    /** (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $fields = $this->getFields();

        if (is_null($offset))
        {
            throw new SerenityException("Cannot set field value without a field name");
        }
        else if(!isset($fields[$offset]))
        {
            //throw new SerenityException("Field '$offset' does not exist in model '" . $this->tableName . "'");
            $this->addField($offset);
        }

        $fields[$offset]->setValue($value);
    }

    /** (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        $fields = $this->getFields();

        return isset($fields[$offset]);
    }

    /** (non-PHPdoc)
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

        if(!isset($fields[$name]))
        {
        	throw new SerenityException("Undefined field '$name' in class " . get_class($this));
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


    public function undirtyFields()
    {
        foreach($this->getFields() as $field)
        {
            $field->isDirty = false;
        }
    }

    public function onLoadedFromDatabase()
    {
        $this->undirtyFields();

        foreach($this->getFields() as $field)
        {
            if($field->autoSerialize && strlen($field->getRawValue()) > 0)
            {
                $field->setValue(unserialize($field->getRawValue()));
                $field->isDirty = false;
            }
        }
    }

    /**
     * Get a SerenityField field
     * @param string $fieldName
     * @return SerenityField
     */
    public function getRawField($fieldName)
    {
        $fields = $this->getFields();
        if(!isset($fields[$fieldName]))
            return null;

        $field = $fields[$fieldName];

        return $field;
    }

    public function getFieldValue($fieldName)
    {
        $fields = $this->getFields();

        $field = $fields[$fieldName];
        if($field)
        {
            return $field->getValue();
        }

        return null;
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
    protected function validatorInit()
    {
        $paramDefinitions = $this->getParamDefinitions();

        foreach($this->getFields() as $field)
        {
            $validator = $field->validator;
            if($validator != null)
            {
                if(!isset($validator['type']) || $validator['type'] == "")
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

                if(isset($validator['name']) && $validator['name'] != "")
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
            {
                $this->primaryKey = $field->name;
            }

            if(isset($paramDefinitions[$field->name]))
                $field->paramDefinition = $paramDefinitions[$field->name];
               else
                   $field->paramDefinition = null;
        }
    }

    /**
     * Save the model to the database. Will insert a new row if the primary
     * key is empty. Will update if the primary key exists.
     * @throws SerenityException
     * @return Number ID of inseted row or number of rows updated
     */
    public function save()
    {
        $primaryKey = $this->getPrimaryKey();
        if($primaryKey == "")
        {
            throw new SerenityException("Unable to save form: Model " . get_class($this) . " is missing primary key.");
        }

        $primaryKeyField = $this->getRawField($primaryKey);
        if($primaryKeyField->getValue() == null)
        {
            $newId = $this->insertNew();
            $this->undirtyFields();
            $this->onInserted();
            return $newId;
        }
        else
        {
            $rowsUpdated = $this->updateRow();
            $this->undirtyFields();
            return $rowsUpdated;
        }
    }

    /**
     *  Delete the model entry from the database
     */
    public function remove()
    {
        $primaryKey = $this->getPrimaryKey();
        $primaryKeyField = $this->getRawField($primaryKey);
        if($primaryKeyField->getValue() != null)
        {
            $query = "DELETE FROM " . $this->tableName . " WHERE "  . $primaryKey . "= ?";
            $stmt = sp::db()->query($query, array($this->getPrimaryKeyValue()));
            return $stmt->rowCount();
        }

        return 0;
    }

    /**
     * Called from save() to update an existing
     */
    private function updateRow()
    {
        $hasDirtyField = false;
        $params = array();
        $primaryKey = $this->getPrimaryKey();

        $query = "UPDATE " . $this->tableName . " SET ";
        foreach($this->getFields() as $field)
        {
            if($field->name != $primaryKey && $field->isDatabaseField() && $field->isDirty || $field->name == "updatedOn")
            {
                $value = $field->getRawValue();

                if($field->autoSerialize)
                    $value = $field->getSerialized();

                if($field->name == "updatedOn" || $value === "UNIX_TIMESTAMP()")
                    $query .= $field->name . "=UNIX_TIMESTAMP(), ";
                else
                {
                    $query .= $field->name . "= ?, ";
                    $params[] = $value;
                }

                $hasDirtyField = true;
            }
        }

        // There was nothing to update
        if(!$hasDirtyField)
        {
            return;
        }

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 2);


        $query .= ' WHERE ' . $primaryKey . '= ?';
        $params[] = $this->getPrimaryKeyValue();

        $stmt = sp::db()->query($query, $params);

        return $stmt->rowCount();
    }
    
    public function onInserted()
    {
    }

    /**
     * Called from save() to insert a new row
     */
    private function insertNew()
    {
        $params = array();
        $primaryKey = $this->getPrimaryKey();

        $query = "INSERT INTO " . $this->tableName . " (";
        foreach($this->getFields() as $field)
        {
            // Set default values
            if(isset($field->defaultValue) && strlen($field->getRawValue()) == 0)
                $field->setValue($field->defaultValue);

                
            if($field->name != $primaryKey && $field->isDatabaseField() && !$field->isEmpty() || $field->name == "createdOn" || $field->name == "updatedOn")
            {
                if($field->autoSerialize)
                    $value = $field->getSerialized();
                else                
                    $value = $field->getRawValue();

                if($field->name == "createdOn" || $field->name == "updatedOn"  || strcasecmp($value, "UNIX_TIMESTAMP()") == 0)
                {
                    $values[] = "UNIX_TIMESTAMP()";
                }
                else
                {

                    if(is_array($value))
                        throw new SerenityException('Attempting to save an array as a value on field \'' . $field->name . '\' on model \'' . $this->modelName . '\'. Try setting the field to autoSerialize = true');

                    $values[] = "?";
                    $params[] = $value;
                }

                $query .= "" . $field->name . ", ";

                $hasDirtyField = true;
            }
        }

        // Strip last comma
        $query = substr($query, 0, strlen($query) - 2);

        $query .= ") VALUES(";

        foreach($values as $value)
        {
            $query .= $value . ", ";
        }


        // Strip last comma
        $query = substr($query, 0, strlen($query) - 2);

        $query .= ")";

        // Insert the row
        $stmt = sp::db()->query($query, $params);

        $fields = $this->getFields();
        $primaryKeyField = $fields[$this->getPrimaryKey()];

        // Set the new primary key
        $newId = sp::db()->lastInsertId();
        if($newId)
        {
            $primaryKeyField->setValue($newId);
        }

        return $newId;
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
        return $this->getRawField($this->primaryKey)->getValue();
    }

    /**
     * Return a query object. Call fetch() or fetchOne() to execute your query
     * @param string $primaryKeyValue
     * @return SerenityQuery
     */
    static function query($primaryKeyValue = null)
    {
        $query = new SerenityQuery();

        $className = explode('\\', get_called_class());
        $className = $className[count($className) - 1];

        $fqClassName = __NAMESPACE__ . '\\' . $className;

        $modelName = substr($className, 0, strlen($className) - 5);
        $modelInfo = sp::app()->getModel($modelName);

        $tableName = $modelInfo->tableName;

        $query->addFrom($tableName);
        $query->setModelClass($fqClassName);

        if(!is_null($primaryKeyValue))
        {
            if(is_numeric($primaryKeyValue))
            {
                $query->addWhere($tableName . '.' . $modelInfo->getPrimaryKey() . "= ? ", $primaryKeyValue);
            }
            else
            {
                throw new SerenityException("Invalid primary key value: '" . $primaryKeyValue . "'. Consider using addWhere()");
            }
        }

        return $query;
    }

    static function customQuery($sql, $params = array())
    {
        $query = new SerenityQuery();

        $className = explode('\\', get_called_class());
        $className = $className[count($className) - 1];

        $fqClassName = __NAMESPACE__ . '\\' . $className;

        $modelName = substr($className, 0, strlen($className) - 5);
        $modelInfo = sp::app()->getModel($modelName);

        $tableName = $modelInfo->tableName;

        $query->setModelClass($fqClassName);

        $query->sql($sql, $params);

        return $query;
    }

    public function __toString()
    {
        return $this->getPrimaryKeyValue();
    }

    static public function getRandomHash($length, $checkFieldName = null)
    {
        $possible = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $maxPossibleChar = strlen($possible)-1;

        $exists = true;
        while($exists == true)
        {
            $string = '';

            for($i=0;$i < $length; $i++) {
                $char = $possible[mt_rand(0, $maxPossibleChar)];
                $string .= $char;
            }

            $exists = false;
            if($checkFieldName)
            {
                if(static::query()->addWhere($checkFieldName . "='" . $string . "'")->fetchOne())
                    $exists = true;
            }
        }

        return $string;
    }

    public function getName()
    {
        return $this->modelName;
    }

}
?>
