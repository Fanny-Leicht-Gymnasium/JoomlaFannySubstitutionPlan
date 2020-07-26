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

/**
 * HTML View class for the Substitution plan table
 *
 * @category   Extension
 * @package    Joomla.Administrator
 * @subpackage Com_FannySubstitutionPlan
 * @author     Dorian Zedler <dorian@itsblue.de>
 * @license    https://www.gnu.org/licenses/gpl-3.0.de.html GNU GPL 3
 * @link       www.fanny-leicht.de
 * @since      0.0.1
 */
class FannySubstitutionPlanViewSubstitutionPlanTable extends JViewLegacy
{
    /**
     * Display the Table view
     *
     * @param string $tpl The name of the template file to parse; 
     *                    automatically searches through the template paths.
     *
     * @return void
     */
    function display($tpl = null)
    {
        // Assign data to the view
        $this->data = $this->get('SubstitutionPlan');
        $this->events = $this->data["events"];
        $this->mode = JFactory::getApplication()->input->get('mode') == "" ? 
        "0":JFactory::getApplication()->input->get('mode');
        $this->day = JFactory::getApplication()->input->get('day') == "" ? 
        "0":JFactory::getApplication()->input->get('day');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JLog::add(implode('<br />', $errors), JLog::WARNING, 'jerror');

            return false;
        }

        // Display the view
        parent::display($tpl);
    }
}