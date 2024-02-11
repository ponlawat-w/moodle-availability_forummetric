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
 * Forum metric engagement tool.
 *
 * @package     availability_forummetric
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_forummetric;

defined('MOODLE_INTERNAL') || die;

/**
 * Class for engagement calculation methods
 */
class engagement {
    /** @var string Component. */
    private const COMPONENT = 'availability_forummetric';
    /** @var int Engagement method person-to-person. */
    public const PERSON_TO_PERSON = 1;
    /** @var int Engagement method thread total count.  */
    public const THREAD_TOTAL_COUNT = 2;
    /** @var int Engagement method thread engagement.  */
    public const THREAD_ENGAGEMENT = 3;

    /**
     * Get string of calculation method.
     *
     * @param string $method
     * @param string $suffix
     * @return string
     */
    private static function getstring($method, $suffix = '') {
        switch ($method) {
            case static::PERSON_TO_PERSON:
                return get_string('engagement_persontoperson' . $suffix, static::COMPONENT);
            case static::THREAD_TOTAL_COUNT:
                return get_string('engagement_threadtotalcount' . $suffix, static::COMPONENT);
            case static::THREAD_ENGAGEMENT:
                return get_string('engagement_threadengagement' . $suffix, static::COMPONENT);
        }
        throw new \moodle_exception('Invalid method');
    }

    /**
     * Get calculator function.
     *
     * @param int $method
     * @param int $discussionid
     * @param int $starttime
     * @param int $endtime
     * @return engagementcalculator
     */
    public static function getinstancefrommethod($method, $discussionid, $starttime = 0, $endtime = 0) {
        switch ($method) {
            case static::PERSON_TO_PERSON:
                return new p2pengagement($discussionid, $starttime, $endtime);
            case static::THREAD_TOTAL_COUNT:
                return new threadcountengagement($discussionid, $starttime, $endtime);
            case static::THREAD_ENGAGEMENT:
                return new threadengagement($discussionid, $starttime, $endtime);
        }
        throw new \moodle_exception('Invalid method');
    }

    /**
     * Get calculation method name.
     *
     * @param string $method
     * @return string
     */
    public static function getname($method) {
        return static::getstring($method);
    }

    /**
     * Get calculation method description.
     *
     * @param string $method
     * @return string
     */
    public static function getdescription($method) {
        return static::getstring($method, '_description');
    }

    /**
     * Get all available engagement calculation methods.
     *
     * @return int[]
     */
    public static function getallmethods() {
        return [
            static::PERSON_TO_PERSON,
            static::THREAD_TOTAL_COUNT,
            static::THREAD_ENGAGEMENT,
        ];
    }

    /**
     * Get select options for form.
     *
     * @return array
     */
    public static function getselectoptions() {
        $options = [];
        foreach (static::getallmethods() as $option) {
            $options[$option] = static::getname($option);
        }
        return $options;
    }

    /**
     * Add options to form.
     *
     * @param MoodleQuickForm $mform
     */
    public static function addtoform($mform, $elementname = 'engagementmethod', $defaultvalue = null) {
        $mform->addElement(
            'select', $elementname,
            get_string('engagement_method', self::COMPONENT),
            self::getselectoptions()
        );
        $mform->addHelpButton($elementname, 'engagement_method', self::COMPONENT);
        if (is_null($defaultvalue)) {
            $defaultvalue = get_config(static::COMPONENT, 'defaultengagementmethod');
        }
        $mform->setDefault($elementname, $defaultvalue);
    }
}

/**
 * A forum post.
 */
class engagedpost {
    /** @var int $id Post ID. */
    public $id;
    /** @var int $discussion Discussion ID. */
    public $discussion;
    /** @var int $parent Parent post ID. */
    public $parent;
    /** @var int $userid User ID. */
    public $userid;
    /** @var int $created Created timestamp. */
    public $created;
    /** @var bool $statisfiestime True if post satisfies time condition. */
    public $satisfiestime;
    /** @var engagedpost[] $children Children posts */
    public $children;

