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
 * Availability Competencies - Test for the plugin form frontend.
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_competencies;

/**
 * Unit tests for the frontend.
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \availability_competencies\frontend
 */
final class frontend_test extends \advanced_testcase {
    /**
     * Enable competencies for tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Enable the competency subsystem.
        set_config('enabled', 1, 'core_competency');
    }

    /**
     * allow_add is false when competencies are disabled.
     *
     * @covers ::allow_add
     */
    public function test_allow_add_false_when_competencies_disabled(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->create_competency_linked_to_courses([$course->id]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Act as a teacher who would otherwise be allowed, so that only the disabled subsystem
        // can be the reason for the refusal.
        $this->setUser($teacher);
        set_config('enabled', 0, 'core_competency');

        $this->assertFalse($this->allow_add($course));
    }

    /**
     * allow_add is false when the user lacks addinstance capability.
     *
     * @covers ::allow_add
     */
    public function test_allow_add_false_without_capability(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->create_competency_linked_to_courses([$course->id]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $this->assertFalse($this->allow_add($course));
    }

    /**
     * allow_add is true for a teacher in a course which has competencies.
     *
     * @covers ::allow_add
     */
    public function test_allow_add_true_for_teacher(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->create_competency_linked_to_courses([$course->id]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        $this->setUser($teacher);

        $this->assertTrue($this->allow_add($course));
    }

    /**
     * allow_add is false when no competency is linked to the course.
     *
     * @covers ::allow_add
     */
    public function test_allow_add_false_without_course_competencies(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        // The site does have a competency, it is just not linked to this course.
        $this->create_competency_linked_to_courses([]);

        $this->setUser($teacher);

        $this->assertFalse($this->allow_add($course));
    }

    /**
     * The picker options are sorted by language and handed over as a plain list.
     *
     * @covers ::get_course_competency_options
     */
    public function test_options_are_sorted_and_a_list(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->create_competencies_in_course($course->id, ['Zebra', 'Ähnlich', 'Beta']);

        $this->setUser($teacher);

        $frontend = new frontend();
        $method = new \ReflectionMethod(frontend::class, 'get_javascript_init_params');
        $method->setAccessible(true);
        $options = $method->invoke($frontend, $course, null, null)[0];

        $names = array_column($options, 'name');

        // A name starting with an umlaut belongs next to A, not behind Z as a byte sort puts it.
        $this->assertLessThan(array_search('Zebra', $names), array_search('Ähnlich', $names));
        $this->assertLessThan(array_search('Zebra', $names), array_search('Beta', $names));

        // The YUI form reads this as a JavaScript array, so the keys have to be a plain sequence.
        $this->assertTrue(array_is_list($options), 'Options must be a list, not a keyed array');
    }

    /**
     * Create several competencies with given shortnames and link them to a course.
     *
     * @param int $courseid Course ID.
     * @param string[] $shortnames Competency shortnames.
     */
    protected function create_competencies_in_course(int $courseid, array $shortnames): void {
        $generator = $this->getDataGenerator();
        /** @var \core_competency_generator $compgenerator */
        $compgenerator = $generator->get_plugin_generator('core_competency');
        $framework = $compgenerator->create_framework();
        foreach ($shortnames as $shortname) {
            $competency = $compgenerator->create_competency([
                'competencyframeworkid' => $framework->get('id'),
                'shortname' => $shortname,
            ]);
            $compgenerator->create_course_competency([
                'courseid' => $courseid,
                'competencyid' => $competency->get('id'),
            ]);
        }
    }

    /**
     * Call the protected allow_add() of the frontend.
     *
     * @param \stdClass $course Course.
     * @param \cm_info|null $cm Module.
     * @param \section_info|null $section Section.
     * @return bool
     */
    protected function allow_add(\stdClass $course, ?\cm_info $cm = null, ?\section_info $section = null): bool {
        $frontend = new frontend();
        $method = new \ReflectionMethod(frontend::class, 'allow_add');
        $method->setAccessible(true);
        return (bool) $method->invoke($frontend, $course, $cm, $section);
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
}
