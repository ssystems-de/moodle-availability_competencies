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
 * Availability Competencies - Cache definitions.
 *
 * @package    availability_competencies
 * @copyright  2026 Alexander Bias, ssystems GmbH <abias@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Proficiency of a user across all competencies, keyed by user ID.
    //
    // Kept fresh by \availability_competencies\observer, which drops the entry of a user as soon
    // as evidence is added for them. That covers every change core makes to the proficiency
    // itself, see db/events.php for why one event is enough.
    //
    // What it does not cover is a user_competency record going away. Core never deletes one
    // through the competency API. The only place which removes them is the privacy provider
    // (\core_competency\privacy\provider, delete_records_select on user_competency), so it happens
    // on GDPR erasure and raises no event to observe. That is what the time to live is for: the
    // entry ages out on its own, and the user whose records were erased is leaving the site
    // anyway. The same net catches anything else which writes the table directly, such as an
    // upgrade script or a third party plugin bypassing the API.
    'proficiencies' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 2, // Availability is evaluated for one user at a time.
        'ttl' => 3600,
    ],
];
