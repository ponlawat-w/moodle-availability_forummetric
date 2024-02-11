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
require_once(__DIR__ . '/../classes/engagement.php');

/**
 * Test Condition
 *
 * @package     availability_forummetric
 * @category    test
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition_test extends \advanced_testcase {
    /**
     * Get forum mocking generator.
     *
     * @return \mod_forum_generator
     */
    private function getforumgenerator() {
        return $this->getDataGenerator()->get_plugin_generator('mod_forum');
    }

    protected function setUp(): void {
        $forumgenerator = $this->getforumgenerator();

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Main Course']);
        $forum1 = $forumgenerator->create_instance(['course' => $course->id, 'name' => 'Forum 1']);
        $forum2 = $forumgenerator->create_instance(['course' => $course->id, 'name' => 'Forum 2']);

        $othercourse = $this->getDataGenerator()->create_course();
        $otherforum = $forumgenerator->create_instance(['course' => $othercourse->id]);

        $postuser = $this->getDataGenerator()->create_user(['username' => 'postuser', 'country' => 'TH']);
        $replyuser1 = $this->getDataGenerator()->create_user(['username' => 'replyuser1', 'country' => 'JP']);
        $replyuser2 = $this->getDataGenerator()->create_user(['username' => 'replyuser2', 'country' => 'ES']);

        $otherdiscussion = $forumgenerator->create_discussion(
            ['userid' => $postuser->id, 'course' => $othercourse->id, 'forum' => $otherforum->id]
        );
        $forumgenerator->create_post(['discussion' => $otherdiscussion->id, 'userid' => $replyuser1->id]);

        // The postuser has 1 unique day in each forum and 2 unique days in both forum.
        $discussion1in1 = $forumgenerator->create_discussion([
            'userid' => $postuser->id, 'course' => $course->id,
            'forum' => $forum1->id, 'timemodified' => mktime(12, 0, 0, 10, 1, 2023),
        ]);
        $discussion2in1 = $forumgenerator->create_discussion([
            'userid' => $postuser->id, 'course' => $course->id,
            'forum' => $forum1->id, 'timemodified' => mktime(12, 30, 0, 10, 1, 2023),
        ]);
        $discussion1in2 = $forumgenerator->create_discussion([
            'userid' => $postuser->id, 'course' => $course->id,
            'forum' => $forum2->id, 'timemodified' => mktime(12, 0, 0, 10, 2, 2023),
        ]);

        // The replyuser1 made 1 reply in forum1 and 2 replies in forum2.
        $post1in1in1 = $forumgenerator->create_post([
            'userid' => $replyuser1->id,
            'discussion' => $discussion1in1->id,
            'parent' => $discussion1in1->firstpost,
            'created' => mktime(12, 30, 0, 10, 2, 2023),
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser1->id,
            'discussion' => $discussion1in2->id,
            'parent' => $discussion1in2->firstpost,
            'created' => mktime(12, 30, 0, 10, 2, 2023),
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser1->id,
            'discussion' => $discussion1in2->id,
            'parent' => $discussion1in2->firstpost,
            'created' => mktime(13, 0, 0, 10, 2, 2023),
        ]);
        // At this point, replyuser1 has 1 unique day overall
        // postuser has 1 reply in forum1, and 2 replies in forum2
        // postuser has 1 interacted nationality.

        // The replyuser2 made 1 own reply to forum1, and 1 nested reply to replyuser1 in forum1.
        $forumgenerator->create_post([
            'userid' => $replyuser2->id,
            'discussion' => $discussion2in1->id,
            'parent' => $discussion2in1->firstpost,
            'created' => mktime(12, 0, 0, 10, 2, 2023),
        ]);
        $forumgenerator->create_post([
            'userid' => $replyuser2->id,
            'discussion' => $discussion1in1->id,
            'parent' => $post1in1in1->id,
            'created' => mktime(12, 30, 0, 10, 3, 2023),
        ]);
        // Finally:
        // forum1
        // .┣ discussion1 by postuser - day1
        // .┃   ┗ replyuser1 - day2
        // .┃       ┗ replyuser2 - day2
        // .┗ discussion2 by pu - day1
        // .    ┗ replyuser2 - day3
        // forum2
        // .┗ discussion1 by postuser - day2
        // .    ┣ replyuser1 - day2
        // .    ┗ replyuser1 - day2
        //
        // Summary:
        // F1/F2/ALL    reply   nation  days    ep2p    ettc    eteg
        // pu           0/0/0   2/1/2   1/1/2   0/0/0   0/0/0   0/0/0
        // ru1          1/2/3   2/1/2   1/1/1   1/2/2   1/1/1   1/1/1
        // ru2          2/0/2   2/0/2   2/0/2   1/0/1   1/0/1   1/0/1.
    }

    /**
     * Get course.
     *
     * @return stdClass
     */
    protected function getcourse() {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;
        return $DB->get_record('course', ['fullname' => 'Main Course'], '*', MUST_EXIST);
    }

    /**
     * Get username dict in the array of: [username => userid].
     *
     * @return array
     */
    protected function getusernamedict() {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;
        $users = $DB->get_records('user', [], '', 'id,username');
        $arr = [];
        foreach ($users as $user) {
            $arr[$user->username] = $user->id;
        }
        return $arr;
    }

    /**
     * Get forum dict in the array of: [forumname => forumid].
     *
     * @return array
     */
    protected function getforumnamedict() {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;
        $forums = $DB->get_records('forum', [], '', 'id,name');
        $arr = [];
        foreach ($forums as $forum) {
            $arr[$forum->name] = $forum->id;
        }
        return $arr;
    }

    /**
     * Assert metric value value.
     *
     * @param string $title Assert title.
     * @param stdClass $course Course object.
     * @param int $forumid Forum ID.
     * @param int $userid User ID.
     * @param string $metric Metric to test.
     * @param int $value Expected value.
     */
    protected function assertmetricvalueequals($title, $course, $forumid, $userid, $metric, $value) {
        $info = new \core_availability\mock_info($course, $userid);
        $condition1 = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forumid,
            'metric' => $metric,
            'condition' => 'morethan',
            'value' => $value - 1,
        ]);
        $uservalue = $condition1->getuservalue($userid, $info);
        $this->assertEquals($value, $uservalue, $title);
        $this->assertTrue(
            $condition1->is_available(false, $info, false, $userid),
            $title . ': ' . $condition1->get_description(true, false, $info)
        );
        $condition2 = new condition((object)[
            'type' => 'forummetric',
            'forum' => $forumid,
            'metric' => $metric,
            'condition' => 'lessthan',
            'value' => $value + 1,
        ]);
        $this->assertTrue(
            $condition2->is_available(false, $info, false, $userid),
            $title . ': ' . $condition2->get_description(true, false, $info)
        );
    }

    /**
     * Test number of replies.
     *
     * @covers \availability_forummetric\condition::is_availables
     */
    public function test_numreplies() {
        $this->resetAfterTest(true);

        $course = $this->getcourse();
        $forums = $this->getforumnamedict();
        $users = $this->getusernamedict();

        $this->assertmetricvalueequals(
            'postuser in Forum 1', $course, $forums['Forum 1'], $users['postuser'], 'numreplies', 0
        );
        $this->assertmetricvalueequals(
            'postuser in Forum 2', $course, $forums['Forum 2'], $users['postuser'], 'numreplies', 0
        );
        $this->assertmetricvalueequals(
            'postuser in all forums', $course, 0, $users['postuser'], 'numreplies', 0
        );
        $this->assertmetricvalueequals(
            'replyuser1 in Forum 1', $course, $forums['Forum 1'], $users['replyuser1'], 'numreplies', 1
        );
        $this->assertmetricvalueequals(
            'replyuser1 in Forum 2', $course, $forums['Forum 2'], $users['replyuser1'], 'numreplies', 2
        );
        $this->assertmetricvalueequals(
            'replyuser1 in all forums', $course, 0, $users['replyuser1'], 'numreplies', 3
        );
        $this->assertmetricvalueequals(
            'replyuser2 in Forum 1', $course, $forums['Forum 1'], $users['replyuser2'], 'numreplies', 2
        );
        $this->assertmetricvalueequals(
            'replyuser2 in Forum 2', $course, $forums['Forum 2'], $users['replyuser2'], 'numreplies', 0
        );
        $this->assertmetricvalueequals(
            'replyuser2 in all forums', $course, 0, $users['replyuser2'], 'numreplies', 2
        );
    }

    /**
     * Test number of nationalities.
     *
     * @covers \availability_forummetric\condition::is_availables
     */
    public function test_numnationalities() {
        $this->resetAfterTest(true);

        $course = $this->getcourse();
        $forums = $this->getforumnamedict();
        $users = $this->getusernamedict();

        $this->assertmetricvalueequals(
            'postuser in Forum 1', $course, $forums['Forum 1'], $users['postuser'], 'numnationalities', 2
        );
        $this->assertmetricvalueequals(
            'postuser in Forum 2', $course, $forums['Forum 2'], $users['postuser'], 'numnationalities', 1
        );
        $this->assertmetricvalueequals(
            'postuser in all forums', $course, 0, $users['postuser'], 'numnationalities', 2
        );
        $this->assertmetricvalueequals(
            'replyuser1 in Forum 1', $course, $forums['Forum 1'], $users['replyuser1'], 'numnationalities', 2
        );
        $this->assertmetricvalueequals(
            'replyuser1 in Forum 2', $course, $forums['Forum 2'], $users['replyuser1'], 'numnationalities', 1
        );
        $this->assertmetricvalueequals(
            'replyuser1 in all forums', $course, 0, $users['replyuser1'], 'numnationalities', 2
        );
        $this->assertmetricvalueequals(
            'replyuser2 in Forum 1', $course, $forums['Forum 1'], $users['replyuser2'], 'numnationalities', 2
        );
        $this->assertmetricvalueequals(
            'replyuser2 in Forum 2', $course, $forums['Forum 2'], $users['replyuser2'], 'numnationalities', 0
        );
        $this->assertmetricvalueequals(
            'replyuser2 in all forums', $course, 0, $users['replyuser2'], 'numnationalities', 2
        );
    }

    /**
     * Test number of unique active days.
     *
     * @covers \availability_forummetric\condition::is_availables
     */
    public function test_uniquedaysactive() {
        $this->resetAfterTest(true);

        $course = $this->getcourse();
        $forums = $this->getforumnamedict();
        $users = $this->getusernamedict();

        $this->assertmetricvalueequals(
            'postuser in Forum 1', $course, $forums['Forum 1'], $users['postuser'], 'uniquedaysactive', 1
        );
        $this->assertmetricvalueequals(
            'postuser in Forum 2', $course, $forums['Forum 2'], $users['postuser'], 'uniquedaysactive', 1
        );
        $this->assertmetricvalueequals(
            'postuser in all forums', $course, 0, $users['postuser'], 'uniquedaysactive', 2
        );
        $this->assertmetricvalueequals(
            'replyuser1 in Forum 1', $course, $forums['Forum 1'], $users['replyuser1'], 'uniquedaysactive', 1
        );
        $this->assertmetricvalueequals(
            'replyuser1 in Forum 2', $course, $forums['Forum 2'], $users['replyuser1'], 'uniquedaysactive', 1
        );
        $this->assertmetricvalueequals(
            'replyuser1 in all forums', $course, 0, $users['replyuser1'], 'uniquedaysactive', 1
        );
        $this->assertmetricvalueequals(
            'replyuser2 in Forum 1', $course, $forums['Forum 1'], $users['replyuser2'], 'uniquedaysactive', 2
        );
        $this->assertmetricvalueequals(
            'replyuser2 in Forum 2', $course, $forums['Forum 2'], $users['replyuser2'], 'uniquedaysactive', 0
        );
        $this->assertmetricvalueequals(
            'replyuser2 in all forums', $course, 0, $users['replyuser2'], 'uniquedaysactive', 2
        );
    }

    /**
     * Test engagement value.
     *
     * @covers \availability_forummetric\condition::is_availables
     */
    public function test_engagement() {
        $this->resetAfterTest(true);

        $course = $this->getcourse();
        $forums = $this->getforumnamedict();
        $users = $this->getusernamedict();

        $metric = 'maxengagement_' . \availability_forummetric\engagement::PERSON_TO_PERSON;
        $this->assertmetricvalueequals(
            '(p2p) postuser in Forum 1', $course, $forums['Forum 1'], $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(p2p) postuser in Forum 2', $course, $forums['Forum 2'], $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(p2p) postuser in all forums', $course, 0, $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(p2p) replyuser1 in Forum 1', $course, $forums['Forum 1'], $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(p2p) replyuser1 in Forum 2', $course, $forums['Forum 2'], $users['replyuser1'], $metric, 2
        );
        $this->assertmetricvalueequals(
            '(p2p) replyuser1 in all forums', $course, 0, $users['replyuser1'], $metric, 2
        );
        $this->assertmetricvalueequals(
            '(p2p) replyuser2 in Forum 1', $course, $forums['Forum 1'], $users['replyuser2'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(p2p) replyuser2 in Forum 2', $course, $forums['Forum 2'], $users['replyuser2'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(p2p) replyuser2 in all forums', $course, 0, $users['replyuser2'], $metric, 1
        );

        $metric = 'maxengagement_' . \availability_forummetric\engagement::THREAD_TOTAL_COUNT;
        $this->assertmetricvalueequals(
            '(threadtotal) postuser in Forum 1', $course, $forums['Forum 1'], $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadtotal) postuser in Forum 2', $course, $forums['Forum 2'], $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadtotal) postuser in all forums', $course, 0, $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadtotal) replyuser1 in Forum 1', $course, $forums['Forum 1'], $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadtotal) replyuser1 in Forum 2', $course, $forums['Forum 2'], $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadtotal) replyuser1 in all forums', $course, 0, $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadtotal) replyuser2 in Forum 1', $course, $forums['Forum 1'], $users['replyuser2'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadtotal) replyuser2 in Forum 2', $course, $forums['Forum 2'], $users['replyuser2'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadtotal) replyuser2 in all forums', $course, 0, $users['replyuser2'], $metric, 1
        );

        $metric = 'maxengagement_' . \availability_forummetric\engagement::THREAD_ENGAGEMENT;
        $this->assertmetricvalueequals(
            '(threadeng) postuser in Forum 1', $course, $forums['Forum 1'], $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadeng) postuser in Forum 2', $course, $forums['Forum 2'], $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadeng) postuser in all forums', $course, 0, $users['postuser'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadeng) replyuser1 in Forum 1', $course, $forums['Forum 1'], $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadeng) replyuser1 in Forum 2', $course, $forums['Forum 2'], $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadeng) replyuser1 in all forums', $course, 0, $users['replyuser1'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadeng) replyuser2 in Forum 1', $course, $forums['Forum 1'], $users['replyuser2'], $metric, 1
        );
        $this->assertmetricvalueequals(
            '(threadeng) replyuser2 in Forum 2', $course, $forums['Forum 2'], $users['replyuser2'], $metric, 0
        );
        $this->assertmetricvalueequals(
            '(threadeng) replyuser2 in all forums', $course, 0, $users['replyuser2'], $metric, 1
        );
    }
}
