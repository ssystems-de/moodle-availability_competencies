@availability @availability_competencies @availability_competencies_form @javascript
Feature: Configure the competency restriction in the availability form
  In order to set up competency restrictions without surprises
  As a teacher
  I need the competency picker to validate my input, to remember my choice and to flag competencies it cannot offer

  Background:
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following config values are set as admin:
      | enabled | 1 | core_competency |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
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

  Scenario: Saving the form without a selected competency is rejected
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    When I press "Save and return to course"
    Then I should see "Select a competency." in the "#id_error_availabilityconditionsjson" "css_element"
    And "Add restriction..." "button" should exist

  Scenario: The saved competency is preselected when the restriction is edited again
    Given the following "core_competency > competencies" exist:
      | shortname | idnumber | competencyframework |
      | COMP2     | COMP2    | FW1                 |
    And the following "core_competency > course_competencies" exist:
      | course | competency |
      | C1     | COMP2      |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    And I set the field "Competency" to "COMP1"
    And I press "Save and return to course"
    When I am on the "Page 1" "page activity editing" page
    And I expand all fieldsets
    Then the field "Competency" matches value "COMP1"
    # Switching to another competency has to be stored as well.
    And I set the field "Competency" to "COMP2"
    And I press "Save and return to course"
    And I am on the "Page 1" "page activity editing" page
    And I expand all fieldsets
    And the field "Competency" matches value "COMP2"

  Scenario: A multilang competency name is offered in the current language
    Given the "multilang" filter is "on"
    And the "multilang" filter applies to "content and headings"
    And the following "core_competency > competencies" exist:
      | shortname                                                                                        | idnumber | competencyframework |
      | <span lang="en" class="multilang">English</span><span lang="de" class="multilang">Deutsch</span> | COMP2    | FW1                 |
    And the following "core_competency > course_competencies" exist:
      | course | competency |
      | C1     | COMP2      |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    Then the "Competency" select box should contain "English"
    And the "Competency" select box should not contain "Deutsch"

  Scenario: A competency which is no longer linked to the course is flagged as invalid
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Competency" "button" in the "Add restriction..." "dialogue"
    And I set the field "Competency" to "COMP1"
    And I press "Save and return to course"
    # Unlinking the competency from the course leaves the stored condition pointing at a
    # competency which the picker does not offer anymore.
    And I navigate to "Competencies" in current page administration
    And I click on "[data-action=delete-competency-link]" "css_element"
    And I click on "Confirm" "button" in the "Confirm" "dialogue"
    When I am on the "Page 1" "page activity editing" page
    And I expand all fieldsets
    Then I should see "(invalid competency)" in the ".availability-competencies" "css_element"
    And the "Competency" select box should not contain "COMP1"

  # The picker offers what the course has to offer, so it can end up empty while a condition is
  # already stored: the competency was unlinked from the course or, as here, competencies were
  # switched off site-wide. The stored competency then cannot be preselected, and the form asks for
  # a selection which cannot be made. Removing the restriction is the way out.
  Scenario: An activity whose competency cannot be offered anymore is only saveable without the restriction
    Given the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |
    And the following config values are set as admin:
      | enabled | 0 | core_competency |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    And I should see "(invalid competency)" in the ".availability-competencies" "css_element"
    When I press "Save and return to course"
    Then I should see "Select a competency." in the "#id_error_availabilityconditionsjson" "css_element"
    And "Add restriction..." "button" should exist
    When I click on ".availability-item .availability-delete img" "css_element"
    And I press "Save and return to course"
    # Leaving the form behind is what proves that the activity was saved this time.
    Then "Add restriction..." "button" should not exist
    And I should not see "Select a competency."
