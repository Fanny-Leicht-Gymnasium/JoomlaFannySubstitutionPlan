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

defined('_JEXEC') or die('Restricted access');

/**
 * Class to parse a substitution plan from untis
 *
 * @category   Extension
 * @package    Joomla.Administrator
 * @subpackage Com_FannySubstitutionPlan
 * @author     Dorian Zedler <dorian@itsblue.de>
 * @license    https://www.gnu.org/licenses/gpl-3.0.de.html GNU GPL 3
 * @link       www.fanny-leicht.de
 * @since      0.0.1
 */
class UntisEventParser
{
    /**
     * Function to parse a substitution plan from untis
     * 
     * @param string $eventsFile        full path an filname 
     *                                  of the text file to parse
     * @param string $headerStartString the first few chars of the first 
     *                                  line of the header 
     *                                  !!MUST ONLY BE IN THE FIRST LINE 
     *                                  OF THE HEADER!!
     *                                  (nowhere else)
     * @param string $bodyStartString   the first few chars of the first 
     *                                  line of the body
     *                                  !!MUST ONLY BE IN THE FIRST LINE 
     *                                  OF THE HEADER!!
     *                                  (nowhere else)
     * 
     * @return mixed object containing all parsed data from the given file
     */
    public static function parse($eventsFile, $headerStartString, $bodyStartString)
    {
        // array to store results
        $eventsObj = array(
            "targetDate" => "",
            "refreshDate" => "",
            "stewardingClass" => "",
            "additionalHeaderData" => array(),
            "events" => array(),
            "rawHeader" => array(),
            "rawEvents" => array(),
            "rawData" => array()
        );

        // open the file as read-only
        $handle = fopen($eventsFile, 'r');

        // and read all of its contents
        $data = fread($handle, filesize($eventsFile));

        // split the raw string up into its rows
        $rows = explode("\n", $data);
        $eventsObj["rawData"] = $rows;

        // ----------------------------
        // -Separate header and events-
        // ----------------------------
        // variable to count empty rows in order to detect 
        // when the header is over (two empty rows)

        $emptycount = 0;

        // variable to store the current section of the document

        $currentSection = - 1; // -1 = unset; 0 = header; 1 = body (events)

        // variable to store the current page of the document

        $currentPage = 0;

        // go through all rows to separate the header and the actual data
        for ($i = 0; $i < count($rows); $i++) {

            // store the current row
            $row = $rows[$i];
            if (substr($row, 0, strlen($headerStartString)) === $headerStartString) {
                // if the row starts with the header start string 
                // -> set the current section to header 
                //    and increase the page count by one
                $currentSection = 0;
                $currentPage++;
            } else if (substr(
                $row, 0, strlen($bodyStartString)
            ) === $bodyStartString
            ) {
                // if the row starts with the body start string 
                // -> set the current section to body and set the header to done
                $currentSection = 1;

                if ($currentPage > 1) {
                    // if we're not on the first page -> skip the column description
                    $row = "";
                }
            }

            if ($currentSection == 0 && $currentPage == 1 && $row !== "") {
                // if we're in the header on the first page and the row is not empty 
                // -> put the row into the raw header
                array_push($eventsObj["rawHeader"], $row);
            } else if ($currentSection == 1 && $row !== "") {
                // if we're in the body and the row is not empty 
                // -> put the row into the raw data
                array_push($eventsObj["rawEvents"], $row);
            }
        }

        // --------------------------
        // -----Parse raw header-----
        // --------------------------

        // get the relevant data out of the raw header
        $eventsObj["targetDate"] = $eventsObj["rawHeader"][2];
        $eventsObj["stewardingClass"] = $eventsObj["rawHeader"][3];
        $eventsObj["refreshDate"] = UntisEventParser::processRow(
            $eventsObj["rawHeader"][1]
        )[2];

        // add the rest of the data
        for ($i = 4; $i < count($eventsObj["rawHeader"]); $i++) {
            array_push(
                $eventsObj["additionalHeaderData"], $eventsObj["rawHeader"][$i]
            );
        }

        // --------------------------
        // -----Parse raw events-----
        // --------------------------

        // count of the cols(is set during parsing of the first row)
        $cols = 7;

        // go through the list and process every single row
        for ($x = 0; $x < count($eventsObj["rawEvents"]); $x++) {

            // store the event string
            $event = $eventsObj["rawEvents"][$x];

            // split the different
            $eventList = UntisEventParser::processRow(
                $event, $x === 0 ? 1:2, $cols
            );

            if ($x === 0) {
                $cols = count($eventList);
            }

            // append the current event to the final list
            array_push($eventsObj["events"], $eventList);
        }

        // return the results
        return $eventsObj;
    }

    /**
     * Helper function to process a single row
     * 
     * Takes a string and splits it into pieces (after a certian amount of spaces)
     * 
     * @param string $row       the string to process
     * @param int    $minSpaces count of spaces to split after
     * @param int    $minCols   minimum length of resulting list
     * 
     * @return array array of strings
     */
    public static function processRow($row, $minSpaces = 2, $minCols = 7)
    {

        // value to count spaces between text
        $spaceCount = 0;

        // temporary list to store the data of one day
        $eventList = "";
        $eventList = array();

        // temporary dtring to store the data of one block
        $tmpString = "";

        // processing works like:
        //  go through the line char by char
        for ($i = 0; $i < strlen($row); $i++) {

            // store the char temporarly
            $tmpChar = $row[$i];

            // check if the char is a space
            if ($tmpChar === " ") {

                // if so, increase the spaceCount by one
                $spaceCount++;
            } else {

                // if not -> new block or part of a block started
                // could be : 8a     1 - 2 OR 8a     1 - 2
                //             here->|     OR    here->|
                // in the first case, the space counter is higer than one
                // in the second case, the space counter is exactly one

                if ($spaceCount === 1) {

                    // -> append a space to the temp string
                    $tmpString.= " ";
                }

                // reset the space counter
                $spaceCount = 0;

                // append the current character
                $tmpString.= $tmpChar;
            }

            // check if the space count is 2
            if ($spaceCount === $minSpaces) {

                // if so -> break between two blocks
                // could be: 8a     1 - 2
                //       here ->|
                // -> append the current tmpString to the eventList
                array_push($eventList, $tmpString);

                // and clear the tmpString
                $tmpString = "";
            }
        }

        // append the remaining tmpString to the eventList
        array_push($eventList, $tmpString);

        // fill up the list to 7 elements
        while (count($eventList) < $minCols) {
            array_push($eventList, "");
        }

        return ($eventList);
    }
}

?>
