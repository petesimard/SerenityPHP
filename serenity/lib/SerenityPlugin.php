<?php
namespace Serenity;

/**
 * Abstract class plugin's inherit from
 * @author Pete
 *
 */
abstract class SerenityPlugin
{
    /**
     * Called once on page load
     * @param unknown_type $params
     */
    abstract function onAppLoad($params);
    /**
     * Called whenever a page action is called
     * @param SerenityPage $page
     */
    abstract function onActionStart($page);
    /**
     * Called at the end of a page action
     * @param SerenityPage $page
     */
    abstract function onActionEnd($page);
    
     /**
      * Return an array of key => values that will be converted
      * into local variables for easy use in templates and layouts
      */
     abstract function getTemplateVariables();
}
?>
