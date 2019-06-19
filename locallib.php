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
 * This file contains the definition for the library class for author submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_author
 * @copyright 2013 Rene Roepke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

define('ASSIGNSUBMISSION_ONLINETEXT', 'onlinetext');
define('ASSIGNSUBMISSIONAUTHOR_MAXAUTHORS', 20);

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/author/classes/controllers/submission_controller.php');
require_once($CFG->dirroot . '/mod/assign/submission/author/classes/controllers/author_group_controller.php');
require_once($CFG->dirroot . '/mod/assign/submission/author/classes/utilities.php');

use assign_submission_author\utilities;
use assign_submission_author\submission_controller;
use assign_submission_author\author_group_controller;

/**
 * Library class for author submission plugin extending submission plugin base class
 *
 * @package assignsubmission_author
 * @author Rene Roepke
 * @author Guido Roessling
 * @copyright 2013 Rene Roepke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_author extends assign_submission_plugin
{

    /**
     * Get the name of the author submission plugin
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('author', 'assignsubmission_author');
    }

    /**
     * Get the default setting for author submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     * @throws coding_exception
     */
    public function get_settings(MoodleQuickForm $mform) {
        // Get config infos.
        $defaultmaxauthors = $this->get_config('maxauthors');
        $defaultgroupsused = $this->get_config('groupsused');
        $defaultingroupsonly = $this->get_config('ingroupsonly');
        $defaultnotification = $this->get_config('notification');

        // Generate maxauthors setting.
        $options = array();
        for ($i = 1; $i <= ASSIGNSUBMISSIONAUTHOR_MAXAUTHORS; $i++) {
            $options[$i] = $i;
        }
        // Display maxauthors setting.
        $name = get_string('maxauthors', 'assignsubmission_author');
        $mform->addElement('select', 'assignsubmissionauthor_maxauthors', $name, $options);
        $mform->addHelpButton('assignsubmissionauthor_maxauthors', 'maxauthors', 'assignsubmission_author');
        $mform->setDefault('assignsubmissionauthor_maxauthors', $defaultmaxauthors);
        $mform->disabledIf('assignsubmissionauthor_maxauthors', 'assignsubmission_author_enabled', 'notchecked');

        // Display notification setting.
        $name = get_string('notification', 'assignsubmission_author');
        $mform->addElement('checkbox', 'assignsubmissionauthor_notification', $name, '', 0);
        $mform->setDefault('assignsubmissionauthor_notification', $defaultnotification);
        $mform->addHelpButton('assignsubmissionauthor_notification', 'notification', 'assignsubmission_author');
        $mform->disabledIf('assignsubmissionauthor_notification', 'assignsubmission_author_enabled', 'notchecked');

        // Display groupsused setting.
        $name = get_string('groupsused', 'assignsubmission_author');
        $mform->addElement('checkbox', 'assignsubmissionauthor_groupsused', $name, '', 0);
        $mform->setDefault('assignsubmissionauthor_groupsused', $defaultgroupsused);
        $mform->addHelpButton('assignsubmissionauthor_groupsused', 'groupsused', 'assignsubmission_author');
        $mform->disabledIf('assignsubmissionauthor_groupsused', 'assignsubmission_author_enabled', 'notchecked');

        // Display ingroupsonly setting.
        $name = get_string('ingroupsonly', 'assignsubmission_author');
        $mform->addElement('checkbox', 'assignsubmissionauthor_ingroupsonly', $name, '', 0);
        $mform->setDefault('assignsubmissionauthor_ingroupsonly', $defaultingroupsonly);
        $mform->addHelpButton('assignsubmissionauthor_ingroupsonly', 'ingroupsonly', 'assignsubmission_author');
        $mform->disabledIf('assignsubmissionauthor_ingroupsonly', 'assignsubmissionauthor_groupsused', 'notchecked');
        $mform->disabledIf('assignsubmissionauthor_ingroupsonly', 'assignsubmission_author_enabled', 'notchecked');
    }

    /**
     * Save the settings for author submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        // Set config info.
        $checkmaxauthors = isset($data->assignsubmissionauthor_maxauthors);
        $this->set_config('maxauthors',
            $checkmaxauthors ? $data->assignsubmissionauthor_maxauthors : 0);
        $checkgroupsused = isset($data->assignsubmissionauthor_groupsused) && $data->assignsubmissionauthor_groupsused == 1;
        $checkingroupsonly = isset($data->assignsubmissionauthor_ingroupsonly);
        $this->set_config('ingroupsonly',
            $checkgroupsused ? ($checkingroupsonly ? $data->assignsubmissionauthor_ingroupsonly : 0) : 0);
        $checknotification = isset($data->assignsubmissionauthor_notification);
        $this->set_config('notification',
            $checknotification ? $data->assignsubmissionauthor_notification : 0);
        $checkgroupsused = isset($data->assignsubmissionauthor_groupsused);
        $this->set_config('groupsused',
            $checkgroupsused ? $data->assignsubmissionauthor_groupsused : 0);
        return true;
    }

    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $USER, $COURSE;

        $authorgroupcontroller = new author_group_controller($this->assignment);
        $submissioncontroller = new submission_controller();

        // Get maxauthors config info.
        $maxauthors = $this->get_config('maxauthors');

        // If maxauthors <= 1 then return comment and no more content.
        if ($maxauthors <= 1) {
            $mform->addElement('static', '', '', get_string('oneauthoronly', 'assignsubmission_author'), 1);
            return true;
        }

        // If team assignment is activated then return comment and no more content.
        if ($this->assignment->get_instance()->teamsubmission == 1) {
            $mform->addElement('static', '', '', get_string('noteamsubmission', 'assignsubmission_author'), 1);
            return true;
        }

        // Start generating content.
        $courseid = $COURSE->id;
        $userid = $USER->id;
        $selectedauthors = array();
        $alreadyinauthorgroup = false;
        $assignment = $this->assignment->get_instance()->id;

        // If authorsubmission then get it.
        if ($submission) {
            $authorsubmission = $submissioncontroller->get_author_submission(
                    $assignment,
                    $submission->id);
            if ($authorsubmission) {
                $alreadyinauthorgroup = $authorsubmission->author != $userid;
                $selectedauthors = utilities::get_author_array(
                        $authorsubmission->author . ',' . $authorsubmission->authorlist,
                        $this->assignment->get_course()->id,
                        true);
                $origauthor = utilities::get_author_array($authorsubmission->author, $this->assignment->get_course()->id, true);
            }
        }

        // Get ingroupsonly config info.
        $ingroupsonly = $this->get_config('ingroupsonly');

        // Get config info about groups.
        $groupsused = $this->get_config('groupsused');

        // Get possible coauthors.
        $possiblecoauthors = $authorgroupcontroller->get_possible_co_authors($courseid,
                $userid, $ingroupsonly, $assignment, $groupsused);

        $userarr = null;
        $userarr[$userid] = '';

        // Get author default.
        $authordefaultsubmission = $authorgroupcontroller->get_author_default($userid,
                $courseid);

        if ($authordefaultsubmission) {
            $default = $authordefaultsubmission->coauthors;
            $array = utilities::get_author_array($default, $this->assignment->get_course()->id, true);
            $array = array_diff_key($array, $userarr);
            $showdefault = utilities::is_default_usable($array, $possiblecoauthors, $maxauthors);
            $default = implode(', ', $array);
        }

        // Get preselected authors.
        $selectedauthors = array_diff_key($selectedauthors, $userarr);

        // Set reactive behaviour for all options.
        $mform->disabledIf('defcoauthors', 'selcoauthors', 'checked');
        $mform->disabledIf('defcoauthors', 'nocoauthors', 'checked');
        $mform->disabledIf('defcoauthors', 'groupcoauthors', 'checked');
        $mform->disabledIf('selcoauthors', 'defcoauthors', 'checked');
        $mform->disabledIf('selcoauthors', 'nocoauthors', 'checked');
        $mform->disabledIf('selcoauthors', 'groupcoauthors', 'checked');
        $mform->disabledIf('nocoauthors', 'defcoauthors', 'checked');
        $mform->disabledIf('nocoauthors', 'selcoauthors', 'checked');
        $mform->disabledIf('nocoauthors', 'groupcoauthors', 'checked');
        $mform->disabledIf('groupcoauthors', 'defcoauthors', 'checked');
        $mform->disabledIf('groupcoauthors', 'selcoauthors', 'checked');
        $mform->disabledIf('groupcoauthors', 'nocoauthors', 'checked');

        // If already in authorgroup then 4th option.
        if ($alreadyinauthorgroup) {
            $mform->setDefault('groupcoauthors', 'checked');
            $mform->addElement('checkbox', 'groupcoauthors', '', get_string('choose_group', 'assignsubmission_author'), 1);
            $mform->addElement('static', 'group2coauthors', get_string('group', 'assignsubmission_author'),
                $this->get_summary($origauthor, array_diff_key($selectedauthors, $origauthor)), null);
            $mform->addElement('static', '', '', '');
        } else {
            $mform->setDefault('selcoauthors', 'checked');
        }

        $mform->addElement('header',
                'header',
                get_string('header', 'assignsubmission_author'));

        // Display 1st option to select co authors.
        $mform->addElement('checkbox', 'selcoauthors', '', get_string('choose_coauthors', 'assignsubmission_author'), 1);

        if (count($possiblecoauthors) != 0) {
            // Define content of choice boxes.
            $achoices = array();
            $achoices[0] = get_string('choose', 'assignsubmission_author');
            $achoices = $achoices + $possiblecoauthors;

            // Generate as many choice boxes as necessary.
            $objs = array();
            for ($i = 0; $i < $maxauthors - 1; ++$i) {
                $objs[$i] = &$mform->createElement('select', 'coauthors[' . $i . ']', '', $achoices, null);
            }

            // Add elements.
            $mform->addElement('group', 'coauthorselection',
                get_string('coauthors', 'assignsubmission_author'), $objs, ' ', false);
            $mform->disabledIf('coauthorselection', 'selcoauthors', 'notchecked');
            $mform->addElement('checkbox', 'asdefault', ' ', get_string('asdefault', 'assignsubmission_author'));
            $mform->disabledIf('asdefault', 'selcoauthors', 'notchecked');

            // Set preselected coauthors.
            if ($alreadyinauthorgroup) {
                $i = 0;
                foreach ($selectedauthors as $key => $value) {
                    $mform->setDefault('coauthors[' . $i . ']', 0);
                    $i++;
                }
            } else {
                $i = 0;
                foreach ($selectedauthors as $key => $value) {
                    $mform->setDefault('coauthors[' . $i . ']', $key);
                    $i++;
                }
            }
        } else {
            $mform->addElement('static', '', '', get_string('nopossiblecoauthors', 'assignsubmission_author'), 1);
        }

        $mform->addElement('static', '', '', '');

        // If default then display 2nd option for default.
        if (isset($showdefault) && $showdefault && isset($default)) {
            $mform->addElement('checkbox', 'defcoauthors', '',
                get_string('choose_defaultcoauthors', 'assignsubmission_author'), 1);
            $mform->addElement('static', 'defaultcoauthors',
                get_string('defaultcoauthors', 'assignsubmission_author'), $default, 1);
            $mform->addElement('static', '', '', '');
        }

        // Display 3rd option for no coauthors.
        $mform->addElement('checkbox', 'nocoauthors', '', get_string('choose_nocoauthors', 'assignsubmission_author'), 1);

        return true;
    }

    /**
     * Save data to the database
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $COURSE;

        $submissioncontroller = new submission_controller();
        $authorgroupcontroller = new author_group_controller($this->assignment);

        // If team submission is activated no submission is possible.
        if ($this->assignment->get_instance()->teamsubmission == 1) {
            $this->set_error(get_string('error_teamsubmission', 'assignsubmission_author'));
            return false;
        }

        // Get notification config info.
        $notification = $this->get_config('notification');

        $userid = $USER->id;
        $courseid = $COURSE->id;
        $assignment = $this->assignment->get_instance()->id;

        // If already submission then update else create.
        $currentcoauthors = array();
        if ($submission) {
            // If already author submission then update else create.
            $authorsubmission = $submissioncontroller->get_author_submission($assignment, $submission->id);

            if ($authorsubmission) {
                // UPDATE AUTHORSUBMISSION.

                // Get current coauthors as array.
                $currentcoauthors = explode(',', $authorsubmission->authorlist);

                if (isset($data->groupcoauthors) && $data->groupcoauthors == 1) {
                    // Fourth (4th) option - coauthor perspective.
                    $currentcoauthors = explode(',', $authorsubmission->author . ',' . $authorsubmission->authorlist);

                    // Update onlinetext submission.
                    if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                            ASSIGNSUBMISSION_ONLINETEXT,
                            'assignsubmission')) {
                        $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                    }

                    return true;
                } else if ($authorsubmission->author == $userid) {
                    if (isset($data->selcoauthors) && $data->selcoauthors == 1) {
                        // First (1st) option - author perspective.

                        // Get new selected coauthors.
                        $selectedcoauthors = utilities::get_selected_coauthors($data);

                        // If no new selected coauthors then delete current authorgroup else just update.
                        if (count($selectedcoauthors) == 0) {
                            $deletecoauthors = $currentcoauthors;

                            $authorgroupcontroller->delete_author_group($deletecoauthors, $submission->assignment);

                            $submissioncontroller->delete_author_submission($userid, $submission->assignment);
                        } else {

                            // Distinguish between new coauthors, deleted coauthors, current coauthors.
                            $deletecoauthors = array_diff($currentcoauthors, $selectedcoauthors);
                            $newcoauthors = array_diff($selectedcoauthors, $currentcoauthors);
                            $updatecoauthors = array_diff($selectedcoauthors, $newcoauthors);
                            $currentcoauthors = $selectedcoauthors;

                            // Delete author group with deleted coauthors.
                            $authorgroupcontroller->delete_author_group($deletecoauthors, $submission->assignment);

                            $author = $authorsubmission->author;
                            $authorlist = implode(',', $currentcoauthors);

                            // Create and update author group with new and current coauthors.
                            $authorgroupcontroller->create_author_group($newcoauthors,
                                    $submission,
                                    $authorlist);
                            $authorgroupcontroller->update_author_group($updatecoauthors,
                                    $submission->assignment,
                                    $author,
                                    $authorlist);

                            // Update own author submission.
                            $submissioncontroller->update_author_submission($authorsubmission, $author, $authorlist);

                            // If onlinetext plugin is enabled then update/create submissions.
                            if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                                    ASSIGNSUBMISSION_ONLINETEXT,
                                    'assignsubmission')) {
                                $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                            }

                            // If default option is set then save this group as default group.
                            if (isset($data->asdefault) && $data->asdefault == 1) {
                                $authorgroupcontroller->set_author_default($authorlist, $userid, $courseid);
                            }

                            // If notifications are on then send notifications to all new and currend coauthors.
                            if ($notification) {
                                $this->send_notifications($author, $currentcoauthors);
                            }
                        }

                        return true;
                    } else if (isset($data->defcoauthors) && $data->defcoauthors == 1) {
                        // Second (2nd) option - author perspective.

                        // Get default coauthors.
                        $defaultcoauthors = $authorgroupcontroller->get_default_coauthors($userid, $courseid);

                        // Distinguish between new coauthors, deleted coauthors, current coauthors.
                        $deletecoauthors = array_diff($currentcoauthors, $defaultcoauthors);
                        $newcoauthors = array_diff($defaultcoauthors, $currentcoauthors);
                        $updatecoauthors = array_diff($defaultcoauthors, $newcoauthors);

                        $currentcoauthors = $defaultcoauthors;

                        // Delete author group with deleted coauthors.
                        $authorgroupcontroller->delete_author_group($deletecoauthors, $submission->assignment);

                        $author = $authorsubmission->author;
                        $authorlist = implode(',', $currentcoauthors);

                        // Create and update author group with new and current coauthors.
                        $authorgroupcontroller->update_author_group($updatecoauthors,
                                $submission->assignment,
                                $author,
                                $authorlist);
                        $authorgroupcontroller->create_author_group($newcoauthors,
                                $submission,
                                $authorlist);

                        // Update own author submission.
                        $submissioncontroller->update_author_submission($authorsubmission, $author, $authorlist);

                        // If onlinetext plugin is enabled then update/create submissions.
                        if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                                ASSIGNSUBMISSION_ONLINETEXT,
                                'assignsubmission')) {
                            $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                        }

                        // If notifications are on then send notifications to all new and currend coauthors.
                        if ($notification) {
                            $this->send_notifications($author, $currentcoauthors);
                        }

                        return true;
                    } else if (isset($data->nocoauthors) && $data->nocoauthors == 1) {
                        // Third (3rd) option - author perspective.

                        $deletecoauthors = $currentcoauthors;

                        // Delete authorgroup.
                        $authorgroupcontroller->delete_author_group($deletecoauthors, $submission->assignment);

                        $submissioncontroller->delete_author_submission($userid, $submission->assignment);
                        return true;
                    }
                } else {
                    if (isset($data->selcoauthors) && $data->selcoauthors == 1) {
                        // First (1st) option - coauthor perspective.
                        $userarr = array(
                            $userid
                        );

                        $updatecoauthors = array_diff($currentcoauthors, $userarr);

                        $updateauthor = array(
                            $authorsubmission->author
                        );

                        $author = $authorsubmission->author;
                        $authorlist = implode(',', $updatecoauthors);

                        // Update or delete remaining author group.
                        if ($authorlist != '') {
                            $authorgroupcontroller->update_author_group($updatecoauthors,
                                    $submission->assignment,
                                    $author,
                                    $authorlist);
                            $authorgroupcontroller->update_author_group($updateauthor,
                                    $submission->assignment,
                                    $author,
                                    $authorlist);
                        } else {
                            $authorgroupcontroller->delete_author_group($updatecoauthors, $submission->assignment);
                            $authorgroupcontroller->delete_author_group($updateauthor, $submission->assignment);
                        }
                        $selectedcoauthors = utilities::get_selected_coauthors($data);

                        // Delete author group and submission.
                        if (count($selectedcoauthors) == 0) {

                            $deletecoauthors = $currentcoauthors;
                            $authorgroupcontroller->delete_author_group($deletecoauthors, $submission->assignment);
                            $submissioncontroller->delete_author_submission($userid, $submission->assignment);

                            return true;
                        }

                        $author = $userid;
                        $authorlist = implode(',', $selectedcoauthors);

                        // Create new author group.
                        $authorgroupcontroller->create_author_group($selectedcoauthors, $submission, $authorlist);

                        $currentcoauthors = $selectedcoauthors;

                        // Update own author submission.
                        $submissioncontroller->update_author_submission($authorsubmission, $author, $authorlist);

                        // If onlinetext plugin is enabled then update/create submissions.
                        if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                                ASSIGNSUBMISSION_ONLINETEXT,
                                'assignsubmission')) {
                            $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                        }

                        // If notifications are on then send notifications to all new and currend coauthors.
                        if ($notification) {
                            $this->send_notifications($author, $currentcoauthors);
                        }

                        // If default option is set then save this group as default group.
                        if (isset($data->asdefault) && $data->asdefault == 1) {
                            $authorgroupcontroller->set_author_default($authorlist, $userid, $courseid);
                        }
                        return true;
                    } else if (isset($data->defcoauthors) && $data->defcoauthors == 1) {
                        // Second (2nd) option - coauthor perspective.

                        $userarr = array(
                            $userid
                        );

                        $updatecoauthors = array_diff($currentcoauthors, $userarr);

                        $updateauthor = array(
                            $authorsubmission->author
                        );

                        $author = $authorsubmission->author;
                        $authorlist = implode(',', $updatecoauthors);

                        // Update or delete remaining authorgroup.
                        if ($authorlist != '') {
                            $authorgroupcontroller->update_author_group($updatecoauthors,
                                    $submission->assignment,
                                    $author,
                                    $authorlist);
                            $authorgroupcontroller->update_author_group($updateauthor,
                                    $submission->assignment,
                                    $author,
                                    $authorlist);
                        } else {
                            $authorgroupcontroller->delete_author_group($updatecoauthors, $submission->assignment);
                            $authorgroupcontroller->delete_author_group($updateauthor, $submission->assignment);
                        }

                        // Get default coauthors.
                        $defaultcoauthors = $authorgroupcontroller->get_default_coauthors($userid, $courseid);

                        $author = $userid;
                        $authorlist = implode(',', $defaultcoauthors);

                        // Create new authorgroup by default.
                        $authorgroupcontroller->create_author_group($defaultcoauthors, $submission, $authorlist);

                        $currentcoauthors = $defaultcoauthors;

                        // Update own authorsubmission.
                        $submissioncontroller->update_author_submission($authorsubmission, $author, $authorlist);

                        // If onlinetext plugin is enabled then update/create submissions.
                        if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                                ASSIGNSUBMISSION_ONLINETEXT,
                                'assignsubmission')) {
                            $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                        }

                        // If notifications are on then send notifications to all new and currend coauthors.
                        if ($notification) {
                            $this->send_notifications($author, $currentcoauthors);
                        }
                        return true;
                    } else if (isset($data->nocoauthors) && $data->nocoauthors == 1) {
                        // Third (3rd) option - coauthor perspective.

                        $userarr = array(
                            $userid
                        );

                        $updatecoauthors = array_diff($currentcoauthors, $userarr);

                        $updateauthor = array(
                            $authorsubmission->author
                        );

                        $author = $authorsubmission->author;
                        $authorlist = implode(',', $updatecoauthors);

                        // Update current author group.
                        $authorgroupcontroller->update_author_group($updatecoauthors,
                                $submission->assignment,
                                $author,
                                $authorlist);
                        $authorgroupcontroller->update_author_group($updateauthor,
                                $submission->assignment,
                                $author,
                                $authorlist);

                        // Delete own author submission.
                        $submissioncontroller->delete_author_submission($userid, $submission->assignment);

                        return true;
                    }
                }

            } else {

                if (isset($data->selcoauthors) && $data->selcoauthors == 1) {

                    // Get new coauthors.
                    $currentcoauthors = utilities::get_selected_coauthors($data);

                    if (count($currentcoauthors) == 0) {
                        return true;
                    }

                    $author = $userid;
                    $authorlist = implode(',', $currentcoauthors);

                    // Create new authorgroup.
                    $authorgroupcontroller->create_author_group($currentcoauthors, $submission, $authorlist);
                    $submissioncontroller->create_author_submission($submission->assignment, $submission->id, $author, $authorlist);

                    // If onlinetext plugin is enabled then update/create submissions.
                    if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                            ASSIGNSUBMISSION_ONLINETEXT,
                            'assignsubmission')) {
                        $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                    }

                    // If notifications are on then send notifications to all new and currend coauthors.
                    if ($notification) {
                        $this->send_notifications($author, $currentcoauthors);
                    }

                    // If default option is set then save this group as default group.
                    if (isset($data->asdefault) && $data->asdefault == 1) {
                        $authorgroupcontroller->set_author_default($authorlist, $userid, $courseid);
                    }
                    return true;

                } else if (isset($data->defcoauthors) && $data->defcoauthors == 1) {
                    // Second (2nd) option - new authorgroup like the default group.

                    $currentcoauthors = $authorgroupcontroller->get_default_coauthors($userid, $courseid);

                    $author = $userid;
                    $authorlist = implode(',', $currentcoauthors);

                    // Create new authorgroup.
                    $authorgroupcontroller->create_author_group($currentcoauthors, $submission, $authorlist);
                    $submissioncontroller->create_author_submission($submission->assignment, $submission->id, $author, $authorlist);

                    // If onlinetext plugin is enabled then update/create submissions.
                    if (utilities::is_plugin_enabled($this->assignment->get_instance()->id,
                            ASSIGNSUBMISSION_ONLINETEXT,
                            'assignsubmission')) {
                        $authorgroupcontroller->set_onlinetext_submission_for_coauthors($currentcoauthors, $data);
                    }

                    // If notifications are on then send notifications to all new and currend coauthors.
                    if ($notification) {
                        $this->send_notifications($author, $currentcoauthors);
                    }

                    return true;
                } else if (isset($data->nocoauthors) && $data->nocoauthors == 1) {
                    // No coauthors, so nothing to create.
                    return true;
                }
            }
        }

        return true;
    }

    /**
     * Display the author and coauthors
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     *            - If the summary has been truncated set this to true
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $submissioncontroller = new submission_controller();
        $assignment = $this->assignment->get_instance()->id;
        $authorsubmission = $submissioncontroller->get_author_submission($assignment, $submission->id);
        // Always show the view link.
        $showviewlink = false;

        if ($authorsubmission) {
            $author = utilities::get_author_array($authorsubmission->author, $this->assignment->get_course()->id, true);
            $coauthors = utilities::get_author_array($authorsubmission->authorlist, $this->assignment->get_course()->id, true);

            return $this->get_summary($author, $coauthors);
        }
        return get_string('summary_nocoauthors', 'assignsubmission_author');
    }

    /**
     * Display the author and coauthors
     *
     * @param stdClass $submission
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function view(stdClass $submission) {
        $showviewlink = true;
        return $this->view_summary($submission, $showviewlink);
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission
     *            The new submission
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function format_for_log(stdClass $submission) {
        $submissioncontroller = new submission_controller();
        // Format the info for each submission plugin (will be logged).
        $authorsubmission = $submissioncontroller->get_author_submission($this->assignment->get_instance()->id, $submission->id);
        $authorloginfo = '';

        if ($authorsubmission) {
            $authorloginfo .= $authorsubmission->author . ',';
            $authorloginfo .= $authorsubmission->authorlist;
        }
        return $authorloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records('assignsubmission_author', array(
            'assignment' => $this->assignment->get_instance()->id
        ));

        return true;
    }

    /**
     * No authors are set
     *
     * @param stdClass $submission
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function is_empty(stdClass $submission) {
        $submissioncontroller = new submission_controller();
        return ($submissioncontroller->get_author_submission($this->assignment->get_instance()->id, $submission->id) == false);
    }

    /**
     * Creates summary string with author and coauthors
     *
     * @param array $author
     * @param array $coauthors
     * @return string
     * @throws coding_exception
     */
    public function get_summary($author, $coauthors) {
        $summary = get_string('summary_author', 'assignsubmission_author');
        $summary .= ': ';
        $summary .= implode(',', $author);
        $summary .= '<br>';
        $summary .= get_string('summary_coauthors', 'assignsubmission_author');
        $summary .= ': ';
        $summary .= implode(', ', $coauthors);
        return $summary;
    }

    /**
     * Send notifications to all coauthors
     *
     * @param int $author
     * @param int[] $coauthors
     * @throws coding_exception
     * @throws dml_exception
     */
    private function send_notifications($author, $coauthors) {
        global $CFG, $USER;
        $course = $this->assignment->get_course();
        $a = new stdClass();
        $a->courseurl = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $a->coursename = $course->fullname;
        $a->username = fullname(core_user::get_user($author));
        $a->assignmentname = format_string($this->assignment->get_instance()->name, true,
                array('context' => $this->assignment->get_context()));
        $a->assignmenturl = $CFG->wwwroot . '/mod/assign/view.php?id=' . $this->assignment->get_course_module()->id;
        $subject = get_string('subject', 'assignsubmission_author', $a);
        $message = $subject . ': ' . get_string('message', 'assignsubmission_author', $a);
        foreach ($coauthors as $coauthor) {
            $userto = core_user::get_user($coauthor);
            $eventdata = new \core\message\message;
            $eventdata->modulename = 'assign';
            $eventdata->userfrom = $USER;
            $eventdata->userto = $userto;
            $eventdata->subject = $subject;
            $eventdata->fullmessage = $message;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = $message;
            $eventdata->smallmessage = $subject;
            $eventdata->name = 'assign_notification';
            $eventdata->component = 'mod_assign';
            $eventdata->notification = 1;
            $eventdata->contexturl = $CFG->wwwroot . '/mod/assign/view.php?id=' . $this->assignment->get_course_module()->id;
            $eventdata->contexturlname = format_string($this->assignment->get_instance()->name, true, array(
                    'context' => $this->assignment->get_context()
            ));
            message_send($eventdata);
        }
    }

    /**
     * Delete submission record
     *
     * @param int $id
     * @return
     */
    private function delete_submission($id) {
        global $DB;
        return $DB->delete_record('assign_submission', array(
                'id' => $id
        ));
    }

}


