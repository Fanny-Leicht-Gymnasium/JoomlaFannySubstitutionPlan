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
 * General Controller of FannySubstitutionPlan component
 *
 * @category   Extension
 * @package    Joomla.Administrator
 * @subpackage Com_FannySubstitutionPlan
 * @author     Dorian Zedler <dorian@itsblue.de>
 * @license    https://www.gnu.org/licenses/gpl-3.0.de.html GNU GPL 3
 * @link       www.fanny-leicht.de
 * @since      0.0.1
 */
class FannySubstitutionPlanController extends JControllerLegacy
{
    /**
     * The default view for the display method.
     *
     * @var string
     */
    protected $default_view = 'substitutionplantable';

    /**
     * All valid commands for the api
     * They have to be implemented as functions that terminate the application 
     * after their execution!!
     * 
     * @var mixed
     */
    protected $apiCommands = array('login', 'getData');

    /**
     * Function called when the controller is executed
     * 
     * @param string $task task to execute can be:
     *                     empty - show default view
     *                     downloadPdf - get sub plan as pdf
     *                     api_login - get a login token
     *                     api_getData - get sub plan
     * 
     * @return void
     */
    public function execute($task) 
    {

        $user = JFactory::getUser();        // Get the user object
        $app  = JFactory::getApplication(); // Get the application

        $view = JFactory::getApplication()->input->get('view');

        if ($user->id != 0) {
            // user is logged in

            // get component params
            $params = JComponentHelper::getParams('com_fannysubstitutionplan');

            // get user parameters
            $mode = JFactory::getApplication()->input->get('mode');
            $day = JFactory::getApplication()->input->get('day');

            // check if the user actually has permission to view the requested plan
            $user   = JFactory::getUser();
            $levels = JAccess::getAuthorisedViewLevels($user->id);

            // if the mode is not set
            // -> check if the users permissions are sufficient
            //    to view the teachers sPlan and redirect in that case
            if (!isset($mode) 
                && in_array($params->get('teacherPlanAccess'), $levels)
            ) {
                $url = JRoute::_(
                    'index.php?option=com_fannysubstitutionplan
                    &view=substitutionplantable&mode=1&day='.$day.'&task='.$task
                );
                $app->redirect($url);
                JFactory::getApplication()->close();
            }

            // check if the users permissions are sufficient to view the sPlan 
            // he requested
            if (!in_array(
                $params->get(
                    $mode === "1" ? 'teacherPlanAccess':'studentPlanAccess'
                ), $levels
            )
            ) {
                // if they are not and the mode is set to 1
                // -> check if the user has acces to that sPlan
                //    and redirect properly
                if ($mode === "1" 
                    && in_array($params->get('studentPlanAccess'), $levels)
                ) {
                    $url = JRoute::_(
                        'index.php?option=com_fannysubstitutionplan
                        &view=substitutionplantable&mode=0&day='.$day.'&task='.$task
                    );
                    $app->redirect(
                        $url, "You have no permission 
                        to access the teachers substitutionplan."
                    );
                } else {
                    $url = JRoute::_('index.php/');
                    $app->redirect(
                        $url, "You have no permission to access substitutionplan."
                    );
                }

                JFactory::getApplication()->close();
            }

            if ($task === "downloadPdf") {
                $pdfFile = $this->getSubstitutionPlanFile().'.pdf';
                $this->sendFile($pdfFile);
            }

            // call the superclass
            parent::execute($task);
            
        } else if (count(explode("_", $task)) === 2 
            && explode("_", $task)[0] === "api" 
            && in_array(explode("_", $task)[1], $this->apiCommands)
        ) {
            // this is an api request 
            // -> login is not handled using the joomla! login system
            $task = explode("_", $task)[1];
            parent::execute($task);
        } else {
            // this is not an api call and the user is not logged in
            // -> redirect to login

            if (!isset($view)) {
                $view = $this->default_view;
            }
            $day = JFactory::getApplication()->input->get('day');
            $message = "Bitte zuerst anmelden";
            $url = JRoute::_(
                'index.php?option=com_users&view=login&return=' . 
                base64_encode(
                    'index.php?option=com_fannysubstitutionplan&view='.$view.
                    '&task='.$task.'&day='.$day
                )
            );
            $app->redirect($url, $message);
        }
    }

    /**
     * API function to login a user (called when task of execute is api_login)
     * 
     * Requires GET parameters:
     * username - username of the user to log in
     * password - password of the user to log in
     * 
     * @return void
     */
    public function login() 
    {
        // Get the log in options.
        $loginIsBase64 = JFactory::getApplication()->input->get('loginIsBase64');

        if ($loginIsBase64) {
            $username  = base64_decode(
                JFactory::getApplication()->input->get('username')
            );
            $password  = base64_decode(
                JFactory::getApplication()->input->get('password')
            );
        } else {
            // use $_GET to get specialcharaters like '@'
            $username  = $_GET['username'];
            $password  = JFactory::getApplication()->input->get('password');
        }

        // Get a db connection.
        $db = JFactory::getDbo();

        // Create a new query object.
        $query = $db->getQuery(true);

        // Select all records from the user profile table 
        // where key begins with "custom.".
        // Order it by the ordering field.
        $query->select($db->quoteName(array('id', 'name', 'password', 'block')));
        $query->from($db->quoteName('#__users'));
        $query->where($db->quoteName('username') . ' = ' . $db->quote($username));
        $query->limit('1');

        // Reset the query using our newly populated query object.
        $db->setQuery($query);

        // Load the results as a list of stdClass objects
        $results = $db->loadObjectList();

        if (count($results) === 1) {
            $user = $results[0];
            if (JUserHelper::verifyPassword($password, $user->password, $user->id)) {
                if ($user->block === '0') {
                    // user data is correct and user is not blocked
                    $this->sendApiResponse(
                        200, array(
                        'token' => base64_encode($user->id . ':' . $user->password)
                        )
                    );
                } else {
                    // user data is correct but user is blocked
                    $this->sendApiResponse(
                        403, array("errorMsg" => "This user is blocked.")
                    );
                }
            } else {
                // password is incorrect
                $this->sendApiResponse(
                    401, array("errorMsg" => "Invalid username or password.")
                );
            }
        } else {
            // username was not found
            $this->sendApiResponse(
                401, array("errorMsg" => "Invalid username or password.")
            );
        }
        
        // this should never happen!
        $this->sendApiResponse(
            500, array("errorMsg" => "Some server error occured.")
        );
    }

