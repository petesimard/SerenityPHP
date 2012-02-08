<?php
namespace Serenity;

/**
 * SerenityPHP static class for accessing all other
 * parts of the framework
 * @author Pete
 *
 */
class sp
{
    public static $baseDir = "";
    public static $baseAppDir = "";
    public static $router = null;
    public static $currentApp = null;
    public static $dababase = null;
    public static $parameterValidator = null;
    private static $yamlParser;

    /**
     * Setup initial framework properties
     */
    public static function init($serenity_base_app_dir)
    {
        self::$baseDir = realpath(dirname(__FILE__).'/../..');
        self::$baseAppDir = $serenity_base_app_dir;
        self::$yamlParser = new \sfYamlParser();
    }

    /**
     * Returns the current application controller
     * @return SerenityAppController
     */
    public static function app()
    {
        return self::$currentApp;
    }

    /**
     * Returns the current database controller
     * @return SerenityDatabase
     */
    public static function db()
    {
        return self::$dababase;
    }

    /**
     * Returns the current parameter validator
     * @return ParameterValidator
     */
    public static function validator()
    {
        return self::$parameterValidator;
    }

    /**
     * Returns the current route controller
     * @return SerenityRouter
     */
    public static function router()
    {
        return self::$router;
    }

    public static function yaml()
    {
        return self::$yamlParser;
    }
}

// Start initilization
sp::init($serenity_base_app_dir);
?>
