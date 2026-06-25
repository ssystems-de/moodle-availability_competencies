@availability @availability_competencies @availability_competencies_display
Feature: Show the reason for a competency restriction
  In order to understand why an item is locked
  As a learner
  I need to see which competency I am expected to achieve

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
      | activity | course | section | name   | idnumber |
      | page     | C1     | 1       | Page 1 | PAGE1    |
      | page     | C1     | 2       | Page 2 | PAGE2    |

  Scenario: The competency is named on the course page and on the activity page
    Given the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |
    # The teacher keeps access to the activity and sees which restriction is in place.
    When I am on the "Course 1" course page logged in as "teacher1"
    Then I should see "Not available unless: You have achieved the competency COMP1" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should exist in the "region-main" "region"
    And I log out
    And I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Not available unless: You have achieved the competency COMP1" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should not exist in the "region-main" "region"
    # Calling the activity directly leads to the restricted activity page with the same reason.
    And I am on the "Page 1" "page activity" page
    And I should see "You have achieved the competency COMP1" in the "region-main" "region"

  Scenario: A negated restriction is described as a competency which must not be achieved
    Given the following "core_competency > user_competency" exist:
      | competency | user     | grade |
      | COMP1      | learner1 | Great |
    And the following "availability_competencies > activity restrictions" exist:
      | activity | competency | negated |
      | PAGE1    | COMP1      | 1       |
    When I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Not available unless: You have not achieved the competency COMP1" in the "Page 1" "core_availability > Activity availability"
    And "Page 1" "link" should not exist in the "region-main" "region"

  Scenario: A multilang competency name is displayed in the current language
    Given the "multilang" filter is "on"
    And the "multilang" filter applies to "content and headings"
    And the following "core_competency > competencies" exist:
      | shortname                                                                                        | idnumber | competencyframework |
      | <span lang="en" class="multilang">English</span><span lang="de" class="multilang">Deutsch</span> | COMP2    | FW1                 |
    And the following "core_competency > course_competencies" exist:
      | course | competency |
      | C1     | COMP2      |
    And the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP2      |
    When I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Not available unless: You have achieved the competency English" in the "Page 1" "core_availability > Activity availability"
    And I should not see "Deutsch" in the "region-main" "region"

  Scenario: A restricted section stays visible and names the competency
    Given the following "availability_competencies > section restrictions" exist:
      | course | section | competency |
      | C1     | 1       | COMP1      |
    When I am on the "Course 1" course page logged in as "learner1"
    Then I should see "Not available unless: You have achieved the competency COMP1" in the "section-1" "core_availability > Section availability"
    And I should not see "Page 1" in the "region-main" "region"
    And I should see "Page 2" in the "region-main" "region"

  @javascript
  Scenario: The restriction is lifted as soon as the learner is rated proficient
    Given the following "availability_competencies > activity restrictions" exist:
      | activity | competency |
      | PAGE1    | COMP1      |
    # Viewing the course fills the proficiency cache of the learner while they hold nothing yet.
    And I am on the "Course 1" course page logged in as "learner1"
    And I should see "Not available unless: You have achieved the competency COMP1" in the "Page 1" "core_availability > Activity availability"
    And I log out
    # Rating has to reach the learner on their very next page view, without any cache purge.
    When I am on the "Course 1" course page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Competency breakdown" "link"
    And I click on "Not rated" "link"
    And I click on "Rate" "button"
    And I set the field "Rating" to "Great"
    And I click on "Rate" "button" in the ".competency-grader" "css_element"
    And I click on "Close" "button" in the "User competency summary" "dialogue"
    And I log out
    And I am on the "Course 1" course page logged in as "learner1"
    Then "Page 1" "link" should exist in the "region-main" "region"
    And I should not see "You have achieved the competency COMP1" in the "region-main" "region"
