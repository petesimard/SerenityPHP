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

/**
 * Exception class
 * @author Pete
 *
 */
class SerenityException extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}


/**
 * Serenity exception handler
 * @param SernityException $exception
 */
function exception_handler($exception)
{
    ?>
    <b>Exception:</b> <?=$exception->getMessage()?>
    <br>
    <b>In file:</b> <?=$exception->getFile()?> Line <?=$exception->getLine()?><br>
    <b>Stack Trace:</b><br>
    <?
    foreach($exception->getTrace() as $trace)
    {
        echo $trace['function'] . "() -- " . $trace[file] . " line " . $trace[line] . "<br>";
    }
}

// Register the exception handler
set_exception_handler('Serenity\exception_handler');

// Start initilization
sf::init();
?>
