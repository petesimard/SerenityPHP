<?php
namespace Serenity;

/**
 * Serenity Backend. Limits to local IPs
 * !! REMOVE FOR RELEASE !!
 * @author Pete
 *
 */
class BackendPage extends SerenityBackendPage
{
	public function index()
	{

	}

	/**
	 * Enter description here ...
	 */
	public function generateModels()
	{
		$report = "<b>Generating models</b><br><br>";

		$stmt = sp::db()->query("show tables");
		foreach ($stmt as $row)
		{
            $tableName = $row[0];
            $modelName = $tableName;

            while(($ucPos = strpos($modelName, "_")) !== false)
            {
                $modelName = substr($modelName, 0, $ucPos) . ucfirst(substr($modelName, ($ucPos+1), (strlen($modelName) - $ucPos - 1)));
            }

			$baseDir = sp::$baseDir . SerenityAppController::APP_DIRECTORY . "/" . SerenityAppController::MODEL_DIRECTORY;
			$fields = array();

			$fileString = "<?
namespace Serenity;\n
/**
/* Auto generated class. DO NOT EDIT.
/* All edits should be on the child class '" . ucfirst($modelName) ."Model'
 */
abstract class Base" . ucfirst($modelName) . "Model extends SerenityModel
{
    function baseInit()
    {";

			$columsStmt = sp::db()->query("SHOW COLUMNS FROM $tableName");
			foreach ($columsStmt as $column)
			{
				$fields[$column['Field']] = array('name' => $column['Field'], 'type' => $column['Type']);

				$sizeStart = strpos($column['Type'], '(');
				if($sizeStart !== false)
				{
					$fields[$column['Field']]['type'] = substr($column['Type'], 0, $sizeStart);
					$fields[$column['Field']]['size'] = substr($column['Type'], ($sizeStart + 1), -1);
				}

				if($column['Key'] == 'PRI')
					$fields[$column['Field']]['index'] = 'primary';
			}

			$indexStmt = sp::db()->query("SHOW INDEXES FROM $tableName");
			foreach ($indexStmt as $index)
			{
				if($index['Key_name'] == "PRIMARY")
					continue;

				$fields[$index['Column_name']]['index'] = 'index';
			}

			foreach($fields as $field)
			{
				$fileString .= "\n" . '		$field = $this->addField("' . $field['name'] . '");' . "\n";
        		$fileString .= '		$field->type = "' . $field['type'] . '";' . "\n";

				if(isset($field['size']))
					$fileString .= '		$field->size = ' . $field['size'] . ';' . "\n";

				if(isset($field['index']))
					$fileString .= '		$field->index = "' . $field['index'] . '";' . "\n";
			}


			$fileString .= "	}\n}";

			$file = $baseDir . "/base/Base" . ucfirst($modelName) . ".php";
			$fh = fopen($file, 'w');
			fwrite($fh, $fileString);
			fclose($fh);

			// Build the non-base model
			if(!file_exists($baseDir . "/" . ucfirst($modelName) . ".php"))
			{
				$fileString = "<?
namespace Serenity;\n
class " . ucfirst($modelName) . "Model extends Base" . ucfirst($modelName) . "Model
{
    function init()
    {
    }
}";
				$file = $baseDir . "/" . ucfirst($modelName) . ".php";
				$fh = fopen($file, 'w');
				fwrite($fh, $fileString);
				fclose($fh);
			}


			$report .= "Generated model '" . ucfirst($modelName) . "'<br>";

		}

		$this->setNotice('info', $report);

		sendTo(getPageUrl('backend'));
	}


	public function generateSQL()
	{
		$sql = "";
		foreach(sp::app()->getModels() as $model)
		{
			$indexs = array();
			$sql .= "CREATE TABLE " . $model->tableName . "(";
			foreach($model->getFields() as $field)
			{
				if($field->type != '' && $field->type != 'form')
				{
					$sql .= '`' . $field->name . "` " . strtoupper($field->type);
					if($field->size > 0)
						$sql .= "(" .$field->size . ")";

					if ($field->isPrimaryKey())
						$sql .= " AUTO_INCREMENT PRIMARY KEY";

					if ($field->index == "index")
						$indexs[] = $field->name;
					$sql .= ", ";
				}
			}

			foreach($indexs as $index)
			{
				$sql .= "INDEX ( `$index` ), ";
			}

			// Strip last comma
			$sql = substr($sql, 0, strlen($sql) - 2);

			$sql .= ");<br>";
		}

		$this->setNotice('info', "<b>MySQL statement:</b><br><br>" . $sql);
		sendTo(getPageUrl('backend'));
	}

	private function parseBaseFile($base, $pageName, $pageModel, $fieldList)
	{

		$base = str_replace('[fieldList]', ucfirst($fieldList), $base);
		$base = str_replace('[PageName]', ucfirst($pageName), $base);
		$base = str_replace('[ModelName]', ucfirst($pageModel->modelName), $base);
		$base = str_replace('[pageName]', $pageName, $base);
		$base = str_replace('[modelName]', lcfirst($pageModel->modelName), $base);
		$base = str_replace('[modelPrimaryKey]', $pageModel->getPrimaryKey(), $base);
		$base = str_replace('[phpStart]', '<?php', $base);
		$base = str_replace('[phpEnd]', '?>', $base);

		return $base;
	}

	public function newPage()
	{
		$this->setTemplate('index');

		$basePageDir = sp::$baseDir . SerenityAppController::APP_DIRECTORY . "/" . SerenityAppController::PAGE_DIRECTORY;

		$pageName = $this->getParam('pageName');
		if($this->getParam('pageModel') != "0")
		{
			$pageModel = sp::app()->getModel($this->getParam('pageModel'));

			if($pageModel == null)
			{
				$this->setNotice('error', 'Invalid model.');
				return;
			}
		}

		if(file_exists($newPageDir))
		{
			$this->setNotice('error', 'A page with that name already exists.');
			return;
		}

		$newPageDir = $basePageDir . "/" . $pageName;

		// Make the directories
		mkdir($newPageDir);
		mkdir($newPageDir . "/templates");
		mkdir($newPageDir . "/code");

		if($pageModel != null)
		{
			// Write the code file
			ob_start();
			include "base_code.php";
			$base = ob_get_contents();
			ob_clean();

			$base = $this->parseBaseFile($base, $pageName, $pageModel, "");

			$file = $newPageDir . "/code/code.php";
			$fh = fopen($file, 'w');
			fwrite($fh, $base);
			fclose($fh);


			// Write the index
			ob_start();
			include "base_index.php";
			$base = ob_get_contents();
			ob_clean();

			$base = $this->parseBaseFile($base, $pageName, $pageModel, "");

			$file = $newPageDir . "/templates/index.php";
			$fh = fopen($file, 'w');
			fwrite($fh, $base);
			fclose($fh);

			// Write show
			ob_start();
			include "base_show.php";
			$base = ob_get_contents();
			ob_clean();

			$fieldList = "";
			foreach($pageModel->getFields() as $field)
				$fieldList .= '<tr><td>' . $field->name . '</td><td><? echo $' . lcfirst($pageModel->modelName) . '[\'' . $field->name . '\']?></td></tr>';

			$base = $this->parseBaseFile($base, $pageName, $pageModel, $fieldList);

			$file = $newPageDir . "/templates/show.php";
			$fh = fopen($file, 'w');
			fwrite($fh, $base);
			fclose($fh);


			// Write edit
			ob_start();
			include "base_edit.php";
			$base = ob_get_contents();
			ob_clean();

			$fieldList = "";
			foreach($pageModel->getFields() as $field)
			{
				if($field->isDatabaseField() && $field->name != $pageModel->getPrimaryKey() && !$field->isMagicField())
				{
					$fieldList .= '<tr><td>' . ucfirst($field->name) . '<br> <?=$formErrors[\'' . $field->name . '\']?></td><td>';
					$fieldList .= '<? echo $' . lcfirst($pageModel->modelName) . '->getRawField(\'' . $field->name . '\')->getFormField(); ?>';
					$fieldList .= '</td></tr>';
				}
			}

			$base = $this->parseBaseFile($base, $pageName, $pageModel, $fieldList);

			$file = $newPageDir . "/templates/edit.php";
			$fh = fopen($file, 'w');
			fwrite($fh, $base);
			fclose($fh);

			// Write new
			ob_start();
			include "base_create.php";
			$base = ob_get_contents();
			ob_clean();

			$fieldList = "";
			foreach($pageModel->getFields() as $field)
			{
				if($field->isDatabaseField() && $field->name != $pageModel->getPrimaryKey() && !$field->isMagicField() && $field->foreignRelationship == "hasMany")
				{
					$fieldList .= '<tr><td>' . ucfirst($field->name) . '<br><?=$formErrors[\'' . $field->name . '\']?></td><td>';
					$fieldList .= '<? echo $' . lcfirst($pageModel->modelName) . '->getRawField(\'' . $field->name . '\')->getFormField(); ?>';
					$fieldList .= '</td></tr>';
				}
			}

			$base = $this->parseBaseFile($base, $pageName, $pageModel, $fieldList);

			$file = $newPageDir . "/templates/create.php";
			$fh = fopen($file, 'w');
			fwrite($fh, $base);
			fclose($fh);
		}
		else
		{
			// No model

			$base = '<?php
namespace Serenity;

class ' . ucfirst($pageName) . 'Page extends SerenityPage
{
    function index()
    {

    }
}';


			$file = $newPageDir . "/code/code.php";
			$fh = fopen($file, 'w');
			fwrite($fh, $base);
			fclose($fh);
		}

        $file = $newPageDir . "/code/config.yaml";
        $fh = fopen($file, 'w');
        fclose($fh);

		$this->setNotice('success', "Page '$pageName' created.");
		sendTo(getPageUrl('backend'));
	}

	public function newPage_registerParams()
	{
		$this->setErrorUrl('backend', 'index');

		$this->addParam("pageName", array("type" => "string", "required" => true));
		$this->addParam("pageModel", array("type" => "string", "required" => true));
	}
}
