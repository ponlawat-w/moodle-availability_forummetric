<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Forum metric frontend
 *
 * @package     availability_forummetric
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_forummetric;

defined('MOODLE_INTERNAL') or die();

include_once(__DIR__ . '/engagement.php');

class frontend extends \core_availability\frontend {
    protected function get_javascript_strings() {
        return [
            'allforums',
            'lessthan',
            'morethan'
        ];
    }

    /**
     * @return array
     */
    protected function get_metricoptions() {
        $options = [
            ['metric' => 'numreplies', 'name' => get_string('numreplies', 'availability_forummetric')],
            ['metric' => 'numnationalities', 'name' => get_string('numnationalities', 'availability_forummetric')]
        ];

        foreach (engagement::getselectoptions() as $metric => $name) {
            $options[] = ['metric' => 'maxengagement_' . $metric, 'name' => $name];
        }

        return $options;
    }

    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        /**
         * @var \moodle_daabase $DB
         */
        global $DB;
        $forums = $DB->get_records('forum', ['course' => $course->id], 'name ASC, id ASC', 'id, name');
        $arr = [];
        foreach ($forums as $forum) {
            $arr[] = $forum;
        }
        return [$this->get_metricoptions(), $arr];
    }
}
