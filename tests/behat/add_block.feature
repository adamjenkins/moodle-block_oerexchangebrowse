@block @block_oerexchangebrowse @javascript
Feature: Add the OER Exchange browse block to the Dashboard
  In order to browse the OER Exchange catalogue from my Dashboard
  As a user
  I need to be able to add the block and see it render real content

  Scenario: An admin adds the block to the site homepage and it renders correctly
    Given I log in as "admin"
    And I am on site homepage
    And I turn editing mode on
    When I add the "OER Exchange: browse" block
    Then I should see "No published resources yet." in the "OER Exchange: browse" "block"

  Scenario: An admin adds the block to the Dashboard, its primary home
    Given I log in as "admin"
    And I visit "/my/"
    And I turn editing mode on
    When I add the "OER Exchange: browse" block
    Then I should see "No published resources yet." in the "OER Exchange: browse" "block"
