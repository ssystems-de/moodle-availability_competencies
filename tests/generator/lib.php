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
 * Availability Competencies - Data generator.
 *
 * @package    availability_competencies
 * @copyright  2026 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Availability Competencies - Data generator.
 *
 * @package    availability_competencies
 * @copyright  2026 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_competencies_generator extends component_generator_base {
    /**
     * Restricts an activity by a competency.
     *
     * @param array $data Restriction data, see {@see self::build_availability()} for the options.
     *                    Needs a 'cmid'.
     */
    public function create_activity_restriction(array $data): void {
        global $DB;

        $courseid = $DB->get_field('course_modules', 'course', ['id' => $data['cmid']], MUST_EXIST);
        $DB->set_field('course_modules', 'availability', $this->build_availability($data), ['id' => $data['cmid']]);

        // The availability of an item is part of the course cache, which was built without it.
        rebuild_course_cache($courseid, true);
    }

    /**
     * Restricts a course section by a competency.
     *
     * @param array $data Restriction data, see {@see self::build_availability()} for the options.
     *                    Needs a 'courseid' and a 'section'.
     */
    public function create_section_restriction(array $data): void {
        global $DB;

        $DB->set_field('course_sections', 'availability', $this->build_availability($data), [
            'course' => $data['courseid'],
            'section' => (int) $data['section'],
        ]);

        rebuild_course_cache($data['courseid'], true);
    }

    /**
     * The availability tree of a single competency condition, encoded the way the form stores it.
     *
     * @param array $data Restriction data. 'competencyid' names the competency. 'negated' turns
     *                    the condition around, the way the "must not" option of the form does.
     *                    'hidden' hides the item entirely instead of showing it greyed out with
     *                    its reason, the way the closed eye does.
     * @return string JSON for the availability field.
     */
    protected function build_availability(array $data): string {
        $negated = !empty($data['negated']);
        $hidden = !empty($data['hidden']);

        $tree = [
            'op' => $negated ? '!&' : '&',
            'c' => [
                ['type' => 'competencies', 'competencyid' => (int) $data['competencyid']],
            ],
        ];
        // A negated tree carries one display setting for the whole set, a plain one carries a
        // setting per condition. That is how \core_availability\tree::save() writes it.
        if ($negated) {
            $tree['show'] = !$hidden;
        } else {
            $tree['showc'] = [!$hidden];
        }

        return json_encode($tree);
    }
}
