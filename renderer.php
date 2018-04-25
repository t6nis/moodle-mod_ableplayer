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
 * Ableplayer module renderering methods are defined here.
 *
 * @package    mod_ableplayer
 * @author     T6nis Tartes <tonis.tartes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ableplayer/locallib.php');

/**
 * ableplayer module renderer class
 */
class mod_ableplayer_renderer extends plugin_renderer_base {

    /**
     * Render the ableplayer page
     *
     * @param ableplayer
     * @return string The page output.
     */
    public function ableplayer_page($ableplayer_media) {
        $output = '';

        $output .= $this->ableplayer($ableplayer_media);

        return $output;
    }
    /**
     * Utility function for getting a file URL
     *
     * @param stored_file $file
     * @return string file url
     */
    private function util_get_file_url($file) {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false);
    }
    /**
     * Utility function for getting area files
     *
     * @param int $contextid
     * @param string $areaname file area name (e.g. "videos")
     * @return array of stored_file objects
     */
    private function util_get_area_files($contextid, $areaname) {
        $fs = get_file_storage();
        return $fs->get_area_files($contextid,
            'mod_ableplayer',
            $areaname,
            false,
            'itemid, filepath, filename',
            false);
    }
    /**
     * Utility function for getting the captions
     *
     * @param int $contextid
     * @return url to the poster image (or the default image)
     */
    private function get_captions($contextid) {
        $captions = array();
        $captions_files = $this->util_get_area_files($contextid, 'caption');
        foreach ($captions_files as $file) {
            $captions[] = $file;
        }

        return $captions;
    }
    /**
     * Utility function for getting the descs
     *
     * @param int $contextid
     * @return url to the poster image (or the default image)
     */
    private function get_descs($contextid) {
        $captions = array();
        $captions_files = $this->util_get_area_files($contextid, 'desc');
        foreach ($captions_files as $file) {
            $captions[] = $file;
        }

        return $captions;
    }
    /**
     * Utility function for getting the video poster image
     *
     * @param int $contextid
     * @return url to the poster image (or the default image)
     */
    private function get_poster_image($contextid) {
        $posterurl = null;
        $poster = $this->util_get_area_files($contextid, 'poster');
        foreach ($poster as $file) {
            $posterurl = $this->util_get_file_url($file);
            break;  // Only one poster allowed.
        }

        return $posterurl;
    }
    /**
     * Utility function for creating the video source elements HTML.
     *
     * @param int $contextid
     * @return string HTML
     */
    private function get_ableplayer_html($contextid, $captions_settings, $ableplayer) {
        $output = '';

        $videos = $this->util_get_area_files($contextid, 'media');
        $posterurl = $this->get_poster_image($contextid);
        $captions = $this->get_captions($contextid);
        $desc = $this->get_descs($contextid);
        $videoscnt = count($videos);

        if ($videoscnt > 1) {
            $sorted_arr = array();
            $i = 0;
            foreach ($videos as $file) {
                if ($mimetype = $file->get_mimetype()) {
                    $mimetag = explode('/', $mimetype);
                    if (!empty($captions[$i])) {
                        $sorted_arr[$mimetag[0]][$i]['caption'] = $captions[$i];
                    }
                    $sorted_arr[$mimetag[0]][$i]['file'] = $file;
                    $i++;
                }
            }

            // General settings.
            $options = array(
                'data-able-player' => '',
                'preload' => 'auto',
                'width' => 'auto',
                'height' => 'auto',
            );
            if (!empty($ableplayer->mode)) {
                $options[$ableplayer->mode] = '';
            }
            if (!empty($ableplayer->lang)) {
                $options['data-lang'] = $ableplayer->lang;
                $options['data-force-lang'] = '';
            }
            
            if (!empty($sorted_arr['video'])) {
                $options['id'] = 'ableplayer_video';
                $options['poster'] = $posterurl;
                $output .= html_writer::start_tag(
                    'video',
                    $options
                );

                // Playlist?
                if ($ableplayer->playlist == 1) {
                    foreach ($sorted_arr['video'] as $key => $value) {
                        if (!empty($value['caption'])) {
                            $output .= $this->get_captions_html($contextid, $value['caption'], $captions_settings);
                        }
                    }
                    $output .= html_writer::end_tag('video'); // IF playlist end video tag early
                    $output .= html_writer::empty_tag('ul', array(
                        'class' => 'able-playlist',
                        'data-player' => 'ableplayer_video',
                        'data-embedded' => ''
                    ));
                    foreach ($sorted_arr['video'] as $key => $value) {
                        $videourl = $this->util_get_file_url($value['file']);
                        $mimtype = explode('/', $value['file']->get_mimetype());
                        $output .= html_writer::empty_tag(
                            'li',
                            array('data-' . $mimtype[1] => $videourl,
                                'class' => 'data-' . $mimtype[0])
                        );
                        $output .= $value['file']->get_filename();
                        $output .= html_writer::end_tag('li');
                    }
                    $output .= html_writer::end_tag('ul');
                } else {
                    $i = 0;
                    foreach ($videos as $file) {
                        if ($mimetype = $file->get_mimetype()) {
                            $videourl = $this->util_get_file_url($file);
                            $source_opts = array(
                                'src' => $videourl,
                                'type' => $mimetype
                            );
                            if (!empty($desc[$i])) {
                                $source_opts['data-desc-src'] = $this->util_get_file_url($desc[$i]);
                            }
                            $output .= html_writer::empty_tag(
                                'source',
                                $source_opts
                                );
                            $i++;
                        }
                    }
                    foreach ($sorted_arr['video'] as $key => $value) {
                        if (!empty($value['caption'])) {
                            $output .= $this->get_captions_html($contextid, $value['caption'], $captions_settings);
                        }
                    }
                    $output .= html_writer::end_tag('video');
                }
            }
            if (!empty($sorted_arr['audio'])) {
                $options['id'] = 'ableplayer_audio';
                $output .= html_writer::start_tag(
                    'audio',
                    $options
                );
                foreach ($sorted_arr['audio'] as $key => $value) {
                    if (!empty($value['caption'])) {
                        $output .= $this->get_captions_html($contextid, $value['caption'], $captions_settings);
                    }
                }
                $output .= html_writer::end_tag('audio');
                // Audios
                $output .= html_writer::empty_tag('ul', array(
                    'class' => 'able-playlist',
                    'data-player' => 'ableplayer_audio',
                    'data-embedded' => ''
                ));
                foreach ($sorted_arr['audio'] as $key => $value) {
                    $videourl = $this->util_get_file_url($value['file']);
                    $mimtype = explode('/', $value['file']->get_mimetype());
                    $output .= html_writer::empty_tag(
                        'li',
                        array('data-' . $mimtype[1] => $videourl,
                            'class' => 'data-' . $mimtype[0])
                    );
                    $output .= $value['file']->get_filename();
                    $output .= html_writer::end_tag('li');
                }
                $output .= html_writer::end_tag('ul');
            }
        } else {
            $options = array('id' => 'ableplayer',
                'data-able-player' => '',
                //'data-transcript-div' => 'transcript-placeholder',
                'preload' => 'auto',
                'width' => 'auto',
                'height' => 'auto',
                'poster' => $posterurl,
            );
            if (!empty($ableplayer->mode)) {
                $options[$ableplayer->mode] = '';
            }
            if (!empty($ableplayer->lang)) {
                $options['data-lang'] = $ableplayer->lang;
                $options['data-force-lang'] = '';
            }
            $output .= html_writer::start_tag(
                'video',
                $options
            );

            // Descriptive file url
            $descurl = '';
            $desc = $this->util_get_area_files($contextid, 'desc');
            foreach ($desc as $file) {
                if ($mimetype = $file->get_mimetype()) {
                    $descurl = $this->util_get_file_url($file);
                }
            }

            foreach ($videos as $file) {
                if ($mimetype = $file->get_mimetype()) {
                    $videourl = $this->util_get_file_url($file);
                    $output .= html_writer::empty_tag(
                        'source',
                        array('src' => $videourl,
                            'type' => $mimetype,
                            'data-desc-src' => $descurl)
                    );
                }
            }
            if (!empty($captions)) {
                foreach ($captions as $key => $value) {
                    if (!empty($value)) {
                        $output .= $this->get_captions_html($contextid, $value, $captions_settings);
                    }
                }
            }
            $output .= html_writer::end_tag('video');
        }
        return $output;
    }
    /**
     * Utility function for creating the video caption track elements
     * HTML.
     *
     * @param int $contextid
     * @return string HTML
     */
    private function get_captions_html($contextid, $file, $captions_settings) {
        $output = '';

        if ($mimetype = $file->get_mimetype()) {
            $captionurl = $this->util_get_file_url($file);
            // Get or construct caption label for video.js player.
            $filename = $file->get_filename();
            $dot = strrpos($filename, '.');
            if ($dot) {
                $label = substr($filename, 0, $dot);
            } else {
                $label = $filename;
            }
            // Perhaps filename is a three letter ISO 6392 language code (e.g. eng, swe)?
            if (preg_match('/^[a-z]{3}$/', $label)) {
                $maybelabel = get_string($label, 'core_iso6392');

                /* Strings not in language files come back as [[string]], don't
                   use those for labels. */
                if (substr($maybelabel, 0, 2) !== '[[' ||
                    substr($maybelabel, -2, 2) === ']]') {
                    $label = $maybelabel;
                }
            }

            $options = array();
            $options['kind'] = 'captions';
            $options['src'] = $captionurl;
            if (in_array($file->get_itemid(), array_keys($captions_settings))) {
                $itemid = $file->get_itemid();
                if (!empty($captions_settings[$itemid]->kind)) {
                    $options['kind'] = $captions_settings[$itemid]->kind;
                }
                $label = (!empty($captions_settings[$itemid]->label) ? $captions_settings[$itemid]->label : $label);
                if (!empty($captions_settings[$itemid]->srclang)) {
                    $options['srclang'] = $captions_settings[$itemid]->srclang;
                }
            }
            $options['label'] = $label;

            // Track seems to need closing tag in IE9 (!).
            $output .= html_writer::tag('track', '', $options);
        }
        return $output;
    }
    /**
     * Renders ableplayer video.
     *
     * @param ableplayer $videofile
     * @return string HTML
     */
    public function ableplayer(ableplayer $ableplayer_media) {
        $output  = '';

        $contextid = $ableplayer_media->get_context()->id;
        $captions_settings = $ableplayer_media->get_captions_settings($ableplayer_media->get_instance()->id);
        $ableplayer = $ableplayer_media->get_instance();

        $output .= $this->get_ableplayer_html($contextid, $captions_settings, $ableplayer);

        return $output;
    }
}