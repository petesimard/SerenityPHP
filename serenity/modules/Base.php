<?php
namespace Serenity;

/**
 * SerenityPHP static class for accessing all other
 * parts of the framework
 * @author Pete
 *
 */
class sf
{
    public static $baseDir = "";
    public static $router = null;
    public static $currentApp = null;
    public static $dababase = null;
    public static $parameterValidator = null;

    /**
     * Setup initial framework properties
     */
    public function init()
    {
        self::$baseDir = realpath(dirname(__FILE__).'/../..');
    }

    /**
     * Returns the current application controller
     * @return SerenityAppController
     */
    public function app()
    {
        return sf::$currentApp;
    }

    /**
     * Returns the current database controller
     * @return SerenityDatabase
     */
    public function db()
    {
        return sf::$dababase;
    }
    
    /**
     * Returns the current parameter validator
     * @return ParameterValidator
     */
    public function validator()
    {
        return sf::$parameterValidator;
    }
    
    /**
     * Returns the current route controller
     * @return SerenityRouter
     */
    public function router()
    {
    	return sf::$router;
    }
}

// Start initilization
sf::init();
?>
