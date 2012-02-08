<?php
namespace Serenity;

abstract class SerenityBackendPage extends SerenityPage
{
    private function testIP($ip)
    {
        for($i=0, $cnt=count($ip); $i<$cnt; $i++)
        {
            $ipregex = @preg_replace("/./", "\.", $ip[$i]);
            $ipregex = @preg_replace("/*/", ".*", $ipregex);

            if(preg_match('/^'.$ipregex.'/', $_SERVER['REMOTE_ADDR']))
                return true;
        }
        return false;
    }

	public function setCurrentAction($actionName)
	{
		$whitelist = array('127.0.0.1', '49.49.88.*');

		if(!$this->testIP($whitelist))
			throw new SerenityException("Serenity Backend can not be accessed remotely.");

		parent::setCurrentAction($actionName);
	}
}