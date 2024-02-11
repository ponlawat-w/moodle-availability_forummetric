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
 * Plugin strings are defined here.
 *
 * @package     availability_forummetric
 * @category    string
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Restriction by Forum Metric';
$string['title'] = 'Forum Metric';
$string['description'] = 'Allow only students who satisfy the specific metric value of forum modules in the course.';

$string['engagement_method'] = 'Engagement Method';
$string['engagement_method_help'] = '<p>Engagement Calculation Method</p><strong>Person-to-Person Engagement:</strong> The engagement level increases each time a user replies to the same user in the same thread.<br><strong>Thread Total Count Engagement:</strong> The engagement level increases each time a user participate in the same thread.<br><strong>Thread Engagement:</strong> The engagement level increases each time a user participates in a reply where they already participated in the parent posts.';
$string['engagement_persontoperson'] = 'Maximum Engagement Level (Person-to-Person)';
$string['engagement_persontoperson_description'] = 'The engagement level increases each time a user replies to the same user in the same thread.';
$string['engagement_threadtotalcount'] = 'Maximum Engagement Level (Thread Total Count)';
$string['engagement_threadtotalcount_description'] = 'The engagement level increases each time a user participate in the same thread.';
$string['engagement_threadengagement'] = 'Maximum Engagement Level (Thread)';
$string['engagement_threadengagement_description'] = 'The engagement level increases each time a user participates in a reply where they already participated in the parent posts.';

$string['availabilitydescription'] = '{$a->metric} in "{$a->forum}" must be {$a->condition} {$a->value}.';
$string['availabilitydescriptionfrom'] = '{$a->metric} from {$a->from} in "{$a->forum}" must be {$a->condition} {$a->value}.';
$string['availabilitydescriptionto'] = '{$a->metric} to {$a->to} in "{$a->forum}" must be {$a->condition} {$a->value}.';
$string['availabilitydescriptionbetween'] = '{$a->metric} between {$a->from} and {$a->to} in "{$a->forum}" must be {$a->condition} {$a->value}.';
$string['notavailabilitydescription'] = '{$a->metric} in "{$a->forum}" must not be {$a->condition} {$a->value}.';
$string['notavailabilitydescriptionfrom'] = '{$a->metric} from {$a->from} in "{$a->forum}" must not be {$a->condition} {$a->value}.';
$string['notavailabilitydescriptionto'] = '{$a->metric} to {$a->to} in "{$a->forum}" must not be {$a->condition} {$a->value}.';
$string['notavailabilitydescriptionbetween'] = '{$a->metric} between {$a->from} and {$a->to} in "{$a->forum}" must not be {$a->condition} {$a->value}.';

$string['allforums'] = 'All forums';

$string['lessthan'] = 'less than';
$string['morethan'] = 'more than';
$string['fromdate'] = 'From date';
$string['todate'] = 'To date';

$string['numreplies'] = 'Number of replies';
$string['numnationalities'] = 'Number of nationalities engaged';
$string['uniquedaysactive'] = 'Unique days active';
$string['maxengagement'] = 'Maximum Engagement Level';
