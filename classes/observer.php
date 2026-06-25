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
 * Availability Competencies - Event observers.
 *
 * @package    availability_competencies
 * @copyright  2026 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_competencies;

/**
 * Availability Competencies - Event observers.
 *
 * @package    availability_competencies
 * @copyright  2026 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Forget the cached proficiency of the user the evidence belongs to.
     *
     * Only that one user is dropped. A learner being rated must not cost everybody else their
     * cached proficiency.
     *
     * The cache is filled in {@see \availability_competencies\condition::get_proficiencies()}.
     *
     * @param \core\event\competency_evidence_created $event The event.
     */
    public static function competency_evidence_created(\core\event\competency_evidence_created $event): void {
        \cache::make('availability_competencies', 'proficiencies')->delete((int) $event->relateduserid);
    }
}
