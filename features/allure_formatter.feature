Feature: Allure Formatter
  In order integrate with Allure test report tool
  As a developer
  I need to be able to generate a allure-compatible report

  Scenario: Scenario annotation
    Given a file named "behat.yml" with:
      """
      default:
        formatters:
          pretty: false
          allure: true
        extensions:
          Allure\Behat\AllureFormatterExtension:
            severity_key: "severity:"
            ignored_tags: "tag_ignore"
            issue_tag_prefix: "JIRA:"
            test_id_tag_prefix: "BUG:"
      """
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php
      use Behat\Behat\Context\Context,
          Behat\Behat\Tester\Exception\PendingException;
      class FeatureContext implements Context
      {
          /**
           * @Given /^scenario has annotation$/
           */
          public function scenarioHasAnnotation() {
              return;
          }
          /**
           * @When /^it passed$/
           */
          public function iAdd() {
              return;
          }
          /**
           * @Then /^annotation is collected$/
           */
          public function somethingNotDoneYet() {
              return;
          }
      }
      """
    And a file named "features/World.feature" with:
      """
      @tag_feature @severity:blocker @JIRA:PROD-4444
      Feature: Annotation
        In order to have meta information of the scenario
        As a features developer
        I want, allure to collect all feature & scenarios tags

        @tag_scenario @BUG:7654 @tag_ignore
        Scenario: Scenario annotation
          Given scenario has annotation
          When it passed
          Then annotation is collected
      """
    When I run "behat --no-colors -f allure -o allure-results"
    Then "allure-results/*testsuite.xml" file xml should be like:
      """
      <?xml version="1.0" encoding="UTF-8"?>
      <alr:test-suite xmlns:alr="urn:model.allure.qatools.yandex.ru" start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" version="1.4.0">
        <name>default</name>
        <test-cases>
          <test-case start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
            <name>Annotation | Scenario annotation</name>
            <title><![CDATA[Scenario annotation]]></title>
            <description type="text"><![CDATA[In order to have meta information of the scenario
      As a features developer
      I want, allure to collect all feature & scenarios tags]]></description>
            <steps>
              <step start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
                <name><![CDATA[scenario has annotation]]></name>
                <title><![CDATA[Given scenario has annotation]]></title>
              </step>
              <step start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
                <name><![CDATA[it passed]]></name>
                <title><![CDATA[When it passed]]></title>
              </step>
              <step start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
                <name><![CDATA[annotation is collected]]></name>
                <title><![CDATA[Then annotation is collected]]></title>
              </step>
            </steps>
            <labels>
              <label name="severity" value="blocker"/>
              <label name="story" value="tag_feature"/>
              <label name="story" value="tag_scenario"/>
              <label name="issue" value="PROD-4444"/>
              <label name="testId" value="7654"/>
            </labels>
          </test-case>
        </test-cases>
      </alr:test-suite>
      """

  Scenario: Each outline example has its own result
    Given a file named "behat.yml" with:
      """
      default:
        extensions:
          Allure\Behat\AllureFormatterExtension: ~
      """
    And a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php
      use Behat\Behat\Context\Context;

      class FeatureContext implements Context
      {
          /** @Given the value is :value */
          public function checkValue($value) {
              if ($value === 'bad') {
                  throw new RuntimeException('Invalid value: ' . $value);
              }
          }
      }
      """
    And a file named "features/Outline.feature" with:
      """
      Feature: Outline results
        Scenario Outline: Validate a value
          Given the value is <value>

          Examples:
            | value |
            | bad   |
            | good  |
      """
    When I run "behat --no-colors -f allure -o allure-results"
    Then it should fail
    And "allure-results/*testsuite.xml" file xml should satisfy:
      """
      count(/alr:test-suite/test-cases/test-case) = 2
      count(//test-case[@status='failed' and title='Validate a value' and parameters/parameter[@name='value' and @value='bad'] and count(steps/step)=1 and steps/step/name='the value is bad' and failure/message='Invalid value: bad']) = 1
      count(//test-case[@status='passed' and title='Validate a value' and parameters/parameter[@name='value' and @value='good'] and count(steps/step)=1 and steps/step/name='the value is good' and not(failure)]) = 1
      count(//test-case[contains(name, 'Outline.feature:7')]) = 1
      count(//test-case[contains(name, 'Outline.feature:8')]) = 1
      """
