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
 * The main ableplayer configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage ableplayer
 * @author     Tõnis Tartes <tonis.tartes@gmail.com>
 * @copyright  2013 Tõnis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_ableplayer_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('ableplayername', 'ableplayer'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'ableplayername', 'ableplayer');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        //--------------------------------------- MEDIA SOURCE ----------------------------------------
        $mform->addElement('header', 'ableplayersource', get_string('ableplayersource', 'ableplayer'));

        // Medias file manager.
        $options = array('subdirs' => false,
            'maxbytes' => 0,
            'maxfiles' => -1,
            'accepted_types' => array('.mp4', '.webm', '.webv', '.ogg', '.ogv', '.oga', '.wav', '.mp3'));
        $mform->addElement(
            'filemanager',
            'medias',
            get_string('medias', 'ableplayer'),
            null,
            $options);
        $mform->addHelpButton('medias', 'medias', 'ableplayer');
        $mform->addRule('medias', null, 'required', null, 'client');

        // Posters file manager.
        $options = array('subdirs' => false,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => array('image'));
        $mform->addElement(
            'filemanager',
            'posters',
            get_string('posters', 'ableplayer'),
            null,
            $options);
        $mform->addHelpButton('posters', 'posters', 'ableplayer');

        // Captions file manager.
       /*$options = array('subdirs' => false,
            'maxbytes' => 0,
            'maxfiles' => -1,
            'accepted_types' => array('.vtt'));
        $mform->addElement(
            'filemanager',
            'captions',
            get_string('captions', 'ableplayer'),
            null,
            $options);
        $mform->addHelpButton('captions', 'captions', 'ableplayer');*/

        $repeatarray = array();
        $options = array('subdirs' => false,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => array('.vtt'));
        $repeatarray[] = $mform->createElement('header', 'ableplayercaptions', get_string('ableplayercaptions', 'ableplayer'));
        $repeatarray[] = $mform->createElement(
            'filemanager',
            'captions',
            get_string('captions', 'ableplayer'),
            null,
            $options);
        $repeatarray[] = $mform->createElement('text', 'title', get_string('title', 'ableplayer'));
        $repeatarray[] = $mform->createElement('hidden', 'captionid', 0);

        if ($this->_instance){
            $repeatno = $DB->count_records('ableplayer_captions', array('ableplayerid'=>$this->_instance));
        } else {
            $repeatno = 1;
        }

        $repeateloptions = array();
        /*$repeateloptions['limit']['default'] = 0;
        $repeateloptions['limit']['disabledif'] = array('limitanswers', 'eq', 0);
        $repeateloptions['limit']['rule'] = 'numeric';
        $repeateloptions['limit']['type'] = PARAM_INT;

        $repeateloptions['option']['helpbutton'] = array('choiceoptions', 'choice');
        $mform->setType('option', PARAM_CLEANHTML);
        */
        $mform->setType('title', PARAM_TEXT);
        $mform->setType('captionid', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno,
            $repeateloptions, 'ableplayercaptions_repeats', 'ableplayercaptions_add_fields', 1, null, true);


        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        global $DB;

        if ($this->current->instance) {
            $options = array('subdirs' => false,
                'maxbytes' => 0,
                'maxfiles' => -1);
            $draftitemid = file_get_submitted_draft_itemid('medias');
            file_prepare_draft_area($draftitemid,
                $this->context->id,
                'mod_ableplayer',
                'medias',
                0,
                $options);
            $default_values['medias'] = $draftitemid;

            $options = array('subdirs' => false,
                'maxbytes' => 0,
                'maxfiles' => 1);
            $draftitemid = file_get_submitted_draft_itemid('posters');
            file_prepare_draft_area($draftitemid,
                $this->context->id,
                'mod_ableplayer',
                'posters',
                0,
                $options);
            $default_values['posters'] = $draftitemid;

            $options = array('subdirs' => false,
                'maxbytes' => 0,
                'maxfiles' => -1);
            $captions = $DB->get_records('ableplayer_captions',array('ableplayerid'=>$this->_instance));
            foreach (array_values($captions) as $key => $value) {
                $draftitemid = file_get_submitted_draft_itemid('captions');
                file_prepare_draft_area($draftitemid,
                    $this->context->id,
                    'mod_ableplayer',
                    'captions',
                    $value->id,
                    $options);
                if ($draftitemid) {
                    $default_values['captions[' . $key . ']'] = $draftitemid;
                }
                $default_values['title['.$key.']'] = $value->title;
                $default_values['captionid['.$key.']'] = $value->id;
            }

            /*$options = array('subdirs' => false,
                'maxbytes' => 0,
                'maxfiles' => -1);
            $draftitemid = file_get_submitted_draft_itemid('captions');
            file_prepare_draft_area($draftitemid,
                $this->context->id,
                'mod_ableplayer',
                'captions',
                0,
                $options);
            $default_values['captions'] = $draftitemid;*/
        }
    }
}