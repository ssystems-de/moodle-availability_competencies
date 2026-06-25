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
 * Availability Competencies - Frontend class
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_competencies;

/**
 * Availability Competencies - Frontend class
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Competency options already built, as courseid => options.
     *
     * @var array
     */
    protected $optionscache = [];

    /**
     * Strings required by the YUI form module.
     *
     * @return string[]
     */
    protected function get_javascript_strings() {
        return [
            'error_selectcompetency',
            'invalidcompetency',
        ];
    }

    /**
     * Course competencies for the picker (single select).
     *
     * @param \stdClass $course Course.
     * @param \cm_info|null $cm Module (activities).
     * @param \section_info|null $section Section.
     * @return array
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        return [$this->get_course_competency_options($course->id)];
    }

    /**
     * Hide the add button when competencies are unavailable for this course.
     *
     * @param \stdClass $course Course.
     * @param \cm_info|null $cm Module.
     * @param \section_info|null $section Section.
     * @return bool
     */
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        if (!\core_competency\api::is_enabled()) {
            return false;
        }
        $context = $this->get_edit_context($course, $cm, $section);
        if (!has_capability('availability/competencies:addinstance', $context)) {
            return false;
        }
        return !empty($this->get_course_competency_options($course->id));
    }

    /**
     * Context for editing availability (activity module or course).
     *
     * @param \stdClass $course Course.
     * @param \cm_info|null $cm Module.
     * @param \section_info|null $section Section.
     * @return \context
     */
    protected function get_edit_context(\stdClass $course, ?\cm_info $cm = null, ?\section_info $section = null): \context {
        if ($cm !== null) {
            return $cm->context;
        }
        return \context_course::instance($course->id);
    }

    /**
     * Sorted competency options for the course.
     *
     * Core creates one frontend per request and asks both allow_add() and
     * get_javascript_init_params() for the same list, so the result is remembered on the instance
     * to keep the form down to a single lookup.
     *
     * @param int $courseid Course ID.
     * @return array List of ['id' => int, 'name' => string].
     */
    protected function get_course_competency_options(int $courseid): array {
        if (!array_key_exists($courseid, $this->optionscache)) {
            $this->optionscache[$courseid] = $this->build_course_competency_options($courseid);
        }
        return $this->optionscache[$courseid];
    }

    /**
     * Build sorted competency options for the course.
     *
     * Deliberately without a capability check, which is left to {@see self::allow_add()}. The list
     * is not only what the picker offers, it is also what the YUI form needs to render a condition
     * which is already stored: a value it cannot offer is dropped from the form and then reported
     * as missing, which would leave the item unsaveable for anyone who may not add a new
     * restriction. Nothing is given away by that, as reading the course competencies only takes
     * moodle/competency:coursecompetencyview, which every authenticated user holds by default.
     *
     * @param int $courseid Course ID.
     * @return array List of ['id' => int, 'name' => string].
     */
    protected function build_course_competency_options(int $courseid): array {
        if (!\core_competency\api::is_enabled()) {
            return [];
        }
        $competencies = \core_competency\course_competency::list_competencies($courseid);
        if (empty($competencies)) {
            return [];
        }
        $options = [];
        foreach ($competencies as $competency) {
            $options[] = (object) [
                'id' => $competency->get('id'),
                'name' => format_string($competency->get('shortname'), true, [
                    'context' => \context_course::instance($courseid),
                ]),
            ];
        }
        // Sort by the current language rather than by byte value, so that names with umlauts or
        // accents end up where a reader expects them instead of behind Z.
        \core_collator::asort_objects_by_property($options, 'name');
        // The collator keeps the array keys. They have to be renumbered, because this list is
        // JSON encoded for the YUI form and would otherwise arrive there as an object without a
        // length, leaving the picker empty.
        return array_values($options);
    }
}