    /**     
     * API function to get the substitutionplan of a certain day 
     * (called when task of execute is api_getData)
     * 
     * Requires GET parameters:
     * token - token of the user
     * mode - 0: student, 1:teacher
     * day - 0: today, 1: tomorrow
     * asPdf - false: response as JSON, true: response as pdf
     * 
     * @return void
     */
    public function getData() 
    {
        // verify login token
        $token = JFactory::getApplication()->input->get('token');
        $ret = $this->verifyToken($token);

        // get mode
        $mode = JFactory::getApplication()->input->get('mode');

        // quit if the token is incorrect
        if ($ret === 401) {
            $this->sendApiResponse(401, array("errorMsg" => "Invalid token."));
        } else if ($ret === 403) {
            $this->sendApiResponse(
                403, array("errorMsg" => "This user is blocked.")
            );
        }

        $asPdf = JFactory::getApplication()->input->get('asPdf');
        
        $filePath = $this->getSubstitutionPlanFile();

        if ($asPdf === 'true') {
            // pdf was requests
            if (!file_exists($filePath.'.pdf')) {
                $this->sendApiResponse(
                    500, array("errorMsg" => "Substitutionplan file not found.")
                );
            }

            // send the pdf file
            $this->sendFile($filePath.'.pdf');
        } else {
            // JSON was requested

            if (!file_exists($filePath.'.txt')) {
                http_response_code(500);
                $this->sendApiResponse(
                    500, array("errorMsg" => "Substitutionplan file not found.")
                );
            }
    
            // parse the requested file
            $eventsObj = UntisEventParser::parse(
                $filePath.'.txt', "D-70563", ($mode === "1" ? "Vertr.":"Kl.")
            );
    
            // remove unnecessary stuff
            unset($eventsObj['rawHeader']);
            unset($eventsObj['rawEvents']);
            unset($eventsObj['rawData']);
    
            // send the parsed data
            $this->sendApiResponse(200, $eventsObj);
        }
    }

    /**
     * Function to get the filename and path of the 
     * currently requested substitution plan file
     * 
     * Requires GET parameters:
     * mode - 0: student, 1:teacher
     * day - 0: today, 1: tomorrow
     * 
     * @return string path and filename without ending
     */
    public static function getSubstitutionPlanFile() 
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

        return $filePath;
    }

    /**
     * Function to send finished data as JSOn to the client
     * and close the application
     * 
     * @param int   $result HTTP status code
     * @param mixed $data   data to deliver
     * 
     * @return void
     */
    public function sendApiResponse($result, $data = null) 
    {
        jimport('joomla.application.component.controller');
        header('Content-Type: application/json');
        http_response_code($result);

        $data = array (
            "result" => $result,
            "version" => '1.0.0',
            "data" => $data
        );

        echo json_encode($data);

        // quit
        JFactory::getApplication()->close();
    }

    /**
     * Function to send a file to the client
     * and close the application
     * 
     * @param string $filePath full filename and path
     * 
     * @return void
     */
    public function sendFile($filePath) 
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header(
            'Content-Disposition: attachment; filename="'.basename($filePath).'"'
        );
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);

        // quit
        JFactory::getApplication()->close(); 
    }

    /**
     * Function to verify a login token
     * 
     * @param string $token base64 encoded token, format:
     *                      <userid>:<hashed password>
     * 
     * @return int 401: invalid, 403: user is blocked, 200: OK
     */
    public function verifyToken($token) 
    {
        // decode the token
        $token = base64_decode($token);
        
        // split token
        $userId = explode(":", $token)[0];
        $passwordHash = substr($token, strlen($userId)+1);

        // Get the log in options.
        $username  = JFactory::getApplication()->input->get('username');
        $password  = JFactory::getApplication()->input->get('password');

        // Get a db connection.
        $db = JFactory::getDbo();

        // Create a new query object.
        $query = $db->getQuery(true);

        // search for the requested user
        $query->select($db->quoteName(array('id', 'password', 'block')));
        $query->from($db->quoteName('#__users'));
        $query->where(
            $db->quoteName('id') . ' = ' . $db->quote($userId) . ' AND ' . 
            $db->quoteName('password') . ' = ' . $db->quote($passwordHash)
        );

        // Reset the query using our newly populated query object.
        $db->setQuery($query);

        // Load the results as a list of stdClass objects 
        $results = $db->loadObjectList();

        if (count($results) !== 1) {
            return 401;
        } else if ($results[0]->block !== '0' ) {
            return 403;
        } else {
            return 200;
        }
    }
}