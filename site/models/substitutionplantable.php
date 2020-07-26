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
 * Model for the substitutionplan view
 *
 * @category   Extension
 * @package    Joomla.Administrator
 * @subpackage Com_FannySubstitutionPlan
 * @author     Dorian Zedler <dorian@itsblue.de>
 * @license    https://www.gnu.org/licenses/gpl-3.0.de.html GNU GPL 3
 * @link       www.fanny-leicht.de
 * @since      0.0.1
 */
class FannySubstitutionPlanModelSubstitutionPlanTable extends JModelItem
{

    /**
     * Function to get the substitution plan data of a certain day and mode
     * 
     * @param string $mode 0: student, 1:teacher
     * @param string $day  0: today, 1: tomorrow
     * 
     * @return mixed array with substitution plan data
     */
    public function getSubstitutionPlan($mode, $day) 
    {
        // get component parameters
        $params = JComponentHelper::getParams('com_fannysubstitutionplan');

        // get user parameters
        $mode = JFactory::getApplication()->input->get('mode');
        $day = JFactory::getApplication()->input->get('day');

        $defaultBasepath = '/srv/www/virtual/22609/www2.fanny-leicht.de/
        vhostdata/htdoc/static15/http.intern/';

        // get the filename
        $filePath = $mode === "1" ? 
        ($day === "1" ? $params->get('fileLocation_t2', $defaultBasepath.'lmorgen')
        :$params->get('fileLocation_t1', $defaultBasepath.'lheute')):
        ($day === "1" ? $params->get('fileLocation_s2', $defaultBasepath.'smorgen')
        :$params->get('fileLocation_s1', $defaultBasepath.'sheute'));

        if (!file_exists($filePath.'.pdf')) {
            JError::raiseError(500, 'The substitutionplan file was not found!');
            return array();
        }

        // parse the requested file
        $eventsObj = UntisEventParser::parse(
            $filePath.'.txt', "D-70563", ($mode === "1" ? "Vertr.":"Kl.")
        );

        // remove unnecessary stuff
        unset($eventsObj['rawHeader']);
        unset($eventsObj['rawEvents']);
        unset($eventsObj['rawData']);

        return $eventsObj;
    }
}