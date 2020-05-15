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
 * Mandatory public API of MS Teams module
 *
 * @package    mod_msteams
 * @copyright  2020 Center for Learning Management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in MS Teams module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function adventuremap_supports($feature) {
    switch($feature) {
        //case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function adventuremap_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function adventuremap_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function adventuremap_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add adventuremap instance.
 * @param object $data
 * @param object $mform
 * @return int new instance id
 */
function adventuremap_add_instance($data, $mform) {
    global $CFG, $DB;

    $data->timemodified = time();
    $data->id = $DB->insert_record('adventuremap', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'adventuremap', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update Adventuremap instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function adventuremap_update_instance($data, $mform) {
    global $CFG, $DB;

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('adventuremap', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'adventuremap', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete Adventuremap instance.
 * @param int $id
 * @return bool true
 */
function adventuremap_delete_instance($id) {
    global $DB;

    if (!$adventuremap = $DB->get_record('adventuremap', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('adventuremap', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'adventuremap', $id, null);
    $DB->delete_records('adventuremap', array('id'=>$adventuremap->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function adventuremap_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (!$adventuremap = $DB->get_record('adventuremap', array('id'=>$coursemodule->instance),
            'id, name, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $adventuremap->name;


    $fullurl = "$CFG->wwwroot/mod/adventuremap/view.php?id=$coursemodule->id&amp;redirect=1";
    $info->onclick = "window.open('$fullurl'); return false;";

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('adventuremap', $adventuremap, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function adventuremap_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-adventuremap-*'=>get_string('page-mod-adventuremap-x', 'adventuremap'));
    return $module_pagetype;
}

/**
 * Export Adventuremap resource contents
 *
 * @return array of file content
 */
function adventuremap_export_contents($cm, $baseurl) {
    global $CFG, $DB;

    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $adventuremaprecord = $DB->get_record('adventuremap', array('id'=>$cm->instance), '*', MUST_EXIST);

    $adventuremap = array();
    $adventuremap['type'] = 'adventuremap';
    $adventuremap['filename']     = clean_param(format_string($adventuremaprecord->name), PARAM_FILE);
    $adventuremap['filepath']     = null;
    $adventuremap['filesize']     = 0;
    $adventuremap['fileurl']      = $fullurl;
    $adventuremap['timecreated']  = null;
    $adventuremap['timemodified'] = $adventuremaprecord->timemodified;
    $adventuremap['sortorder']    = null;
    $adventuremap['userid']       = null;
    $adventuremap['author']       = null;
    $adventuremap['license']      = null;
    $contents[] = $adventuremap;

    return $contents;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $adventuremap    adventuremap object
 * @param  stdClass $course          course object
 * @param  stdClass $cm              course module object
 * @param  stdClass $context         context object
 * @since Moodle 3.0
 */
function adventuremap_view($adventuremap, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $msteams->id
    );

    $event = \mod_url\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('adventuremap', $adventuremap);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function adventuremap_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid ID override for calendar events
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_adventuremap_core_calendar_provide_event_action(calendar_event $event,
                                                       \core_calendar\action_factory $factory, $userid = 0) {

    global $USER;
    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['adventuremap'][$event->instance];
    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/adventuremap/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
