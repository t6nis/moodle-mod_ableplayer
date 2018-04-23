<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of ableplayer
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage ableplayer
 * @author     Tõnis Tartes <tonis.tartes@gmail.com>
 * @copyright  2013 Tõnis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ableplayer instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('ableplayer', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $ableplayer  = $DB->get_record('ableplayer', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $ableplayer  = $DB->get_record('ableplayer', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $ableplayer->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('ableplayer', $ableplayer->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$videofile = new videofile($context, $cm, $course);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Print the page header
$PAGE->set_url('/mod/ableplayer/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($ableplayer->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_cacheable(true);

// Output starts here
echo $OUTPUT->header();

echo '<script src="thirdparty/modernizr.custom.js"></script>';
echo '<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>';
echo '<script src="thirdparty/js.cookie.js"></script>';
echo '<link rel="stylesheet" href="styles/ableplayer.min.css" type="text/css"/>';
echo '<script src="js/ableplayer.min.js"></script>';

if ($ableplayer->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('ableplayer', $ableplayer, $cm->id), 'generalbox mod_introbox', 'ableplayerintro');
}

//echo ableplayer_video($ableplayer, $cm, $context);
$renderer = $PAGE->get_renderer('mod_ableplayer');
echo $renderer->video_page($videofile);

// Finish the page
echo $OUTPUT->footer();