    /** @var string Out fields when getting posts. */
    public const DB_OUT_FIELDS = 'id,discussion,parent,userid,created';
}

/**
 * Engagement result
 */
class engagementresult {
    /**
     * Array [e1, e2, e3, e4].
     *
     * @var int[]
     */
    public $levels = [];

    /**
     * Increase level value by given amount or default to be 1.
     *
     * @param int $level
     * @param int $amount
     */
    public function increase($level, $amount = 1) {
        if (!isset($this->levels[$level])) {
            $this->levels[$level] = $amount;
            return;
        }
        $this->levels[$level] += $amount;
    }

    /**
     * Add another result to this result.
     *
     * @param engagementresult $result
     */
    public function add($result) {
        foreach ($result->levels as $level => $value) {
            $this->increase($level, $value);
        }
    }

    /**
     * Get engagement value of level.
     *
     * @param int $level.
     * @return int
     */
    public function getlevel($level) {
        return isset($this->levels[$level]) ? $this->levels[$level] : 0;
    }

    /**
     * Get engagement level 1.
     *
     * @return int
     */
    public function getl1() {
        return $this->getlevel(1);
    }

    /**
     * Get engagement level 2.
     *
     * @return int
     */
    public function getl2() {
        return $this->getlevel(2);
    }

    /**
     * Get engagement level 3.
     *
     * @return int
     */
    public function getl3() {
        return $this->getlevel(3);
    }

    /**
     * Get engagement level 4 up.
     *
     * @return int
     */
    public function getl4up() {
        $sum = 0;
        foreach ($this->levels as $level => $value) {
            if ($level < 4) {
                continue;
            }
            $sum += $value;
        }
        return $sum;
    }

    /**
     * Get maximum engagement.
     *
     * @return int
     */
    public function getmax() {
        return count($this->levels) > 0 ? max(array_keys($this->levels)) : null;
    }

    /**
     * Get average engagement.
     *
     * @return double
     */
    public function getaverage () {
        $sum = 0;
        $count = 0;
        foreach ($this->levels as $level => $value) {
            $sum += $level * $value;
            $count += $value;
        }
        return $count ? round($sum / $count, 2) : null;
    }
}

/**
 * Class for calculating engagement
 */
abstract class engagementcalculator {
    /** @var int $discussionid Discussion ID. */
    protected $discussionid;
    /** @var engagedpost[] $postsdict Key being post ID, value beinfg engagedposts. */
    protected $postsdict = [];
    /** @var int $firstpost ID of the first post. */
    protected $firstpost;
    /** @var int $starttime Start timestamp. */
    protected $starttime = 0;
    /** @var int $endtime End timestamp. */
    protected $endtime = 0;

    /**
     * Constructor
     *
     * @param int $discussionid
     * @param int $starttime
     * @param int $endtime
     */
    public function __construct($discussionid, $starttime = 0, $endtime = 0) {
        $this->discussionid = $discussionid;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->getposts();
        $this->initchildren();
        $this->checkpoststime();
    }

    /**
     * Get user IDs participated in the discussion
     *
     * @return int[]
     */
    public function getparticipants() {
        $results = [];
        foreach ($this->postsdict as $post) {
            if (!in_array($post->userid, $results)) {
                $results[] = $post->userid;
            }
        }
        return $results;
    }

    /**
     * Get posts from database
     */
    private function getposts() {
        global $DB;
        $posts = $DB->get_records('forum_posts', ['discussion' => $this->discussionid], '', engagedpost::DB_OUT_FIELDS);
        foreach ($posts as $post) {
            $this->postsdict[$post->id] = $post;
            if (!$post->parent) {
                $this->firstpost = $post->id;
            }
        }
    }

    /**
     * Initialise children
     */
    private function initchildren() {
        foreach ($this->postsdict as $post) {
            $post->children = $this->getchildren($post);
        }
    }

