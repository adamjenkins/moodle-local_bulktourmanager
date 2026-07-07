@tool @local_bulktourmanager @javascript
Feature: Bulk manage user tours
  In order to manage many user tours efficiently
  As an administrator
  I need to bulk import, export, and delete tours from the existing User tours page

  Background:
    Given I log in as "admin"
    And I add a new user tour with:
      | Name               | Tour Alpha |
      | Description        | First tour |
      | Apply to URL match | /my/%      |
      | Tour is enabled    | 1          |
    And I add a new user tour with:
      | Name               | Tour Beta          |
      | Description        | Second tour        |
      | Apply to URL match  | /course/view.php% |
      | Tour is enabled    | 1                  |
    And I navigate to "Appearance > User tours" in site administration

  Scenario: Delete selected tours in a single operation
    When I click on "input[data-bulktourmanager-checkbox]" "css_element" in the "Tour Alpha" "table_row"
    And I click on "input[data-bulktourmanager-checkbox]" "css_element" in the "Tour Beta" "table_row"
    And I click on "Delete selected" "button"
    And I click on "Yes" "button" in the "Delete selected tours" "dialogue"
    Then I should not see "Tour Alpha"
    And I should not see "Tour Beta"

  Scenario: Export and delete buttons stay disabled until a tour is selected
    Then the "Export selected" "button" should be disabled
    And the "Delete selected" "button" should be disabled
    When I click on "input[data-bulktourmanager-checkbox]" "css_element" in the "Tour Alpha" "table_row"
    Then the "Export selected" "button" should be enabled
    And the "Delete selected" "button" should be enabled

  Scenario: Bulk import a zip of tour export files
    When I click on "Bulk import (zip)" "link"
    And I upload "local/bulktourmanager/tests/fixtures/sample_tours.zip" file to "Zip file of tour exports" filemanager
    And I click on "Save changes" "button"
    Then I should see "Imported"
    And I should see "Imported tour A"
    And I should see "Imported tour B"
