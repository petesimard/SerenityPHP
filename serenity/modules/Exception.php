<?php
namespace Serenity;

/**
 * Serenity exception handler
 * @param SernityException $exception
 */
function exception_handler($exception)
{
    if(sp::app()->isDebugMode())
    {
        ?>
        <b>Exception:</b> <?=$exception->getMessage()?>
        <br>
        <b>In file:</b> <?=$exception->getFile()?> Line <?=$exception->getLine()?><br>
        <b>Stack Trace:</b><br>
        <?
        foreach($exception->getTrace() as $trace)
        {
            if(!isset($trace['file']))
                $trace['file'] = 'Unknown File';
            if(!isset($trace['line']))
                $trace['line'] = 'Unknown Line';

            echo $trace['function'] . "() -- " . $trace['file'] . " line " . $trace['line'] . "<br>";
        }

        if(!is_null(sp::app()->getRoute()) && !sp::app()->getRoute()->isAjax)
    	    echo sp::app()->getSnippet("debug");
    }
    else 
    {
        echo '<h2>Internal error</h2>';
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
 * Throw when you want an action to stop processing
 * @author Pete
 *
 */
class SerenityStopException extends SerenityException
{
    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}



// Register the exception handler
set_exception_handler('Serenity\exception_handler');
?>