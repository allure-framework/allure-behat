# Allure Behat Adapter

This is a [Behat](http://behat.org/en/latest/) extension with Formatter that produces report data for [Yandex Allure](http://allure.qatools.ru/) test
reporting tool with eZ Systems modification introducing requirement of eZ Systems `allure-php-commons`.

## Installation

To install using [Composer](https://getcomposer.org/) simply add `"ezsystems/allure-behat"` to `composer.json`:

    ...
    "require": {
    ...
        "ezsystems/allure-behat": "~3.0.0",
    ...
    },
    ...

## Usage

To enable this extension in [Behat](http://behat.org/en/latest/), add it to `extensions` section of your ```behat.yml``` file
To use Allure formatter, add `allure` to your list of formatters in `name`

```yml
  formatters:
    pretty: true
    allure:
      output_path: %paths.base%/build/allure
  extensions:
    Allure\Behat\AllureFormatterExtension:
      severity_key: "severity:"
      ignored_tags: "tag_ignore"
      issue_tag_prefix: "JIRA:"
      test_id_tag_prefix: "BUG:"
```

Here:
 - `output_path` - defines the output dir for report XML data. Default is `./allure-results`
 - `ignored_tags` - either a comma separated string or valid yaml array of Scenario tags to be ignored in reports
 - `severity_key` - tag with this prefix will be interpreted (if possible) to define the Scenario severity level
 in reports (by default it's `normal`).
 - `issue_tag_prefix` - tag with this prefix will be interpreted as Issue marker and will generate issue tracking system
 link for test case (using [**allure.issues.tracker.pattern** setting for allure-cli](https://github.com/allure-framework/allure-core/wiki/Issues))
 - `test_id_tag_prefix` - tag with this prefix will be interpreted as Test Case Id marker and will generate TMS link for
 test case (using [**allure.tests.management.pattern** setting for allure-cli](https://github.com/allure-framework/allure-core/wiki/Test-Case-ID))


### Use attachment support
To have attachments in allure report - make sure your behat runs tests with [Mink](https://github.com/minkphp/Mink)

Allure can handle exception thrown in your Context if that exception is instance of `ArtifactExceptionInterface`
and get screenshots path from it.


### How does it work?

Behat has the following test structure:
```
It has Features described in separate feature files
        Each Feature contains Scenarios
            Each scenario contains Steps
```

Allure has a bit different hierarchy:

```
    Each report contains Test Suites
        A Test Suite contains Test Cases
            Every Test Case can contain one or more Steps
```
On the other hand, Allure also supports grouping Test Cases by Feature, by Story or by Severity level.

Behat Allure formatter does the following mapping:

* Behat Test Run -> Allure Test Suite
* Gherkin Scenario (and every single Example in Scenario Outline, too) -> Allure Test Case
* Gherkin Step -> Allure Test Step

Behat Scenarios are annotated with it's feature title and description to be grouped into Allure Feature.

Behat also has tags and they are also can be used in Allure reports:

* If a tag appears in ignored_tags configuration parameter, then it will be ignored and will not appear on Allure report
* If a tag starts with severity_tag_prefix, then formatter will try to interpret it's affixed part as one of the possible
[Allure Severity Levels](https://github.com/allure-framework/allure-php-adapter-api/blob/master/src/Yandex/Allure/Adapter/Model/SeverityLevel.php)
* If a tag starts with test_id_tag_prefix, then formatter will interpret it's affixed part as
[Test Case Id](https://github.com/allure-framework/allure-core/wiki/Test-Case-ID) for your TMS
* In all other cases tag will be parsed as Allure Story annotation

### Contribution?
Feel free to open PR with changes but before pls make sure you pass tests
`./vendor/behat/behat/bin/behat`
