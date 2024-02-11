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

namespace availability_forummetric;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/fixtures/mock_info.php');

/**
 * Test Date Range
 *
 * @package     availability_forummetric
 * @category    test
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class daterange_test extends \advanced_testcase {

    /**
     * @covers \availability_forummetric\condition::is_availables
     */
    public function test_daterage() {
        $this->resetAfterTest(true);

        /** @var \mod_forum_generator $forumgenerator */
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        $course = $this->getDataGenerator()->create_course();
        $forum = $forumgenerator->create_instance(['course' => $course->id]);

        $postuser = $this->getDataGenerator()->create_user();
        $replyuser = $this->getDataGenerator()->create_user();

        $discussion = $forumgenerator->create_discussion([
            'userid' => $postuser->id, 'course' => $course->id,
            'forum' => $forum->id, 'timemodified' => mktime(12, 0, 0, 1, 26, 2024),
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser->id,
            'discussion' => $discussion->id,
            'parent' => $discussion->id,
            'created' => mktime(12, 30, 0, 1, 26, 2024),
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser->id,
            'discussion' => $discussion->id,
            'parent' => $discussion->id,
            'created' => mktime(8, 0, 0, 1, 27, 2024),
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser->id,
            'discussion' => $discussion->id,
            'parent' => $discussion->id,
            'created' => mktime(10, 0, 0, 1, 27, 2024),
        ]);

        $info = new \core_availability\mock_info($course, $replyuser->id);

        $conditionnone = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
        ]);
        $this->assertEquals(3, $conditionnone->getuservalue($replyuser->id, $info));

        $conditionfrom = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
            'fromdate' => (object)[
                'enabled' => true,
                'date' => '2024-01-27',
                'time' => '00:00:00',
            ],
        ]);
        $this->assertEquals(2, $conditionfrom->getuservalue($replyuser->id, $info));

        $conditionto = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
            'todate' => (object)[
                'enabled' => true,
                'date' => '2024-01-26',
                'time' => '23:59:59',
            ],
        ]);
        $this->assertEquals(1, $conditionto->getuservalue($replyuser->id, $info));

        $conditionrange = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
            'fromdate' => (object)[
                'enabled' => true,
                'date' => '2024-01-27',
                'time' => '07:00:00',
            ],
            'todate' => (object)[
                'enabled' => true,
                'date' => '2024-01-27',
                'time' => '09:00:00',
            ],
        ]);
        $this->assertEquals(1, $conditionrange->getuservalue($replyuser->id, $info));
    }
}
