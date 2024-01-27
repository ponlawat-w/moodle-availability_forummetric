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
    public function test_daterage() {
        $this->resetAfterTest(true);

        /** @var \mod_forum_generator $forumgenerator */
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');

        $course = $this->getDataGenerator()->create_course();
        $forum = $forumgenerator->create_instance(['course' => $course->id]);

        $postuser = $this->getDataGenerator()->create_user();
        $replyuser = $this->getDataGenerator()->create_user();

        $discussion = $forumgenerator->create_discussion(
            ['userid' => $postuser->id, 'course' => $course->id, 'forum' => $forum->id, 'timemodified' => mktime(12, 0, 0, 1, 26, 2024)]
        );
        $forumgenerator->create_post([
            'userid' => $replyuser->id,
            'discussion' => $discussion->id,
            'parent' => $discussion->id,
            'created' => mktime(12, 30, 0, 1, 26, 2024)
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser->id,
            'discussion' => $discussion->id,
            'parent' => $discussion->id,
            'created' => mktime(8, 0, 0, 1, 27, 2024)
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser->id,
            'discussion' => $discussion->id,
            'parent' => $discussion->id,
            'created' => mktime(10, 0, 0, 1, 27, 2024)
        ]);

        $info = new \core_availability\mock_info($course, $replyuser->id);

        $condition_none = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0
        ]);
        $this->assertEquals(3, $condition_none->getuservalue($replyuser->id, $info));

        $condition_from = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
            'fromdate' => mktime(0, 0, 0, 1, 27, 2024)
        ]);
        $this->assertEquals(2, $condition_from->getuservalue($replyuser->id, $info));

        $condition_to = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
            'todate' => mktime(23, 59, 59, 1, 26, 2024)
        ]);
        $this->assertEquals(1, $condition_to->getuservalue($replyuser->id, $info));

        $condition_range = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forum->id,
            'metric' => 'numreplies',
            'condition' => 'morethan',
            'value' => 0,
            'fromdate' => mktime(7, 0, 0, 1, 27, 2024),
            'todate' => mktime(9, 0, 0, 1, 27, 2024)
        ]);
        $this->assertEquals(1, $condition_range->getuservalue($replyuser->id, $info));
    }
}
