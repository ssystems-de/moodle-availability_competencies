@availability @availability_competencies @availability_competencies_backup @javascript
Feature: Copy courses which contain competency restrictions
  In order to reuse a course without rebuilding its learning path
  As a teacher
  I need competency restrictions to keep working after duplicating, backing up and restoring

  Background:
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following config values are set as admin:
      | enabled | 1 | core_competency |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | learner1 | Learner   | One      |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner1 | C2     | student        |
    And the following "scales" exist:
      | name       | scale            |
      | Test Scale | Bad, Good, Great |
    And the following "core_competency > frameworks" exist:
      | shortname | idnumber | scale      |
      | Framework | FW1      | Test Scale |
    And the following "core_competency > competencies" exist:
      | shortname | idnumber | competencyframework |
      | COMP1     | COMP1    | FW1                 |
    And the following "core_competency > course_competencies" exist:
      | course | competency |
      | C1     | COMP1      |
    And the following "activities" exist:
      | activity | course | section | name   | idnumber |
      | page     | C1     | 1       | Page 1 | PAGE1    |
    And the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |

  Scenario: A duplicated activity keeps its competency restriction
    Given I am on the "Course 1" course page logged in as "teacher1"
    And I turn editing mode on
    When I duplicate "Page 1" activity
    And I log out
    And I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Not available unless: You have achieved the competency COMP1" in the "Page 1 (copy)" "core_availability > Activity availability"
    And "Page 1 (copy)" "link" should not exist in the "region-main" "region"

  Scenario: The competency restriction survives a restore into another course of the same site
    Given I am on the "Course 1" course page logged in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into "Course 2" course using this options:
    And I log out
    And I am on the "Course 2" course page logged in as "learner1"
    # Naming the competency proves that the restore mapped it instead of flagging it as unknown.
    Then I should see "Not available unless: You have achieved the competency COMP1" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should not exist in the "region-main" "region"

  # The fixture backup comes from another site and holds one course with a page restricted by a
  # competency, plus the course competencies which carry the idnumbers FIXTUREFW and FIXTURECOMP.
  # On that site the competency had ID 2. No competency of a Behat test site can carry that ID, so a
  # restriction which names a competency here can only have got there through the mapping by
  # idnumber which core builds during the restore. The scenario below it shows the other half:
  # without a competency to map onto, the same backup leaves the restriction unanswerable.
  @_file_upload
  Scenario: A restriction restored from another site is mapped to the competency of this site
    Given the following "core_competency > frameworks" exist:
      | shortname       | idnumber  | scale      |
      | Local framework | FIXTUREFW | Test Scale |
    And the following "core_competency > competencies" exist:
      | shortname        | idnumber    | competencyframework |
      | Local competency | FIXTURECOMP | FIXTUREFW           |
    And I am on the "Course 2" "restore" page logged in as "admin"
    And I press "Manage course backups"
    And I upload "availability/condition/competencies/tests/fixtures/restricted_by_competency.mbz" file to "Files" filemanager
    And I press "Save changes"
    When I merge "restricted_by_competency.mbz" backup into the current course using this options:
    And I log out
    And I am on the "Course 2" course page logged in as "learner1"
    Then I should see "Not available unless: You have achieved the competency Local competency" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should not exist in the "region-main" "region"

  @_file_upload
  Scenario: A restriction restored from another site without its competency stays closed
    Given I am on the "Course 2" "restore" page logged in as "admin"
    And I press "Manage course backups"
    And I upload "availability/condition/competencies/tests/fixtures/restricted_by_competency.mbz" file to "Files" filemanager
    And I press "Save changes"
    When I merge "restricted_by_competency.mbz" backup into the current course using this options:
    And I log out
    And I am on the "Course 2" course page logged in as "learner1"
    # Nothing here matches the idnumbers of the backup, so the restriction has to stay in place
    # as an unanswerable one rather than open the activity up. The course page shortens
    # availability info beyond 100 characters, so the full reason needs opening first.
    Then I click on "Show more" "button" in the "Page 1" "core_availability > Activity availability"
    And I should see "You have achieved a competency (which is, unfortunately, no longer available and thus cannot be achieved at all)" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should not exist in the "region-main" "region"
