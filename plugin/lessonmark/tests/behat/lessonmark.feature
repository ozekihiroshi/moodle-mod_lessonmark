@mod @mod_lessonmark
Feature: Author and publish a LessonMark teaching resource
  In order to publish reusable Markdown teaching material
  As a teacher
  I need to preview and save the same accessible document students will read

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following "activities" exist:
      | activity   | name         | intro              | course | idnumber | markdownsource   |
      | lessonmark | Lesson one   | Teaching resource  | C1     | LM1      | # Original lesson |

  @javascript @accessibility
  Scenario: Preview and publish an accessible teaching document
    Given I am on the "Lesson one" "lessonmark activity editing" page logged in as admin
    Then the "wrap" attribute of "Markdown source" "field" should contain "soft"
    When I set the LessonMark Markdown source to:
      """
      # Updated lesson

      ## Example

      > [!NOTE]
      > Check this explanation.

      ```python
      print("Hello")
      ```

      | Item | Value |
      | --- | --- |
      | Answer | 42 |

      Inline formula: `math:\frac{a}{b}`

      ```asciimath
      sum_(i=1)^n i = (n(n+1))/2
      ```

      ```mermaid
      flowchart LR
          Draft --> Review --> Publish
      ```
      """
    And I press "Refresh preview"
    And I wait until "Preview updated." "text" exists
    Then I should see "Updated lesson" in the "[data-region=\"preview-content\"]" "css_element"
    And ".ozmd-math .katex" "css_element" should exist in the "[data-region=\"preview-content\"]" "css_element"
    And a rendered LessonMark Mermaid diagram should appear
    And ".ozmd-math-copy" "css_element" should exist
    And the LessonMark source editor should stay aligned with its preview
    And the LessonMark source editor should remain visible while the preview scrolls
    And the page should meet accessibility standards
    When I press "Save and display"
    Then I should see "Updated lesson"
    And I should see "Contents"
    And I should see "Note"
    And "[role=\"main\"] > h2" "css_element" should not exist
    And ".ozmd-math .katex" "css_element" should exist
    And a rendered LessonMark Mermaid diagram should appear
    And ".ozmd-math-copy" "css_element" should exist
    And the page should meet accessibility standards

  @javascript
  Scenario: Invalid formulas and diagrams retain their source
    Given I am on the "Lesson one" "lessonmark activity editing" page logged in as admin
    When I set the LessonMark Markdown source to:
      """
      # Invalid browser source

      ```math
      \\frac{
      ```

      ```mermaid
      not a diagram
      ```
      """
    And I press "Refresh preview"
    And I wait until "Preview updated." "text" exists
    And I wait until ".ozmd-math-error" "css_element" exists
    And I wait until ".ozmd-mermaid-error" "css_element" exists
    Then I should see "\\frac{" in the "[data-region=\"preview-content\"]" "css_element"
    And I should see "not a diagram" in the "[data-region=\"preview-content\"]" "css_element"
    When I press "Save and display"
    Then I should see "\\frac{"
    And I should see "not a diagram"

  @javascript
  Scenario: Present the same saved document as separate slides
    Given I am on the "Lesson one" "lessonmark activity editing" page logged in as admin
    When I set the LessonMark Markdown source to:
      """
      # First slide

      Introductory explanation.

      <!-- slide -->

      # Second slide

      Inline formula: `math:\frac{a}{b}`
      """
    And I press "Save and display"
    And I follow "Present this lesson"
    Then I should see "1 / 2"
    And I should see "First slide"
    And I should not see "Second slide"
    When I press "Next"
    Then I should see "2 / 2"
    And I should see "Second slide"
    And I should not see "First slide"
    And ".ozmd-math .katex" "css_element" should exist
    When I follow "Return to lesson"
    Then I should see "First slide"
    And I should see "Second slide"

  @javascript
  Scenario: Continue across lessons and return to the previous lesson's last slide
    Given the following "activities" exist:
      | activity   | name       | course | idnumber | markdownsource |
      | lessonmark | Lesson two | C1     | LM2      | # Final lesson |
    And I am on the "Lesson one" "lessonmark activity editing" page logged in as admin
    When I set the LessonMark Markdown source to:
      """
      # First slide

      <!-- slide -->

      # Second slide

      Inline formula: `math:\frac{a}{b}`

      ```mermaid
      flowchart LR
          Draft --> Publish
      ```
      """
    And I press "Save and display"
    And I follow "Present course lessons"
    And I wait until "Lesson 1 / 2 · Slide 1 / 2" "text" exists
    Then I should see "Lesson 1 / 2 · Slide 1 / 2"
    When I press "Next"
    And I wait until "Lesson 1 / 2 · Slide 2 / 2" "text" exists
    Then I should see "Lesson 1 / 2 · Slide 2 / 2"
    When I switch to "lessonmark-course-frame" iframe
    Then I should see "Second slide"
    And ".ozmd-math .katex" "css_element" should exist
    And a rendered LessonMark Mermaid diagram should appear
    When I switch to the main frame
    When I press "Next"
    And I wait until "Lesson 2 / 2 · Slide 1 / 1" "text" exists
    Then I should see "Lesson 2 / 2 · Slide 1 / 1"
    When I switch to "lessonmark-course-frame" iframe
    Then I should see "Final lesson"
    When I switch to the main frame
    When I press "Previous"
    And I wait until "Lesson 1 / 2 · Slide 2 / 2" "text" exists
    Then I should see "Lesson 1 / 2 · Slide 2 / 2"
    When I switch to "lessonmark-course-frame" iframe
    Then I should see "Second slide"
    And a rendered LessonMark Mermaid diagram should appear
    When I switch to the main frame
    When I follow "Return to course"
    Then I should see "Course 1"
