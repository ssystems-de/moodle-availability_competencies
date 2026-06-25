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
 * Availability Competencies - Test for plugin restrictions.
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_competencies;

use core_availability\info_module;

/**
 * Unit tests for the condition.
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \availability_competencies\condition
 */
final class condition_test extends \advanced_testcase {
    /** @var bool Whether the backup_ids_temp table was created by this test. */
    protected $backupidstemp = false;

    /**
     * Enable competencies for tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Enable the competency subsystem.
        set_config('enabled', 1, 'core_competency');

        // The database is rolled back by resetAfterTest(), but static state is not.
        condition::wipe_static_cache();
    }

    /**
     * Drop the backup_ids_temp table again if a test created it.
     */
    protected function tearDown(): void {
        if ($this->backupidstemp) {
            \backup_controller_dbops::drop_backup_ids_temp_table('');
            $this->backupidstemp = false;
        }
        parent::tearDown();
    }

    /**
     * Proficient learner is allowed access.
     *
     * @covers ::is_available
     */
    public function test_proficient_user_is_allowed(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $this->set_user_proficient($user->id, $competency->get('id'));

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertTrue($condition->is_available(false, $info, false, $user->id));
    }

    /**
     * Non-proficient learner is denied access.
     *
     * @covers ::is_available
     */
    public function test_non_proficient_user_is_denied(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertFalse($condition->is_available(false, $info, false, $user->id));
    }

    /**
     * In-progress competency (proficiency false) does not grant access.
     *
     * @covers ::is_available
     */
    public function test_in_progress_user_is_denied(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $this->set_user_in_progress($user->id, $competency->get('id'));

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertFalse($condition->is_available(false, $info, false, $user->id));
    }

    /**
     * Global proficiency in another course grants access (MDL-76037).
     *
     * @covers ::is_available
     */
    public function test_cross_course_proficiency(): void {
        $generator = $this->getDataGenerator();
        $coursea = $generator->create_course();
        $courseb = $generator->create_course();
        $user = $generator->create_user();

        $competency = $this->create_competency_linked_to_courses([$coursea->id, $courseb->id]);
        $this->set_user_proficient($user->id, $competency->get('id'));

        $pageb = $generator->create_module('page', ['course' => $courseb->id]);
        $modinfo = get_fast_modinfo($courseb);
        $cm = $modinfo->get_cm($pageb->cmid);

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertTrue($condition->is_available(false, $info, false, $user->id));
    }

    /**
     * Disabled competency subsystem fails closed.
     *
     * @covers ::is_available
     */
    public function test_competencies_disabled_fails_closed(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $this->set_user_proficient($user->id, $competency->get('id'));
        set_config('enabled', 0, 'core_competency');

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertFalse($condition->is_available(false, $info, false, $user->id));

        // Inverting an unanswerable condition must not open the item up.
        $this->assertFalse($condition->is_available(true, $info, false, $user->id));
    }

    /**
     * A competency which was not resolved during restore fails closed.
     *
     * @covers ::is_available
     */
    public function test_unrestored_competency_fails_closed(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition(condition::COMPETENCYID_NOTRESTORED);
        $info = new info_module($cm);

        $this->assertFalse($condition->is_available(false, $info, false, $user->id));
        $this->assertFalse($condition->is_available(true, $info, false, $user->id));
    }

    /**
     * An evaluable condition still inverts normally.
     *
     * @covers ::is_available
     */
    public function test_inverted_condition_inverts(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $notproficient = $this->getDataGenerator()->create_user();
        $this->set_user_proficient($user->id, $competency->get('id'));

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertFalse($condition->is_available(true, $info, false, $user->id));
        $this->assertTrue($condition->is_available(true, $info, false, $notproficient->id));
    }

    /**
     * Invalid competency reference fails closed.
     *
     * @covers ::is_available
     */
    public function test_invalid_competency_fails_closed(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);

        $condition = $this->create_condition(999999);
        $info = new info_module($cm);

        $this->assertFalse($condition->is_available(false, $info, false, $user->id));

