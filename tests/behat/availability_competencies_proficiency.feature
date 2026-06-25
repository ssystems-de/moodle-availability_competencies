@availability @availability_competencies @availability_competencies_proficiency @javascript
Feature: Follow the proficiency of a learner as it changes
  In order to build learning paths across courses
  As a teacher
  I need the restriction to follow the site-wide proficiency of a learner in both directions

  # The condition asks for the site-wide proficiency of a learner, not for a rating inside the
  # course the restricted item belongs to. That is what makes a competency from another course a
  # usable prerequisite, and it is also why a rating anywhere has to reach the cached proficiency of
  # that learner. Unlocking after a rating in the same course is covered in
  # availability_competencies_display.feature.

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
      | Course 2 | C2        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
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
      | C2     | COMP1      |
    And the following "activities" exist:
      | activity | course | section | name   | idnumber |
      | page     | C2     | 1       | Page 2 | PAGE2    |
    And the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE2    | COMP1      |

  Scenario: A rating in another course lifts the restriction
    # Viewing Course 2 fills the proficiency cache of the learner while they hold nothing yet.
    Given I am on the "Course 2" course page logged in as "learner1"
    And I should see "Not available unless: You have achieved the competency COMP1" in the "Page 2" "core_availability > Activity availability"
    And I log out
    # The rating happens in Course 1, which the restricted activity does not belong to.
    When I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Competency breakdown" "link"
    And I click on "Not rated" "link"
    And I click on "Rate" "button"
    And I set the field "Rating" to "Great"
    And I click on "Rate" "button" in the ".competency-grader" "css_element"
    And I click on "Close" "button" in the "User competency summary" "dialogue"
    And I log out
    And I am on the "Course 2" course page logged in as "learner1"
    Then "Page 2" "link" should exist in the "region-main" "region"
    And I should not see "You have achieved the competency COMP1" in the "region-main" "region"

  Scenario: A rating below the proficient level closes the activity again
    Given the following "core_competency > user_competency" exist:
      | competency | user     | grade |
      | COMP1      | learner1 | Great |
    # Viewing Course 2 fills the proficiency cache of the learner while they are still proficient.
    And I am on the "Course 2" course page logged in as "learner1"
    And "Page 2" "link" should exist in the "region-main" "region"
    And I log out
    # Rating down has to reach the learner on their very next page view as well, without any cache
    # purge. The report offers "Not rated" because the rating above was written site-wide only,
    # while the report shows what the learner was rated in this course.
    When I am on the "Course 2" course page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Competency breakdown" "link"
    And I click on "Not rated" "link"
    And I click on "Rate" "button"
    And I set the field "Rating" to "Bad"
    And I click on "Rate" "button" in the ".competency-grader" "css_element"
    And I click on "Close" "button" in the "User competency summary" "dialogue"
    And I log out
    And I am on the "Course 2" course page logged in as "learner1"
    Then I should see "Not available unless: You have achieved the competency COMP1" in the "Page 2" "core_availability > Activity availability"
    And "Page 2" "link" should not exist in the "region-main" "region"
