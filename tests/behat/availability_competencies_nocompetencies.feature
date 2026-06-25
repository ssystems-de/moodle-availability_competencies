@availability @availability_competencies @availability_competencies_nocompetencies @javascript
Feature: Offer the competency restriction only when there is a competency to pick
  In order not to be sent to a picker which cannot offer anything
  As a teacher
  I need the restriction to stay out of the dialogue while the site holds no usable competency

  # The course-level counterpart of this, a site which does have competencies but a course which
  # none of them are linked to, lives in availability_competencies_permissions.feature.

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
    And the following "activities" exist:
      | activity | course | section | name   |
      | page     | C1     | 1       | Page 1 |

  Scenario: The restriction is not offered on a site without any competency framework
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    Then I should not see "Competency" in the "Add restriction..." "dialogue"
    # Other restrictions are still on offer, so the dialogue itself is fine.
    And I should see "Date" in the "Add restriction..." "dialogue"

  Scenario: The restriction is not offered while the frameworks of the site are still empty
    Given the following "scales" exist:
      | name       | scale            |
      | Test Scale | Bad, Good, Great |
    And the following "core_competency > frameworks" exist:
      | shortname | idnumber | scale      |
      | Framework | FW1      | Test Scale |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I click on "Add restriction..." "button"
    Then I should not see "Competency" in the "Add restriction..." "dialogue"
