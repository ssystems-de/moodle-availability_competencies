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
 * Availability Competencies - Condition
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_competencies;

use core_availability\info;

/**
 * Availability Competencies - Condition
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /**
     * Competency ID marker for a condition whose competency could not be resolved during restore.
     *
     * The condition is kept in the tree so that the restriction stays visible to teachers, but it
     * never grants access and is described as unknown.
     */
    const COMPETENCYID_NOTRESTORED = -1;

    /**
     * Competency records looked up, as competencyid => competency|false.
     *
     * Deliberately a plain static cache which dies with the request, unlike the proficiency of a
     * user, which is worth keeping in the MUC (see db/caches.php). Holding these records beyond
     * the request would buy a single lookup by primary key, and it would cost two more observers
     * for competency_updated and competency_deleted. Getting that wrong would show: the short
     * name ends up in the description learners read, so a stale one would be visible for as long
     * as the cache lives. A request cache cannot go stale in the first place.
     *
     * It needs no size limit either. Keyed by competency rather than by user, it is bounded by
     * the handful of competencies a course restricts on, and a task walking through many users
     * keeps reusing the same few entries instead of adding one per user.
     *
     * @var array
     */
    protected static $competencies = [];

    /**
     * Competency ID this condition requires, or {@see self::COMPETENCYID_NOTRESTORED}.
     *
     * The ID is global rather than scoped to a course. That is what lets a condition require a
     * competency which belongs to another course, and equally what forces
     * {@see self::update_after_restore()} to remap it when a course is restored onto another site,
     * where the same number means a different competency.
     *
     * It is also the entire state of the condition. {@see self::save()} writes nothing else into
     * the availability tree, so every change here changes what is stored on the restricted item.
     *
     * @var int
     */
    protected $competencyid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Availability structure from JSON.
     * @throws \coding_exception If the structure carries no usable competency ID.
     */
    public function __construct($structure) {
        // A missing or non-numeric value casts to 0 and is rejected below, so there is no need to
        // tell the two cases apart.
        $competencyid = isset($structure->competencyid) ? (int) $structure->competencyid : 0;

        // Only the restore marker is allowed to be negative. Everything else has to be a real ID,
        // as IDs start at 1.
        if ($competencyid !== self::COMPETENCYID_NOTRESTORED && $competencyid <= 0) {
            throw new \coding_exception('Invalid competencyid in availability structure');
        }

        $this->competencyid = $competencyid;
    }

    /**
     * Save for JSON encoding.
     *
     * @return \stdClass
     */
    public function save() {
        return (object) [
            'type' => 'competencies',
            'competencyid' => $this->competencyid,
        ];
    }

    /**
     * Whether the user meets the competency proficiency requirement.
     *
     * Reads the global {@see \core_competency\user_competency} record directly (not
     * course-scoped APIs). The public API enforces view capabilities unsuitable for
     * availability evaluation for learners.
     *
     * @param bool $not Invert the condition.
     * @param info $info Availability info.
     * @param bool $grabthelot Performance hint, not read because what it asks for is done anyway,
     *      see {@see self::get_proficiencies()}.
     * @param int $userid User to check.
     * @return bool
     */
    public function is_available($not, info $info, $grabthelot, $userid) {
        // Fails closed: when the condition cannot be evaluated at all, access is denied.
        if (!$this->is_evaluable()) {
            return false;
        }

        // Check if the student is proficient.
        $allow = $this->is_proficient($userid);
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Whether the condition can be answered at all.
     *
     * All three reasons live here: Whether the competency system is enabled at all, whether the competency ID
     * is a restore marker and whether the competency could be retrieved at all.
     *
     * The order is deliberate. Config and memory answer first, so the lookup in
     * {@see self::get_competency()} only runs when it has to, and it is served from the static
     * cache once per competency and request.
     *
     * @return bool
     */
    protected function is_evaluable(): bool {
        if (!\core_competency\api::is_enabled()) {
            return false;
        }
        if ($this->competencyid === self::COMPETENCYID_NOTRESTORED) {
            return false;
        }
        return $this->get_competency() !== false;
    }

    /**
     * Description shown for this restriction.
     *
     * @param bool $full Full description.
     * @param bool $not Inverted.
     * @param info $info Availability info.
     * @return string
     */
    public function get_description($full, $not, info $info) {
        $name = $this->get_competency_display_name();
        if ($name === '') {
            // Core wraps this in "Not available unless:", so the message has to read as a
            // condition rather than as an error, otherwise it promises that losing the competency
            // is what opens the item up. It says the same thing in both directions on purpose: the
            // condition denies access either way round, see {@see self::is_available()}.
            return get_string('requirescompetencyunknown', 'availability_competencies');
        }
        $identifier = $not ? 'requirescompetency_not' : 'requirescompetency';
        return get_string($identifier, 'availability_competencies', $name);
    }

    /**
     * Debug representation.
     *
     * @return string
     */
    protected function get_debug_string() {
        return 'competencyid=' . $this->competencyid;
    }

    /**
     * Remap the competency ID after a restore.
     *
     * Competency IDs are site-global and are therefore not meaningful on another site. Core maps
     * them by framework and competency ID number, but only when the backup contained the course
     * competencies and both exist on the target site.
     *
     * @param string $restoreid Restore ID.
     * @param int $courseid Target course ID.
     * @param \base_logger $logger Restore logger.
     * @param string $name Name of the restored item, for logging.
     * @return bool Whether the condition was changed and has to be saved.
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        if ($this->competencyid === self::COMPETENCYID_NOTRESTORED) {
            // An earlier restore already flagged this condition, there is nothing left to map.
            return false;
        }
        $rec = \restore_dbops::get_backup_ids_record($restoreid, \core_competency\competency::TABLE, $this->competencyid);
        if (!$rec || !$rec->newitemid) {
            // Without a mapping, keep the ID if the competency still exists. This covers restoring
            // into the same site (e.g. duplicating a course) and restoring a backup which did not
            // include the course competencies.
            if ($this->get_competency() !== false) {
                return false;
            }
            // Otherwise the competency is gone and we can only warn about it.
            $this->competencyid = self::COMPETENCYID_NOTRESTORED;
            $logger->process(
                'Restored item (' . $name . ') has availability condition on a competency that was not restored',
                \backup::LOG_WARNING
            );
        } else {
            $this->competencyid = (int) $rec->newitemid;
        }
        return true;
    }

    /**
     * Check global proficiency for the configured competency.
     *
     * Only call this once {@see self::is_evaluable()} has confirmed that the question can be
     * answered at all.
     *
     * @param int $userid User ID.
     * @return bool
     */
    protected function is_proficient(int $userid): bool {
        return self::get_proficiencies($userid)[$this->competencyid] ?? false;
    }

    /**
     * Proficiency of a user for every competency they hold.
     *
     * The whole set is read at once and cached, because a course page asks about one competency
     * after another, and because competencies are global: a condition may well name one which
     * belongs to a different course.
     *
     * This happens whether or not core passes the $grabthelot hint. The hint exists so that a
     * plugin can weigh a cheap single lookup against an expensive bulk one, but that trade-off
     * does not arise here: the result outlives the request, so reading everything once is the
     * better deal even for a single question, and every later one is free.
     *
     * {@see \availability_competencies\observer} drops the entry of a user as soon as evidence is
     * added for them, so a learner who has just become proficient sees the item unlock right away.
     *
     * @param int $userid User ID.
     * @return array Proficiency as competencyid => bool, missing when the user does not hold it.
     */
    protected static function get_proficiencies(int $userid): array {
        $cache = \cache::make('availability_competencies', 'proficiencies');
        $proficiencies = $cache->get($userid);
        // A user holding nothing is stored as an empty array, which is a hit and not a miss.
        if ($proficiencies === false) {
            $proficiencies = [];
            foreach (\core_competency\user_competency::get_records(['userid' => $userid]) as $usercompetency) {
                $proficiencies[(int) $usercompetency->get('competencyid')] =
                    (bool) $usercompetency->get('proficiency');
            }
            $cache->set($userid, $proficiencies);
        }
        return $proficiencies;
    }

    /**
     * Wipes the static cache used to store competency records.
     */
    public static function wipe_static_cache(): void {
        self::$competencies = [];
    }

    /**
     * The competency record this condition points at.
     *
     * A condition can outlive the competency it names. Core does refuse to delete a competency
     * which is in use, but it only counts frameworks, plans, templates, badges, course links and
     * user records ({@see \core_competency\competency::can_all_be_deleted()}). Availability
     * conditions are not among them, so unlinking a competency from a course makes it deletable
     * while conditions still point at it. Callers therefore have to handle false.
     *
     * All callers need the same row, and a course page asks for it once per restricted item, so
     * the result is kept in {@see self::$competencies} for the rest of the request. Several items
     * restricted by the same competency share the one query.
     *
     * @return \core_competency\competency|false False when there is no such competency.
     */
    protected function get_competency() {
        if ($this->competencyid === self::COMPETENCYID_NOTRESTORED) {
            // The marker stands for "no competency", it is not an ID to search for.
            return false;
        }
        if (!array_key_exists($this->competencyid, self::$competencies)) {
            self::$competencies[$this->competencyid] =
                \core_competency\competency::get_record(['id' => $this->competencyid]);
        }
        return self::$competencies[$this->competencyid];
    }

    /**
     * Human-readable competency name for descriptions.
     *
     * It is not safe to call format_string() here because descriptions are built while the
     * modinfo cache is populated. The name is therefore wrapped in the placeholder which
     * {@see \core_availability\info::format_info()} resolves later in the course context.
     *
     * @return string Empty when competency is missing.
     */
    protected function get_competency_display_name(): string {
        if (!\core_competency\api::is_enabled()) {
            return '';
        }
        $competency = $this->get_competency();
        if (!$competency) {
            return '';
        }
        return self::description_format_string($competency->get('shortname'));
    }
}
