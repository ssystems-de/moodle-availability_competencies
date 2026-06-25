@availability @availability_competencies @availability_competencies_restrict @javascript
Feature: Restrict access by competency
  In order to control learning paths by competency achievement
  As a teacher
  I need competency-based availability restrictions on activities and sections

  Background:
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following config values are set as admin:
      | enabled | 1 | core_competency |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | learner1 | Learner   | One      |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
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
      | activity | course | name   |
      | page     | C1     | Page 1 |

  Scenario: Learner without competency cannot access restricted activity
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I set the following fields to these values:
      | Name         | Restricted |
      | Description  | x          |
      | Page content | x          |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    And I set the field "Competency" to "COMP1"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "Course 1" course page logged in as "learner1"
    Then "Restricted" activity should be hidden

  Scenario: Proficient learner can access restricted activity
    Given the following "core_competency > user_competency" exist:
      | competency | user     | grade |
      | COMP1      | learner1 | Great |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I set the following fields to these values:
      | Name         | Restricted |
      | Description  | x          |
      | Page content | x          |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    And the "Competency" select box should contain "Choose..."
    And I set the field "Competency" to "COMP1"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Page 1" in the "region-main" "region"
    And "Restricted" activity should be visible

  Scenario: Section restriction applies to section content
    Given the following "activities" exist:
      | activity | course | section | name   |
      | page     | C1     | 1       | Page 3 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I edit the section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    And I set the field "Competency" to "COMP1"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save changes"
    And I log out
    When I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Page 1" in the "region-main" "region"
    And I should not see "Page 3" in the "region-main" "region"
