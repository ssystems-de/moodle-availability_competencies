@availability @availability_competencies @availability_competencies_permissions @javascript
Feature: Control who can add competency restrictions and when they are offered
  In order to keep the competency restriction usable and predictable
  As a teacher or manager
  I need the restriction to be offered only where it can actually be configured

  Background:
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following config values are set as admin:
      | enabled | 1 | core_competency |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | manager1 | Manager   | One      |
      | learner1 | Learner   | One      |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | manager1 | C1     | manager        |
      | learner1 | C1     | student        |
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

  Scenario: A teacher can add the restriction
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    Then I should see "Competency" in the "Add restriction..." "dialogue"

  Scenario: A manager can add the restriction
    Given I am on the "Page 1" "page activity editing" page logged in as "manager1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    Then I should see "Competency" in the "Add restriction..." "dialogue"

  Scenario: The restriction can be added without the competency management capability
    Given the following "permission overrides" exist:
      | capability                               | permission | role           | contextlevel | reference |
      | moodle/competency:coursecompetencymanage | Prohibit   | editingteacher | Course       | C1        |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    Then the "Competency" select box should contain "COMP1"

  Scenario: A teacher without the addinstance capability cannot add the restriction, but an existing one still applies
    Given the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |
    And the following "permission overrides" exist:
      | capability                            | permission | role           | contextlevel | reference |
      | availability/competencies:addinstance | Prohibit   | editingteacher | Course       | C1        |
    When I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then I should not see "Competency" in the "Add restriction..." "dialogue"
    And I log out
    And I am on the "Course 1" course page logged in as "learner1"
    And I should see "Not available unless: You have achieved the competency COMP1" in the "Page 1" "core_availability > Activity availability"

  Scenario: An existing restriction stays visible and removable without the capability
    Given the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |
    And the following "permission overrides" exist:
      | capability                            | permission | role           | contextlevel | reference |
      | availability/competencies:addinstance | Prohibit   | editingteacher | Course       | C1        |
    When I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    # The picker keeps offering the course competencies even without the capability, so the stored
    # one stays preselected. Were it missing from the list, the form would drop the value and then
    # refuse to save the activity at all.
    Then the field "Competency" matches value "COMP1"
    And I should not see "(invalid competency)" in the ".availability-competencies" "css_element"
    And ".availability-item .availability-delete" "css_element" should exist

  Scenario: The restriction is not offered in a course without competencies
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 2 | C2        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C2     | editingteacher |
    And the following "activities" exist:
      | activity | course | section | name   |
      | page     | C2     | 1       | Page 2 |
    And I am on the "Page 2" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    Then I should not see "Competency" in the "Add restriction..." "dialogue"

  Scenario: Disabling competencies site-wide withdraws the restriction and keeps restricted items closed
    Given the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |
    And the following "core_competency > user_competency" exist:
      | competency | user     | grade |
      | COMP1      | learner1 | Great |
    When the following config values are set as admin:
      | enabled | 0 | core_competency |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then I should not see "Competency" in the "Add restriction..." "dialogue"
    And I log out
    # Even the proficient learner is locked out, because the condition cannot be evaluated anymore.
    And I am on the "Course 1" course page logged in as "learner1"
    # The course page shortens availability info beyond 100 characters into a "Show more" box, and
    # this message is longer than that, so the full reason only becomes visible after opening it.
    And I click on "Show more" "button" in the "Page 1" "core_availability > Activity availability"
    And I should see "You have achieved a competency (which is, unfortunately, no longer available and thus cannot be achieved at all)" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should not exist in the "region-main" "region"
