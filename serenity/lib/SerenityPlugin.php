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
     * @param unknown_type $params
     * @todo impliment
     */
    abstract function onPageStart($params);
    /**
     * Called at the end of a page action
     * @param unknown_type $params
     * @todo impliment
     */
    abstract function onPageEnd($params);
    
     /**
      * Return an array of key => values that will be converted
      * into local variables for easy use in templates and layouts
      */
     abstract function getTemplateVariables();
}
?>
