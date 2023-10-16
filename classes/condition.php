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
 * Forum metric condition
 *
 * @package     availability_forummetric
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_forummetric;

use stdClass;

defined('MOODLE_INTERNAL') or die();

class condition extends \core_availability\condition {
    protected $valid = false;
    protected $forum = null;
    protected $metric = null;
    protected $condition = null;
    protected $value = null;

    /**
     * Constructor
     *
     * @param \stdClass $structure
     */
    public function __construct($structure) {
        if ($structure->type !== 'forummetric') {
            throw new \moodle_exception('Invalid type');
        }
        if (isset($structure->forum)) {
            $this->forum = $structure->forum;
        }
        if (isset($structure->metric)) {
            $this->metric = $structure->metric;
        }
        if (isset($structure->condition)) {
            $this->condition = $structure->condition;
        }
        if (isset($structure->value)) {
            $this->value = $structure->value;
        }

        $this->valid = !is_null($this->forum) && !is_null($this->metric) && !is_null($this->condition) && !is_null($this->value);
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        if (!$this->valid) return false;
        $uservalue = $this->getuservalue($userid);
        if (is_null($uservalue)) return false;
        $satisfies = false;
        switch ($this->condition) {
            case 'morethan': $satisfies = $uservalue > $this->value; break;
            case 'lessthan': $satisfies = $uservalue < $this->value; break;
        }
        return $not ? !$satisfies : $satisfies;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        /**
         * @var \moodle_database $DB
         */
        global $DB;
        return get_string($not ? 'notavailabilitydescription' : 'availabilitydescription', 'availability_forummetric', [
            'metric' => get_string($this->metric, 'availability_forummetric'),
            'forum' => $this->forum > 0 ? $DB->get_record('forum', ['id' => $this->forum], 'name')->name : get_string('allforums', 'availability_forummetric'),
            'condition' => get_string($this->condition, 'availability_forummetric'),
            'value' => $this->value
        ]);
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return 'DEBUG';
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return \stdClass Structure object (ready to be made into JSON format)
     */
    public function save() {
        return (object)[
            'type' => 'forummetric',
            'forum' => $this->forum,
            'metric' => $this->metric,
            'condition' => $this->condition,
            'value' => $this->value
        ];
    }

    /**
     * @param int $userid
     * @return int|null
     */
    protected function getuservalue($userid) {
        switch ($this->metric) {
            case 'numreplies': return $this->getnumreplies($userid);
            default: return null;
        }
        return null;
    }

    /**
     * @param int $userid
     * @return int|null
     */
    protected function getnumreplies($userid) {
        /**
         * @var \moodle_database $DB
         */
        global $DB;

        $record = $DB->get_record_sql(
            'SELECT COUNT(*) replies FROM {forum_posts} WHERE userid = ? AND parent > 0 AND (? = 0 OR discussion IN (SELECT id FROM {forum_discussions} WHERE forum = ?))',
            [$userid, $this->forum, $this->forum]
        );
        return $record ? $record->replies : null;
    }
}
