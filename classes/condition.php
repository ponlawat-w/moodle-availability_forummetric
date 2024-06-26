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

use core_date;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/engagement.php');

/**
 * Forum metric availability condition.
 *
 * Module availability by metric values from forum modules.
 */
class condition extends \core_availability\condition {
    /** @var bool $valid Is valid. */
    protected $valid = false;
    /** @var int $forum Forum ID to be source of conditional value. */
    protected $forum = null;
    /** @var string $metric Metric name. */
    protected $metric = null;
    /** @var string $engagementmethod Engagement method. */
    protected $engagementmethod = null;
    /** @var bool $engagementinternational International engagement only. */
    protected $engagementinternational = null;
    /** @var string $condition Checking condition. */
    protected $condition = null;
    /** @var string $value Required value. */
    protected $value = null;
    /** @var int|null $fromdate Timestamp of the start date. */
    protected $fromdate = null;
    /** @var int|null $to Timestamp of the end date. */
    protected $todate = null;

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
            if (str_starts_with($this->metric, 'maxengagement_')) {
                $engagements = explode('_', $this->metric);
                $this->metric = 'maxengagement';
                $this->engagementmethod = (int)$engagements[1];
                $this->engagementinternational = isset($structure->engagementinternational) ?
                    ($structure->engagementinternational ? true : false) : false;
            }
        }
        if (isset($structure->condition)) {
            $this->condition = $structure->condition;
        }
        if (isset($structure->value)) {
            $this->value = $structure->value;
        }
        $this->fromdate = isset($structure->fromdate) && !is_null($structure->fromdate) ?
            $this->get_structure_timestamp($structure->fromdate) : null;
        $this->todate = isset($structure->todate) && !is_null($structure->todate) ?
            $this->get_structure_timestamp($structure->todate) : null;

        $this->valid = !is_null($this->forum) && !is_null($this->metric) && !is_null($this->condition) && !is_null($this->value);
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param \core_availability\info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        if (!$this->valid) {
            return false;
        }
        $uservalue = $this->getuservalue($userid, $info);
        if (is_null($uservalue)) {
            return false;
        }
        $satisfies = false;
        switch ($this->condition) {
            case 'morethan':
                $satisfies = $uservalue > $this->value;
                break;
            case 'lessthan':
                $satisfies = $uservalue < $this->value;
                break;
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
        global $DB;
        /** @var \moodle_database $DB */
        $DB;
        $datetype = '';
        if (!is_null($this->fromdate) && is_null($this->todate)) {
            $datetype = 'from';
        } else if (is_null($this->fromdate) && !is_null($this->todate)) {
            $datetype = 'to';
        } else if (!is_null($this->fromdate) && !is_null($this->todate)) {
            $datetype = 'between';
        }
        $strname = ($not ? 'notavailabilitydescription' : 'availabilitydescription') . $datetype;

        $metricname = $this->metric;
        if ($this->metric === 'maxengagement' && $this->engagementinternational) {
            $metricname = 'maxinternationalengagement';
        }

        return get_string($strname, 'availability_forummetric', [
            'metric' => get_string($metricname, 'availability_forummetric'),
            'forum' => $this->forum > 0 ?
                $DB->get_record('forum', ['id' => $this->forum], 'name')->name
                : get_string('allforums', 'availability_forummetric'),
            'condition' => get_string($this->condition, 'availability_forummetric'),
            'value' => $this->value,
            'from' => is_null($this->fromdate) ? 'N/A' : userdate($this->fromdate),
            'to' => is_null($this->todate) ? 'N/A' : userdate($this->todate),
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
     * Get structure timestamp
     *
     * @param \stdClass $obj
     * @return int|null
     */
    protected function get_structure_timestamp($obj) {
        if (!isset($obj->enabled) || is_null($obj->enabled) || !$obj->enabled) {
            return null;
        }
        if (!isset($obj->date) || is_null($obj->date)) {
            return null;
        }
        $date = $obj->date;
        $time = isset($obj->time) && !is_null($obj->time) ? $obj->time : '00:00:00';

        $dt = new \DateTime($date . ' ' . $time, core_date::get_user_timezone_object());
        return $dt->getTimestamp();
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
            'value' => $this->value,
        ];
    }

    /**
     * Get user's value.
     *
     * @param int $userid
     * @param \core_availability\info $info
     * @return int|null
     */
    public function getuservalue($userid, \core_availability\info $info) {
        switch ($this->metric) {
            case 'numreplies':
                return $this->getnumreplies($userid, $info);
            case 'numnationalities':
                return $this->getnumnationalities($userid, $info);
            case 'uniquedaysactive':
                return $this->getuniquedaysactive($userid, $info);
            case 'maxengagement':
                return $this->getmaxengagement($userid, $info);
            default:
                return null;
        }
        return null;
    }

    /**
     * Get date range SQL
     *
     * @return array{ 0: string, 1: int[] }
     */
    protected function getdaterangesql() {
        if (is_null($this->fromdate) && is_null($this->todate)) {
            return ['TRUE', []];
        }
        if (!is_null($this->fromdate) && is_null($this->todate)) {
            return ['created >= ?', [$this->fromdate]];
        }
        if (is_null($this->fromdate) && !is_null($this->todate)) {
            return ['created <= ?', [$this->todate]];
        }
        return ['created BETWEEN ? AND ?', [$this->fromdate, $this->todate]];
    }

    /**
     * Get number of replies.
     *
     * @param int $userid
     * @param \core_availability\info $info
     * @return int|null
     */
    protected function getnumreplies($userid, \core_availability\info $info) {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;

        [$daterangesql, $daterangeparams] = $this->getdaterangesql();

        $record = $DB->get_record_sql(
            'SELECT COUNT(*) replies FROM {forum_posts} WHERE userid = ? AND parent > 0 AND discussion IN ('
            . 'SELECT id FROM {forum_discussions} WHERE forum = ? OR (0 = ? AND forum IN (SELECT id FROM {forum} WHERE course = ?))'
            . ') AND ' . $daterangesql,
            array_merge([$userid, $this->forum, $this->forum, $info->get_course()->id], $daterangeparams)
        );
        return $record ? $record->replies : null;
    }

    /**
     * Get number of nationalities.
     *
     * @param int $userid
     * @param \core_availability\info $info
     * @return int|null
     */
    protected function getnumnationalities($userid, \core_availability\info $info) {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;

        [$daterangesql, $daterangeparams] = $this->getdaterangesql();

        $record = $DB->get_record_sql('SELECT COUNT(DISTINCT country) countrycount FROM {user} WHERE id IN ('
            . "SELECT userid FROM {forum_posts} WHERE userid != ? AND {$daterangesql} AND discussion IN ("
            . 'SELECT id FROM {forum_discussions} fd WHERE (fd.forum = ? OR (0 = ? AND fd.forum IN ('
            . 'SELECT id FROM {forum} WHERE course = ?'
            . '))) AND EXISTS ('
            . 'SELECT 1 FROM {forum_posts} fp WHERE fp.discussion = fd.id AND fp.userid = ?'
            . ')))',
            array_merge([$userid], $daterangeparams, [$this->forum, $this->forum, $info->get_course()->id, $userid])
        );
        return $record ? $record->countrycount : null;
    }

    /**
     * Get number of unique active days.
     *
     * @param int $userid
     * @param \core_availability\info $info
     * @return int|null
     */
    protected function getuniquedaysactive($userid, \core_availability\info $info) {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;

        [$daterangesql, $daterangeparams] = $this->getdaterangesql();

        $record = $DB->get_record_sql(
            'SELECT COUNT(DISTINCT (created - (created % 86400))) uniquedaysactivecount '
            . 'FROM {forum_posts} WHERE userid = ? AND discussion IN ('
            . 'SELECT id FROM {forum_discussions} WHERE forum = ? OR (0 = ? AND forum IN ('
            . 'SELECT id FROM {forum} WHERE course = ?'
            . '))) AND ' . $daterangesql,
            array_merge([$userid, $this->forum, $this->forum, $info->get_course()->id], $daterangeparams)
        );
        return $record ? $record->uniquedaysactivecount : null;
    }

    /**
     * Get number of maximum engagement.
     *
     * @param int $userid
     * @param \core_availability\info $info
     * @return int
     */
    protected function getmaxengagement($userid, \core_availability\info $info) {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;

        $startdate = is_null($this->fromdate) ? $this->fromdate : 0;
        $enddate = is_null($this->todate) ? $this->todate : 0;

        $discussions = $DB->get_records_sql(
            'SELECT id FROM {forum_discussions} WHERE forum = ? OR (0 = ? AND forum IN (SELECT id FROM {forum} WHERE course = ?))',
            [$this->forum, $this->forum, $info->get_course()->id]
        );
        $engagementresult = new engagementresult();
        foreach ($discussions as $discussion) {
            $engagement = engagement::getinstancefrommethod(
                $this->engagementmethod,
                $discussion->id,
                $startdate, $enddate,
                $this->engagementinternational
            );
            $engagementresult->add($engagement->calculate($userid));
        }
        $max = $engagementresult->getmax();
        return $max ? $max : 0;
    }
}
