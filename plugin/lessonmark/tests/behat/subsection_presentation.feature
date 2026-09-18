@mod @mod_lessonmark @javascript
Feature: Course presentations follow subsection display order
  In order to study lessons in the intended sequence
  As a course participant
  I need presentation controls to follow subsection placement

  Scenario Outline: Move forward and backward through subsection lessons
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | participant | Test | Participant | participant@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections | initsections |
      | Nested lessons | NESTED | topics | 1 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | participant | NESTED | <role> |
    And the following "activities" exist:
      | activity | name | course | section | idnumber | markdownsource |
      | lessonmark | Introduction | NESTED | 1 | intro | # Introductory slide |
      | subsection | Lessons | NESTED | 1 | unit | |
      | lessonmark | Lesson 1.1 | NESTED | 2 | first | # First lesson slide |
      | lessonmark | Lesson 1.2 | NESTED | 2 | second | # Second lesson slide |
      | lessonmark | Later lesson | NESTED | 1 | later | # Later lesson slide |
    When I am on the "Lesson 1.1" "lessonmark activity" page logged in as "participant"
    And I follow "Present course lessons"
    And I wait until "Lesson 2 / 4 · Slide 1 / 1" "text" exists
    When I press "Next"
    And I wait until "Lesson 3 / 4 · Slide 1 / 1" "text" exists
    And I switch to "lessonmark-course-frame" iframe
    Then I should see "Second lesson slide"
    When I switch to the main frame
    And I press "Next"
    And I wait until "Lesson 4 / 4 · Slide 1 / 1" "text" exists
    And I switch to "lessonmark-course-frame" iframe
    Then I should see "Later lesson slide"
    When I switch to the main frame
    And I press "Previous"
    And I wait until "Lesson 3 / 4 · Slide 1 / 1" "text" exists
    And I press "Previous"
    And I wait until "Lesson 2 / 4 · Slide 1 / 1" "text" exists
    And I switch to "lessonmark-course-frame" iframe
    Then I should see "First lesson slide"
    When I switch to the main frame
    And I press "Previous"
    And I wait until "Lesson 1 / 4 · Slide 1 / 1" "text" exists
    And I switch to "lessonmark-course-frame" iframe
    Then I should see "Introductory slide"

    Examples:
      | role |
      | student |
      | editingteacher |
