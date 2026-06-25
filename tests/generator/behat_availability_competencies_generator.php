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
 * Availability Competencies - Behat data generator.
 *
 * Restricts activities and course sections by a competency, without going through the form:
 *
 *     Given the following "availability_competencies > activity restrictions" exist:
 *       | activity | competency | negated | hidden |
 *       | PAGE1    | COMP1      | 1       |        |
 *
 *     Given the following "availability_competencies > section restrictions" exist:
 *       | course | section | competency |
 *       | C1     | 1       | COMP1      |
 *
 * The activity is named by its idnumber, the course by its shortname and the competency by its
 * idnumber. "negated" turns the condition around, the way the "must not" option of the form does.
 * "hidden" hides the item entirely instead of showing it greyed out with its reason, the way the
 * closed eye does.
 *
 * The availability form is written in YUI, so configuring a restriction through the user interface
 * needs a JavaScript scenario and a good handful of steps. That is worth doing once, see
 * availability_competencies_restrict.feature. Every other feature only needs the restriction to be
 * in place, and gets it from here instead.
 *
 * A note on making a learner proficient: the plugin caches the proficiency of a user until
 * evidence is added for them, so a "core_competency > user_competency" row written by the
 * generator only counts when it exists before that user views the restricted item for the first
 * time. Scenarios which need proficiency to arrive later have to rate the learner through the
 * user interface or purge, because only that raises the event the plugin listens to, or have to
 * purge caches after the generator added the restriction.
 *
 * @package    availability_competencies
 * @copyright  2026 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_availability_competencies_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'activity restrictions' => [
                'singular' => 'activity restriction',
                'datagenerator' => 'activity_restriction',
                'required' => ['activity', 'competency'],
                'switchids' => ['activity' => 'cmid', 'competency' => 'competencyid'],
            ],
            'section restrictions' => [
                'singular' => 'section restriction',
                'datagenerator' => 'section_restriction',
                'required' => ['course', 'section', 'competency'],
                'switchids' => ['course' => 'courseid', 'competency' => 'competencyid'],
            ],
        ];
    }

    /**
     * Get the competency id using an idnumber.
     *
     * @param string $idnumber Idnumber of the competency.
     * @return int The competency id.
     */
    protected function get_competency_id(string $idnumber): int {
        global $DB;

        if (!$id = $DB->get_field('competency', 'id', ['idnumber' => $idnumber])) {
            throw new Exception('The specified competency with idnumber "' . $idnumber . '" could not be found.');
        }

        return $id;
    }
}
