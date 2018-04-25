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
 * Internal library of functions for module ableplayer
 *
 * All the ableplayer specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage ableplayer
 * @author Tõnis Tartes <tonis.tartes@gmail.com>
 * @copyright  2013 Tõnis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * ableplayer helper
 */
function ableplayer_player_helper($ableplayer, $cm, $context) {
    global $CFG, $COURSE, $CFG;
    
    $fs = get_file_storage();
    $videofiles = $fs->get_area_files($context->id, 'mod_ableplayer', 'file', false, '', false);
    return ableplayer_player($ableplayer, $context, $videofiles);
}

/*
 * Embed video player
 */
function ableplayer_player($ableplayer, $context, $videofiles) {
    global $CFG, $COURSE, $CFG;
    
    $videos = '';
    $general_options = '';
    $playlist_options = '';
    $caption_settings = '';
    $caption_attribute = '';
    $img_attribute = '';

    //Videos
    foreach($videofiles as $videofile) {
        $videolabel = explode('.', $videofile->get_filename());
        $videourl = moodle_url::make_file_url('/pluginfile.php', '/'.$context->id.'/mod_ableplayer/file/0/'.$videofile->get_filename());
        //$videos .= '{ file: "'.$videourl.'", label: "'.$videolabel[0].'" }, ';
    }

    $asd = '<video id="video1" data-able-player preload="auto" width="auto" height="auto">
  <source type="video/mp4" src="'.$videourl.'" data-desc-src="'.$videourl.'"/>
</video>';

    return $asd;

    $playlist_attributes = array('title');
    $general_attributes = array('controls', 'ableplayerrepeat', 'autostart', 'stretching', 'mute', 'width', 'height');
    
    foreach($ableplayer as $key => $value) {
        if (in_array($key, $playlist_attributes)) {
            $playlist_options .= $key.': "'.$value.'", ';
        }
        if (in_array($key, $general_attributes)) {
            if ($key == 'ableplayerrepeat') {
                $key = 'repeat';
            }
            $general_options .= $key.': "'.$value.'", ';
        }
    }
    
    $attributes = 'playlist: [{'.
                    $img_attribute.
                    $caption_attribute.
                    $playlist_options.
                    'sources: ['.$videos.']'.
                  '}], ';    
    $attributes .= $caption_settings;
    $attributes .= $general_options;
    
    //Player
    $player = html_writer::tag('div', '..Loading..', array('id' => 'videoElement'));
    //JS
    $jscode = 'jwplayer("videoElement").setup({'.
               $attributes.
              '});'; 
    $player .= html_writer::script($jscode);
   
    return $player;
}

/**
 * Standard base class for mod_videofile.
 */
