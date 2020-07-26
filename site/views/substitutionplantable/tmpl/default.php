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
?>
<h1>
    <?php echo $this->data["targetDate"]; ?>
</h1>
<a href="index.php/component/fannysubstitutionplan/?task=downloadPdf&mode=
<?php echo $this->mode; ?>&day=<?php echo $this->day; ?>"
target="blank" class="fa fa-download button"> download als pdf</a>
<p>
    Zuletzt aktualisiert: <?php echo $this->data["refreshDate"]; ?>
</p>
<p>
    <?php echo $this->data["stewardingClass"]; ?>
</p>
<p>
    <?php 
    // add additional header data
    foreach ($this->data["additionalHeaderData"] as $row) {
        echo $row . "<br>\n";
    }
    ?>
</p>
<?php if (!empty($this->events)) : ?>
    <div style='overflow-x:auto;'>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <?php for($i = 0; $i < count($this->events[0]); $i++ ): ?>
                    <th>
                        <?php echo JText::_($this->events[0][$i])?>
                    </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                    <?php for ($i = 1; $i < count($this->events); $i++) :?>
                        <tr>
                            <?php 
                            for($x = 0; $x < count($this->events[$i]); $x++ ): 
                                ?>
                                <td>
                                    <?php echo $this->events[$i][$x]; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-no-items">Keine aktuellen Meldungen</div>
<?php endif; ?>