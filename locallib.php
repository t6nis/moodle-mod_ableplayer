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
 * @package    mod_ableplayer
 * @author     T6nis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Standard base class for mod_ableplayer.
 */
class ableplayer {
    /** @var stdClass The ableplayer record that contains the
     *                global settings for this ableplayer instance.
     */
    private $instance;
    /** @var context The context of the course module for this ableplayer instance
     *               (or just the course if we are creating a new one).
     */
    private $context;
    /** @var stdClass The course this ableplayer instance belongs to */
    private $course;
    /** @var ableplayer_renderer The custom renderer for this module */
    private $output;
    /** @var stdClass The course module for this ableplayer instance */
    private $coursemodule;
    /** @var string modulename Prevents excessive calls to get_string */
    private static $modulename = null;
    /** @var string modulenameplural Prevents excessive calls to get_string */
    private static $modulenameplural = null;
    /**
     * Constructor for the base ableplayer class.
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
        $add->playlist = $formdata->playlist;
        $add->mode = $formdata->mode;
        $add->lang = $formdata->lang;
        $returnid = $DB->insert_record('ableplayer', $add);

        $this->instance = $DB->get_record('ableplayer',
            array('id' => $returnid),
            '*',
            MUST_EXIST);
        $this->save_files($formdata);

        // Media save
        if (!empty($formdata->media)) {
            foreach ($formdata->media as $key => $value) {
                $media = new stdClass();
                $media->ableplayerid = $returnid;
                $mediaid = $DB->insert_record("ableplayer_media", $media, true);
                // Storage of files from the filemanager (captions).
                $draftitemid = $value;
                if ($draftitemid) {
                    file_save_draft_area_files(
                        $draftitemid,
                        $this->context->id,
                        'mod_ableplayer',
                        'media',
                        $mediaid
                    );
                }
            }
        }
        // Desc save
        if (!empty($formdata->media)) {
            foreach ($formdata->media as $key => $value) {
                $desc = new stdClass();
                $desc->ableplayerid = $returnid;
                $descid = $DB->insert_record("ableplayer_desc", $desc, true);
                // Storage of files from the filemanager (captions).
                $draftitemid = $value;
                if ($draftitemid) {
                    file_save_draft_area_files(
                        $draftitemid,
                        $this->context->id,
                        'mod_ableplayer',
                        'desc',
                        $mediaid
                    );
                }
            }
        }
        // Caption save
        if (!empty($formdata->caption)) {
            foreach ($formdata->caption as $key => $value) {
                $caption = new stdClass();
                $caption->ableplayerid = $returnid;
                $caption->label = $formdata->label[$key];
                $caption->kind = $formdata->kind[$key];
                $caption->srclang = $formdata->srclang[$key];
                $captionid = $DB->insert_record("ableplayer_caption", $caption, true);
                // Storage of files from the filemanager (captions).
                $draftitemid = $value;
                if ($draftitemid) {
                    file_save_draft_area_files(
                        $draftitemid,
                        $this->context->id,
                        'mod_ableplayer',
                        'caption',
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
        // Delete files associated with this ableplayer.
        $fs = get_file_storage();
        if (! $fs->delete_area_files($this->context->id) ) {
            $result = false;
        }
        // Delete the instance.
        // Note: all context files are deleted automatically.
        $DB->delete_records('ableplayer', array('id' => $this->get_instance()->id));
        $DB->delete_records('ableplayer_caption', array('ableplayerid' => $this->get_instance()->id));
        $DB->delete_records('ableplayer_media', array('ableplayerid' => $this->get_instance()->id));
        $DB->delete_records('ableplayer_desc', array('ableplayerid' => $this->get_instance()->id));
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
        $update->playlist = $formdata->playlist;
        $update->mode = $formdata->mode;
        $update->lang = $formdata->lang;
        $result = $DB->update_record('ableplayer', $update);
        $this->instance = $DB->get_record('ableplayer',
            array('id' => $update->id),
            '*',
            MUST_EXIST);
        $this->save_files($formdata);

        if (!empty($formdata->media)) {
            foreach ($formdata->media as $key => $value) {
                if (isset($formdata->mediaid[$key]) && !empty($formdata->mediaid[$key])) {//existing choice record
                    $media = new stdClass();
                    $media->ableplayerid = $formdata->instance;
                    $media->id = $formdata->mediaid[$key];
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'media',
                            $media->id
                        );
                    }
                    $fs = get_file_storage();
                    $file = $fs->get_area_files($this->context->id, 'mod_ableplayer', 'media', $formdata->mediaid[$key], 'itemid, filepath, filename', false);
                    if (!empty($file)) {
                        $DB->update_record("ableplayer_media", $media);
                    } else {
                        $DB->delete_records("ableplayer_media", array('id' => $media->id));
                    }
                } else {
                    $media = new stdClass();
                    $media->ableplayerid = $formdata->instance;
                    $mediaid = $DB->insert_record("ableplayer_media", $media, true);
                    // Storage of files from the filemanager (media).
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'media',
                            $mediaid
                        );
                    }
                }
            }
        }
        if (!empty($formdata->desc)) {
            foreach ($formdata->desc as $key => $value) {
                if (isset($formdata->descid[$key]) && !empty($formdata->descid[$key])) {//existing choice record
                    $desc = new stdClass();
                    $desc->ableplayerid = $formdata->instance;
                    $desc->id = $formdata->descid[$key];
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'desc',
                            $desc->id
                        );
                    }
                    $fs = get_file_storage();
                    $file = $fs->get_area_files($this->context->id, 'mod_ableplayer', 'desc', $formdata->descid[$key], 'itemid, filepath, filename', false);
                    if (!empty($file)) {
                        $DB->update_record("ableplayer_desc", $desc);
                    } else {
                        $DB->delete_records("ableplayer_desc", array('id' => $desc->id));
                    }
                } else {
                    $desc = new stdClass();
                    $desc->ableplayerid = $formdata->instance;
                    $descid = $DB->insert_record("ableplayer_desc", $desc, true);
                    // Storage of files from the filemanager (desc).
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'desc',
                            $descid
                        );
                    }
                }
            }
        }
        if (!empty($formdata->caption)) {
            foreach ($formdata->caption as $key => $value) {
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
                            'caption',
                            $caption->id
                        );
                    }
                    $fs = get_file_storage();
                    $filex = $fs->get_area_files($this->context->id, 'mod_ableplayer', 'caption', $formdata->captionid[$key], 'itemid, filepath, filename', false);
                    if (!empty($filex)) {
                        $DB->update_record("ableplayer_caption", $caption);
                    } else {
                        $DB->delete_records("ableplayer_caption", array('id' => $caption->id));
                    }
                } else {
                    $caption = new stdClass();
                    $caption->ableplayerid = $formdata->instance;
                    $caption->label = $formdata->label[$key];
                    $caption->kind = $formdata->kind[$key];
                    $caption->srclang = $formdata->srclang[$key];
                    $captionid = $DB->insert_record("ableplayer_caption", $caption, true);
                    // Storage of files from the filemanager (captions).
                    $draftitemid = $value;
                    if ($draftitemid) {
                        file_save_draft_area_files(
                            $draftitemid,
                            $this->context->id,
                            'mod_ableplayer',
                            'caption',
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
     * @return string The module name (ableplayer)
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
     * @return string The module name plural (ableplayers)
     */
    protected function get_module_name_plural() {
        if (isset(self::$modulenameplural)) {
            return self::$modulenameplural;
        }
        self::$modulenameplural = get_string('modulenameplural', 'ableplayer');
        return self::$modulenameplural;
    }
    /**
     * Has this ableplayer been constructed from an instance?
     *
     * @return bool
     */
    public function has_instance() {
        return $this->instance || $this->get_course_module();
    }
    /**
     * Get the settings for the current instance of this ableplayer.
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
            throw new coding_exception('Improper use of the ableplayer class. ' .
                'Cannot load the ableplayer record.');
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
            throw new coding_exception('Improper use of the ableplayer class. ' .
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
        $captions = $DB->get_records('ableplayer_caption', array('ableplayerid' => $id));
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
     * @return ableplayer_renderer
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
        // Storage of files from the filemanager (poster).
        $draftitemid = $formdata->poster;
        if ($draftitemid) {
            file_save_draft_area_files(
                $draftitemid,
                $this->context->id,
                'mod_ableplayer',
                'poster',
                0
            );
        }
    }
}