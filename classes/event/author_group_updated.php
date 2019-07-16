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
 * The assignsubmission_file submission_created event.
 *
 * @package    assignsubmission_file
 * @copyright  2019 Benedikt Schneider (@Nullmann)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_author\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The assignsubmission_author author_group_created event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int authorid: id of the group creator.
 *      - array oldcoauthors: Array with the ids of the old co authors.
 *      - array newcoauthors: Array with the ids of the new co authors.
 * }
 *
 * @package    assignsubmission_file
 * @since      Moodle 3.5
 * @copyright  2019 Benedikt Schneider (@Nullmann)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class author_group_updated extends \mod_assign\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'assignsubmission_author';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventauthorgroupupdated', 'assignsubmission_author');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {

        $authorid = $this->other['authorid'];
        $oldcoauthorsstring = implode(', ', $this->other['oldcoauthors']);
        $newcoauthorsstring = implode(', ', $this->other['newcoauthors']);
        return "The user with id '$this->userid' has changed the co authors group with author id '$authorid' and group member ids "
               ."'$oldcoauthorsstring' to group member with ids '$newcoauthorsstring' in the assignment with "
               ."course module id '$this->contextinstanceid'.";
    }
}