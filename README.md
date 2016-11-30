##What it is

This is a Behat extension with Formatter that produces report data for [Yandex Allure](http://allure.qatools.ru/) test
reporting tool.

### Installation

To install using composer, add `"allure-framework/allure-behat"` to `composer.json`:

    ...
    "require": {
    ...
        "allure-framework/allure-behat": "@dev",
    ...
    },
    "repositories": [
    ...
        {
            "type": "vcs",
            "url": "git@github.com:eduard-sukharev/behat-allure-adapter.git"
        }
    ...
    ]
    ...

### 1.0 Milestones:

 - [x] Fix `Without Behavior` and `Without story` report issue
 - [x] Add proper extension parametrization
 - [x] Test with background
 - [x] Test with scenario outlines
 - [ ] Test on existing project

### Feedback

This formatter is in it's early days and needs your input. Open issues on Github and propose your changes. Pull requrests
are welcome.
