<?php
/**
 * Fanny Substitution Plan 
 * Joomla extension for managing the substitution plan of the Fanny-Leicht Gymnasium
 * 
 * PHP version 5
 * 
 * @category   Extension
 * @package    Joomla.Administrator
 * @subpackage Com_FannySubstitutionPlan
 * @author     Dorian Zedler <dorian@itsblue.de>
 * @license    https://www.gnu.org/licenses/gpl-3.0.de.html GNU GPL 3
 * @link       www.fanny-leicht.de  
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// register event parser
JLoader::register('UntisEventParser', JPATH_COMPONENT . '/helpers/parser.php');

// Get an instance of the controller prefixed by FannySubstitutionPlan
$controller = JControllerLegacy::getInstance('FannySubstitutionPlan');

// Perform the Request task
$controller->execute(JFactory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();

?>