class videofile {
    /** @var stdClass The videofile record that contains the
     *                global settings for this videofile instance.
     */
    private $instance;
    /** @var context The context of the course module for this videofile instance
     *               (or just the course if we are creating a new one).
     */
    private $context;
    /** @var stdClass The course this videofile instance belongs to */
    private $course;
    /** @var videofile_renderer The custom renderer for this module */
    private $output;
    /** @var stdClass The course module for this videofile instance */
    private $coursemodule;
    /** @var string modulename Prevents excessive calls to get_string */
    private static $modulename = null;
    /** @var string modulenameplural Prevents excessive calls to get_string */
    private static $modulenameplural = null;
    /**
     * Constructor for the base videofile class.
     *
     * @param mixed $coursemodulecontext context|null The course module context
     *                                   (or the course context if the coursemodule
     *                                   has not been created yet).
     * @param mixed $coursemodule The current course module if it was already loaded,
     *                            otherwise this class will load one from the context
     *                            as required.
     * @param mixed $course The current course if it was already loaded,
     *                      otherwise this class will load one from the context as
     *                      required.
     */
    public function __construct($coursemodulecontext, $coursemodule, $course) {
        global $PAGE;
        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
    }
    /**
     * Set the course data.
     *
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }
    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return mixed False if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata) {
        global $DB;
        // Add the database record.
        $add = new stdClass();
        $add->name = $formdata->name;
        $add->timemodified = time();
        $add->timecreated = time();
        $add->course = $formdata->course;
        $add->courseid = $formdata->course;
        $add->intro = $formdata->intro;
        $add->introformat = $formdata->introformat;
        $returnid = $DB->insert_record('ableplayer', $add);

        $this->instance = $DB->get_record('ableplayer',
            array('id' => $returnid),
            '*',
            MUST_EXIST);
        $this->save_files($formdata);

        if (!empty($formdata->captions)) {
            foreach ($formdata->captions as $key => $value) {
                $caption = new stdClass();
                $caption->ableplayerid = $returnid;
                $caption->label = $formdata->label[$key];
                $caption->kind = $formdata->kind[$key];
                $caption->srclang = $formdata->srclang[$key];
                $captionid = $DB->insert_record("ableplayer_captions", $caption, true);
                // Storage of files from the filemanager (captions).
                $draftitemid = $value;
                if ($draftitemid) {
                    file_save_draft_area_files(
                        $draftitemid,
                        $this->context->id,
                        'mod_ableplayer',
                        'captions',
                        $captionid
                    );
                }
            }
        }

        // Cache the course record.
        $this->course = $DB->get_record('course',
            array('id' => $formdata->course),
            '*',
            MUST_EXIST);
        return $returnid;
    }
    /**
     * Delete this instance from the database.
     *
     * @return bool False if an error occurs
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        // Delete files associated with this videofile.
        $fs = get_file_storage();
        if (! $fs->delete_area_files($this->context->id) ) {
            $result = false;
        }
        // Delete the instance.
        // Note: all context files are deleted automatically.
        $DB->delete_records('ableplayer', array('id' => $this->get_instance()->id));
        $DB->delete_records('ableplayer_captions', array('ableplayerid' => $this->get_instance()->id));
        return $result;
    }
    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return bool False if an error occurs
     */
    public function update_instance($formdata) {
        global $DB;
        $update = new stdClass();
        $update->id = $formdata->instance;
        $update->name = $formdata->name;
        $update->timemodified = time();
        $update->course = $formdata->course;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $result = $DB->update_record('ableplayer', $update);
        $this->instance = $DB->get_record('ableplayer',
            array('id' => $update->id),
            '*',
            MUST_EXIST);
        $this->save_files($formdata);

        if (!empty($formdata->captions)) {
            foreach ($formdata->captions as $key => $value) {
                if (isset($formdata->captionid[$key]) && !empty($formdata->captionid[$key])) {//existing choice record
                    $caption = new stdClass();
                    $caption->ableplayerid = $formdata->instance;
                    $caption->label = $formdata->label[$key];
                    $caption->kind = $formdata->kind[$key];
                    $caption->srclang = $formdata->srclang[$key];
                    $caption->id = $formdata->captionid[$key];
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'captions',
                            $caption->id
                        );
                    }
                    $fs = get_file_storage();
                    $filex = $fs->get_area_files($this->context->id, 'mod_ableplayer', 'captions', $formdata->captionid[$key], 'itemid, filepath, filename', false);
                    if (!empty($filex)) {
                        $DB->update_record("ableplayer_captions", $caption);
                    } else {
                        $DB->delete_records("ableplayer_captions", array('id' => $caption->id));
                    }
                } else {
                    $caption = new stdClass();
                    $caption->ableplayerid = $formdata->instance;
                    $caption->label = $formdata->label[$key];
                    $caption->kind = $formdata->kind[$key];
                    $caption->srclang = $formdata->srclang[$key];
                    $captionid = $DB->insert_record("ableplayer_captions", $caption, true);
                    // Storage of files from the filemanager (captions).
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'captions',
                            $captionid
                        );
                    }
                }
            }
        }

        return $result;
    }
    /**
     * Get the name of the current module.
     *
     * @return string The module name (Videofile)
     */
    protected function get_module_name() {
        if (isset(self::$modulename)) {
            return self::$modulename;
        }
        self::$modulename = get_string('modulename', 'ableplayer');
        return self::$modulename;
    }
    /**
     * Get the plural name of the current module.
     *
     * @return string The module name plural (Videofiles)
     */
    protected function get_module_name_plural() {
        if (isset(self::$modulenameplural)) {
            return self::$modulenameplural;
        }
        self::$modulenameplural = get_string('modulenameplural', 'ableplayer');
        return self::$modulenameplural;
    }
    /**
     * Has this videofile been constructed from an instance?
     *
     * @return bool
     */
    public function has_instance() {
        return $this->instance || $this->get_course_module();
    }
    /**
     * Get the settings for the current instance of this videofile.
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('ableplayer', $params, '*', MUST_EXIST);
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the videofile class. ' .
                'Cannot load the videofile record.');
        }
        return $this->instance;
    }
    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the videofile class. ' .
                'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }
    /**
     * Get the current course module.
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }
        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('ableplayer',
                $this->context->instanceid,
                0,
                false,
                MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }
    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    public function get_captions_settings($id) {
        global $DB;
        $captions = $DB->get_records('ableplayer_captions', array('ableplayerid' => $id));
        return $captions;
    }
    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;
        if ($this->course) {
            return $this->course;
        }
        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);
        return $this->course;
    }
    /**
     * Lazy load the page renderer and expose the renderer to plugins.
     *
     * @return videofile_renderer
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('mod_ableplayer');
        return $this->output;
    }
    /**
     * Save draft files.
     *
     * @param stdClass $formdata
     * @return void
     */
    protected function save_files($formdata) {
        global $DB;
        // Storage of files from the filemanager (medias).
        $draftitemid = $formdata->medias;
        if ($draftitemid) {
            file_save_draft_area_files(
                $draftitemid,
                $this->context->id,
                'mod_ableplayer',
                'medias',
                0
            );
        }
        // Storage of files from the filemanager (posters).
        $draftitemid = $formdata->posters;
        if ($draftitemid) {
            file_save_draft_area_files(
                $draftitemid,
                $this->context->id,
                'mod_ableplayer',
                'posters',
                0
            );
        }
    }
}