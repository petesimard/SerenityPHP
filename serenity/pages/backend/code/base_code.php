[phpStart]
namespace Serenity;

class [PageName]Page extends SerenityPage
{
	function index()
	{
		$this->[modelName]s = [ModelName]Model::query()->fetchAll();
	}

	function show()
	{
		$this->[modelName] = [ModelName]Model::query($this->getParam('[modelName]_[modelPrimaryKey]'))->fetchOne();

		if($this->[modelName] == null)
		{
			$this->setNotice('error', 'Invalid ID specified.');
			sp::app()->redirect('[pageName]', 'index');
		}
	}

	function create()
	{
		$this->[modelName] = sp::app()->getModel('[modelName]');
	}

	function show_registerParameters()
	{
		$this->addParam("[modelName]_[modelPrimaryKey]", array("type" => "int", "required" => true));
	}

	function edit()
	{
		$this->[modelName] = [ModelName]Model::query($this->getParam('[modelName]_[modelPrimaryKey]'))->fetchOne();

		if($this->[modelName] == null)
		{
			$this->setNotice('error', 'Invalid ID specified.');
			sp::app()->redirect('[pageName]', 'index');
		}
	}

	function edit_registerParameters()
	{
		$this->addParam("[modelName]_[modelPrimaryKey]", array("type" => "int", "required" => true));
	}

	function save()
	{
		$this->[modelName] = $this->getForm();

    	if($this->isFormValid())
    	{
    		$this->[modelName]->save();
    		$this->setNotice('success', 'Successfully saved');

    		$this->[modelName]s = [ModelName]Model::query()->fetchAll();
    		sendTo(getPageUrl('[pageName]', 'index'));
    	}
    	else
    	{
    		if($this->[modelName]->getPrimaryKeyValue())
    			$this->setTemplate('edit');
    		else
    			$this->setTemplate('create');
    	}
	}
}