        // Inverting must not turn a dangling reference into access for everybody.
        $this->assertFalse($condition->is_available(true, $info, false, $user->id));
    }

    /**
     * The competency record is looked up once per request, not once per restricted item.
     *
     * @covers ::get_description
     */
    public function test_competency_record_is_read_once(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $info = new info_module($cm);

        // Several items restricted by the same competency, as on a real course page.
        $first = $this->create_condition($competency->get('id'));
        $second = $this->create_condition($competency->get('id'));

        $first->get_description(false, false, $info);

        $before = $this->db_reads();
        $second->get_description(false, false, $info);
        $second->get_description(false, true, $info);

        $this->assertSame($before, $this->db_reads(), 'Further descriptions must come from cache');
    }

    /**
     * Number of database reads so far.
     *
     * @return int
     */
    protected function db_reads(): int {
        global $DB;
        return $DB->perf_get_reads();
    }

    /**
     * A competency deleted after the condition was set fails closed in both directions.
     *
     * Core allows the deletion once the competency is unlinked from the course and nobody holds
     * it, so a condition can outlive the competency it points at.
     *
     * @covers ::is_available
     */
    public function test_deleted_competency_fails_closed(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $competencyid = (int) $competency->get('id');

        $condition = $this->create_condition($competencyid);
        $info = new info_module($cm);

        // Unlink it from the course, which is what makes core agree to delete it.
        $this->setAdminUser();
        \core_competency\api::remove_competency_from_course($course->id, $competencyid);
        $this->assertTrue(\core_competency\api::delete_competency($competencyid));
        condition::wipe_static_cache();

        $this->assertFalse($condition->is_available(false, $info, false, $user->id));
        $this->assertFalse($condition->is_available(true, $info, false, $user->id));
    }

    /**
     * One query serves any number of competencies, held or not.
     *
     * This is also the only test which proves that proficiency is cached at all. Its companion
     * test_rating_a_competency_invalidates_the_cache() would stay green without any cache, since
     * a fresh read would answer correctly too. Do not remove this one as a mere performance test.
     *
     * @covers ::is_available
     */
    public function test_all_competencies_are_read_at_once(): void {
        global $DB;

        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        // A second one the user does not hold, which is the normal state of a learner who has not
        // got there yet.
        $unheldcompetency = $this->create_competency_linked_to_courses([$course->id]);
        $this->set_user_proficient($user->id, $competency->get('id'));

        $condition = $this->create_condition($competency->get('id'));
        $unheldcondition = $this->create_condition($unheldcompetency->get('id'));
        $info = new info_module($cm);

        $before = $DB->perf_get_reads();
        $this->assertTrue($condition->is_available(false, $info, true, $user->id));
        $afterfirst = $DB->perf_get_reads();
        $this->assertFalse($unheldcondition->is_available(false, $info, true, $user->id));
        $second = $DB->perf_get_reads() - $afterfirst;

        $this->assertGreaterThan($before, $afterfirst);
        // The second condition only looks up its own competency record. Its proficiency came out
        // of the first call, which read the whole set. A second read here would mean proficiency
        // is being fetched per competency again.
        $this->assertSame(1, $second, 'The second condition must not read proficiency again');
    }

    /**
     * Users do not share cached proficiency.
     *
     * @covers ::is_available
     */
    public function test_cache_is_per_user(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $this->set_user_proficient($user->id, $competency->get('id'));

        $otheruser = $this->getDataGenerator()->create_user();

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $this->assertTrue($condition->is_available(false, $info, true, $user->id));
        $this->assertFalse($condition->is_available(false, $info, true, $otheruser->id));
    }

    /**
     * Becoming proficient unlocks the item straight away.
     *
     * This is what the whole cache stands or falls by: it outlives the request, so the observer
     * has to drop the entry of the user as soon as evidence is added for them.
     *
     * @covers \availability_competencies\observer::competency_evidence_created
     */
    public function test_rating_a_competency_invalidates_the_cache(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        // Ask first, so that "not proficient" is what sits in the cache.
        $this->assertFalse($condition->is_available(false, $info, true, $user->id));

        $this->setAdminUser();
        \core_competency\api::grade_competency($user->id, $competency->get('id'), 3);

        $this->assertTrue($condition->is_available(false, $info, true, $user->id));
    }

    /**
     * Core refuses to delete a competency which is still in use.
     *
     * is_proficient() relies on this invariant to skip an existence check on every evaluation.
     * Should core ever start deleting competencies with user_competency records behind them,
     * the check has to come back, because an orphaned record would then grant access.
     *
     * @coversNothing
     */
    public function test_competency_in_use_cannot_be_deleted(): void {
        $user = $this->getDataGenerator()->create_user();
        $competency = $this->create_competency_linked_to_courses([]);

        // Unused so far, so nothing stops core from deleting it.
        $this->assertTrue(\core_competency\competency::can_all_be_deleted([$competency->get('id')]));

        $this->set_user_proficient($user->id, $competency->get('id'));

        // Now a user holds it, and core refuses.
        $this->assertFalse(\core_competency\competency::can_all_be_deleted([$competency->get('id')]));
    }

    /**
     * Description names the required competency.
     *
     * @covers ::get_description
     */
    public function test_description(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $name = condition::description_format_string($competency->get('shortname'));
        $this->assertSame(
            get_string('requirescompetency', 'availability_competencies', $name),
            $condition->get_description(false, false, $info)
        );
    }

    /**
     * The competency name is wrapped for delayed format_string() and resolves on display.
     *
     * @covers ::get_description
     */
    public function test_description_defers_format_string(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);
        $description = $condition->get_description(false, false, $info);

        $this->assertStringContainsString('<AVAILABILITY_FORMAT_STRING>', $description);
        $this->assertStringContainsString(
            $competency->get('shortname'),
            \core_availability\info::format_info($description, $course)
        );
        $this->assertStringNotContainsString(
            '<AVAILABILITY_FORMAT_STRING>',
            \core_availability\info::format_info($description, $course)
        );
    }

    /**
     * Inverted description states that the competency must not have been achieved.
     *
     * @covers ::get_description
     */
    public function test_description_inverted(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition($competency->get('id'));
        $info = new info_module($cm);

        $name = condition::description_format_string($competency->get('shortname'));
        $this->assertSame(
            get_string('requirescompetency_not', 'availability_competencies', $name),
            $condition->get_description(false, true, $info)
        );
        $this->assertNotSame(
            $condition->get_description(false, false, $info),
            $condition->get_description(false, true, $info)
        );
    }

    /**
     * A missing competency yields the same description in both directions.
     *
     * @covers ::get_description
     */
    public function test_description_unknown_competency(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();

        $condition = $this->create_condition(999999);
        $info = new info_module($cm);
        $expected = get_string('requirescompetencyunknown', 'availability_competencies');

        $this->assertSame($expected, $condition->get_description(false, false, $info));
        $this->assertSame($expected, $condition->get_description(false, true, $info));
    }

    /**
     * save() round-trips competencyid.
     *
     * @covers ::save
     */
    public function test_save_roundtrip(): void {
        $condition = $this->create_condition(42);
        $saved = $condition->save();
        $this->assertSame('competencies', $saved->type);
        $this->assertSame(42, $saved->competencyid);
    }

    /**
     * Restore rewrites the competency ID to the mapped one.
     *
     * @covers ::update_after_restore
     */
    public function test_update_after_restore_maps_competency(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $target = $this->create_competency_linked_to_courses([$course->id]);
        $restoreid = $this->create_restore_id();
        \restore_dbops::set_backup_ids_record(
            $restoreid,
            \core_competency\competency::TABLE,
            $competency->get('id'),
            $target->get('id')
        );

        $condition = $this->create_condition($competency->get('id'));
        $logger = new \core_backup_html_logger(\backup::LOG_WARNING);

        $this->assertTrue($condition->update_after_restore($restoreid, $course->id, $logger, 'Page 1'));
        $this->assertSame((int) $target->get('id'), $condition->save()->competencyid);
    }

    /**
     * Without a mapping, an existing competency is kept (restore into the same site).
     *
     * @covers ::update_after_restore
     */
    public function test_update_after_restore_keeps_existing_competency(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $restoreid = $this->create_restore_id();

        $condition = $this->create_condition($competency->get('id'));
        $logger = new \core_backup_html_logger(\backup::LOG_WARNING);

        $this->assertFalse($condition->update_after_restore($restoreid, $course->id, $logger, 'Page 1'));
        $this->assertSame((int) $competency->get('id'), $condition->save()->competencyid);
        $this->assertStringNotContainsString('was not restored', $logger->get_html());
    }

    /**
     * An unmappable competency is flagged, logged and keeps denying access afterwards.
     *
     * @covers ::update_after_restore
     */
    public function test_update_after_restore_flags_missing_competency(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $restoreid = $this->create_restore_id();

        $condition = $this->create_condition(999999);
        $logger = new \core_backup_html_logger(\backup::LOG_WARNING);

        $this->assertTrue($condition->update_after_restore($restoreid, $course->id, $logger, 'Page 1'));
        $this->assertSame(condition::COMPETENCYID_NOTRESTORED, $condition->save()->competencyid);
        $this->assertStringContainsString('was not restored', $logger->get_html());

        // The flagged condition must survive being saved and loaded again.
        $reloaded = new condition($condition->save());
        $info = new info_module($cm);
        $this->assertSame(condition::COMPETENCYID_NOTRESTORED, $reloaded->save()->competencyid);
        $this->assertFalse($reloaded->is_available(false, $info, false, $user->id));
        $this->assertSame(
            get_string('requirescompetencyunknown', 'availability_competencies'),
            $reloaded->get_description(false, false, $info)
        );
    }

    /**
     * A condition flagged by an earlier restore is left alone.
     *
     * @covers ::update_after_restore
     */
    public function test_update_after_restore_ignores_flagged_condition(): void {
        [$course, $cm, $competency, $user] = $this->create_course_with_competency_and_activity();
        $restoreid = $this->create_restore_id();

        $condition = $this->create_condition(condition::COMPETENCYID_NOTRESTORED);
        $logger = new \core_backup_html_logger(\backup::LOG_WARNING);

        $this->assertFalse($condition->update_after_restore($restoreid, $course->id, $logger, 'Page 1'));
        $this->assertSame(condition::COMPETENCYID_NOTRESTORED, $condition->save()->competencyid);
        $this->assertStringNotContainsString('was not restored', $logger->get_html());
    }

    /**
     * The constructor rejects invalid competency IDs but accepts the restore marker.
     *
     * @covers ::__construct
     */
    public function test_constructor_validates_competencyid(): void {
        $this->assertSame(
            condition::COMPETENCYID_NOTRESTORED,
            $this->create_condition(condition::COMPETENCYID_NOTRESTORED)->save()->competencyid
        );

        $this->expectException(\coding_exception::class);
        $this->create_condition(0);
    }

    /**
     * Create a restore ID backed by an initialised backup_ids_temp table.
     *
     * The temporary table has to be dropped again before the test ends, otherwise disposing the
     * database connection fails on a table which resetAfterTest() has already removed.
     *
     * @return string Restore ID.
     */
    protected function create_restore_id(): string {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $restoreid = uniqid('restore');
        \backup_controller_dbops::create_backup_ids_temp_table($restoreid);
        $this->backupidstemp = true;
        return $restoreid;
    }

    /**
     * Create a condition instance.
     *
     * @param int $competencyid Competency ID.
     * @return condition
     */
    protected function create_condition(int $competencyid): condition {
        return new condition((object) [
            'type' => 'competencies',
            'competencyid' => $competencyid,
        ]);
    }

    /**
     * Create course, competency, activity, and user.
     *
     * @return array
     */
    protected function create_course_with_competency_and_activity(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $competency = $this->create_competency_linked_to_courses([$course->id]);
        $page = $generator->create_module('page', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);
        return [$course, $cm, $competency, $user];
    }

    /**
     * Link a new competency to one or more courses.
     *
     * @param int[] $courseids Course IDs.
     * @return \core_competency\competency
     */
    protected function create_competency_linked_to_courses(array $courseids): \core_competency\competency {
        $generator = $this->getDataGenerator();
        /** @var \core_competency_generator $compgenerator */
        $compgenerator = $generator->get_plugin_generator('core_competency');
        $framework = $compgenerator->create_framework();
        $competency = $compgenerator->create_competency([
            'competencyframeworkid' => $framework->get('id'),
            'shortname' => 'Comp ' . uniqid(),
        ]);
        foreach ($courseids as $courseid) {
            $compgenerator->create_course_competency([
                'courseid' => $courseid,
                'competencyid' => $competency->get('id'),
            ]);
        }
        return $competency;
    }

    /**
     * Mark a user proficient in a competency.
     *
     * @param int $userid User ID.
     * @param int $competencyid Competency ID.
     */
    protected function set_user_proficient(int $userid, int $competencyid): void {
        $generator = $this->getDataGenerator();
        /** @var \core_competency_generator $compgenerator */
        $compgenerator = $generator->get_plugin_generator('core_competency');
        $compgenerator->create_user_competency([
            'userid' => $userid,
            'competencyid' => $competencyid,
            'proficiency' => 1,
            'grade' => 1,
        ]);
    }

    /**
     * Mark a user in progress (not proficient) for a competency.
     *
     * @param int $userid User ID.
     * @param int $competencyid Competency ID.
     */
    protected function set_user_in_progress(int $userid, int $competencyid): void {
        $generator = $this->getDataGenerator();
        /** @var \core_competency_generator $compgenerator */
        $compgenerator = $generator->get_plugin_generator('core_competency');
        $compgenerator->create_user_competency([
            'userid' => $userid,
            'competencyid' => $competencyid,
            'proficiency' => 0,
            'grade' => 1,
        ]);
    }
}
