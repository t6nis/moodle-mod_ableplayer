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
