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
 * @author     T6nis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete ableplayer structure for backup, with file and id annotations
 */
class backup_ableplayer_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        // ableplayer user data is in the xmldata field in DB - anyway lets skip that

        // Define each element separated
        $ableplayer = new backup_nested_element('ableplayer', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'urltype', 'ableplayerfile', 'type', 'streamer', 'playlistposition', 
            'playlistsize', 'autostart', 'stretching', 'mute', 'controls', 
            'ableplayerrepeat', 'title', 'width', 'height', 'image', 'notes',
            'notesformat', 'captionsback', 'captionsfile', 'captionsfontsize', 
            'captionsstate'));

        // Build the tree

        // Define sources
        $ableplayer->set_source_table('ableplayer', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations

        // Define file annotations
        $ableplayer->annotate_files('mod_ableplayer', 'intro', null);
        $ableplayer->annotate_files('mod_ableplayer', 'notes', null);
        $ableplayer->annotate_files('mod_ableplayer', 'file', null);
        $ableplayer->annotate_files('mod_ableplayer', 'captionsfile', null);
        $ableplayer->annotate_files('mod_ableplayer', 'image', null);

        // Return the root element (ableplayer), wrapped into standard activity structure
        return $this->prepare_activity_structure($ableplayer);
    }
}