    /**
     * Get children post IDs of given postid
     *
     * @param engagedpost $parentpost
     */
    private function getchildren($parentpost) {
        $results = [];
        foreach ($this->postsdict as $post) {
            if ($post->parent == $parentpost->id) {
                $results[] = $post;
            }
        }
        return $results;
    }

    /**
     * Assign satisfies time property to posts
     */
    private function checkpoststime() {
        foreach ($this->postsdict as $post) {
            $post->satisfiestime = $this->postsatisfiestime($post);
        }
    }

    /**
     * Test if given post satisfies time condition
     *
     * @param engagedpost $post
     * @return bool
     */
    private function postsatisfiestime($post) {
        return (!$this->starttime || ($post->created >= $this->starttime))
            && (!$this->endtime || ($post->created <= $this->endtime));
    }

    /**
     * Calculate engagement of a user.
     *
     * @param int $userid
     * @return engagementresult
     */
    abstract public function calculate($userid);
}

/**
 * Person-to-Person Engagement
 */
class p2pengagement extends engagementcalculator {
    /**
     * Calculate engagement of a user.
     *
     * @param int $userid
     * @return engagementresult
     */
    public function calculate($userid) {
        $result = new engagementresult();
        $this->travel($userid, $this->postsdict[$this->firstpost], $result);
        return $result;
    }

    /**
     * Travel.
     *
     * @param int $userid
     * @param engagedpost $post
     * @param engagementresult $result
     * @param int[] $userengagement
     */
    private function travel($userid, $post, $result, &$userengagement = []) {
        foreach ($post->children as $childpost) {
            if ($childpost->userid != $post->userid && $childpost->userid == $userid) {
                if (!isset($userengagement[$post->userid])) {
                    $userengagement[$post->userid] = 0;
                }
                $userengagement[$post->userid]++;
                if ($childpost->satisfiestime) {
                    $result->increase($userengagement[$post->userid]);
                }
            }
            $this->travel($userid, $childpost, $result, $userengagement);
        }
    }
}

/**
 * Thread Count Engagement
 */
class threadcountengagement extends engagementcalculator {
    /**
     * Calculate engagement of a user.
     *
     * @param int $userid
     * @return engagementresult
     */
    public function calculate($userid) {
        $result = new engagementresult();
        $threads = $this->postsdict[$this->firstpost]->children;
        foreach ($threads as $post) {
            $countinthread = 0;
            if ($post->userid == $userid && $post->userid != $this->postsdict[$this->firstpost]->userid) {
                $countinthread++;
                if ($post->satisfiestime) {
                    $result->increase(1);
                }
            }
            $this->travel($userid, $post, $result, $countinthread);
        }
        return $result;
    }

    /**
     * Travel.
     *
     * @param int $userid
     * @param engagedpost $post
     * @param engagementresult $result
     * @param int $count
     */
    public function travel($userid, $post, $result, &$count) {
        foreach ($post->children as $childpost) {
            if ($childpost->userid != $post->userid && $childpost->userid == $userid && $childpost->satisfiestime) {
                $count++;
                $result->increase($count);
            }
            $this->travel($userid, $childpost, $result, $count);
        }
    }
}

/**
 * Thread Engagement
 */
class threadengagement extends engagementcalculator {
    /**
     * Calculate engagement of a user.
     *
     * @param int $userid
     * @return engagementresult
     */
    public function calculate($userid) {
        $result = new engagementresult();
        $this->travel($userid, $this->postsdict[$this->firstpost], $result);
        return $result;
    }

    /**
     * Travel.
     *
     * @param int $userid
     * @param engagedpost $post
     * @param engagementresult $result
     * @param int $level
     */
    public function travel($userid, $post, $result, $level = 1) {
        foreach ($post->children as $childpost) {
            if ($childpost->userid != $post->userid && $childpost->userid == $userid) {
                if ($childpost->satisfiestime) {
                    $result->increase($level);
                }
                $this->travel($userid, $childpost, $result, $level + 1);
            } else {
                $this->travel($userid, $childpost, $result, $level);
            }
        }
    }
}
