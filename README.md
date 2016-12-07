# Allure Behat Adapter

This is a [Behat](http://behat.org/en/latest/) extension with Formatter that produces report data for [Yandex Allure](http://allure.qatools.ru/) test
reporting tool.

## Installation

To install using [Composer](https://getcomposer.org/) simply add `"allure-framework/allure-behat"` to `composer.json`:

    ...
    "require": {
    ...
        "allure-framework/allure-behat": "1.0.0",
    ...
    },
    ...

## Usage

To enable this extension in [Behat](http://behat.org/en/latest/), add it to `extensions` section of your ```behat.yml``` file:

    extensions:
        Allure\Behat\AllureFormatterExtension: ~

To use Allure formatter, add `allure` to your list of formatters in `name`:

```yml

    formatter:
        name: pretty,allure
        parameters:
            output: build/allure-report
            delete_previous_results: false
            ignored_tags: javascript
            severity_tag_prefix: 'severity_'
            issue_tag_prefix: 'bug_'

```
Here:
 - `output` - defines the output dir for report XML data
 - `delete_previous_results` - defines whether to remove all files in `output` folder before test run
 - `ignored_tags` - either a comma separated string or valid yaml array of Scenario tags to be ignored in reports
 - `severity_tag_prefix` - tag with this prefix will be interpreted (if possible) to define the Scenario severity level
 in reports (by default it's `normal`).
 - `issue_tag_prefix` - tag with this prefix will be interpreted as Issue marker and will generate issue tracking system
 link for test case (using [**allure.issues.tracker.pattern** setting for allure-cli](https://github.com/allure-framework/allure-core/wiki/Issues))
 - `test_id_tag_prefix` - tag with this prefix will be interpreted as Test Case Id marker and will generate TMS link for
 test case (using [**allure.tests.management.pattern** setting for allure-cli](https://github.com/allure-framework/allure-core/wiki/Test-Case-ID))

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
* Behat Scenario (and every single Example in Scenario Outline, too) -> Allure Test Case
* Behat Sentence -> Allure Test Step

Behat Scenarios are annotated with it's feature title and description to be grouped into Allure Feature.

Behat also has tags and they are also can be used in Allure reports:

* If a tag appears in ignored_tags configuration parameter, then it will be ignored and will not appear on Allure report
* If a tag starts with severity_tag_prefix, then formatter will try to interpret it's affixed part as one of the possible
[Allure Severity Levels](https://github.com/allure-framework/allure-php-adapter-api/blob/master/src/Yandex/Allure/Adapter/Model/SeverityLevel.php)
* In all other cases tag will be parsed as Allure Story annotation

By default, this formatter will use `build/allure-results` folder to put it's XML output to. Each Behat run will empty
that folder. To override this behavior, define `output` and `delete_previous_results` parameters respectively.
