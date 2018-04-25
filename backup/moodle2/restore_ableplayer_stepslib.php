<?php
// This file is part of ableplayer module for Moodle - http://moodle.org/
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
 * @package    mod_ableplayer
 * @author     Tõnis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one ableplayer activity
 */
class restore_ableplayer_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('ableplayer', '/activity/ableplayer');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_ableplayer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the ableplayer record
        $newitemid = $DB->insert_record('ableplayer', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add ableplayer related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_ableplayer', 'intro', null);
        $this->add_related_files('mod_ableplayer', 'notes', null);
        $this->add_related_files('mod_ableplayer', 'file', null);
        $this->add_related_files('mod_ableplayer', 'captionsfile', null);
        $this->add_related_files('mod_ableplayer', 'image', null);
    }
}
