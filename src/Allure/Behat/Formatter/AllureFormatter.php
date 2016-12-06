<?php
/**
 * Copyright (c) Eduard Sukharev
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * See LICENSE.md for full license text.
 */

namespace Allure\Behat\Formatter;

use Behat\Behat\Event\OutlineExampleEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Behat\Event\SuiteEvent;
use Behat\Behat\Formatter\FormatterInterface;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use DateTime;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\Translator;
use Throwable;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\AllureException;
use Yandex\Allure\Adapter\Annotation\AnnotationManager;
use Yandex\Allure\Adapter\Annotation\AnnotationProvider;
use Yandex\Allure\Adapter\Annotation\Description;
use Yandex\Allure\Adapter\Annotation\Features;
use Yandex\Allure\Adapter\Annotation\Issues;
use Yandex\Allure\Adapter\Annotation\Parameter;
use Yandex\Allure\Adapter\Annotation\Severity;
use Yandex\Allure\Adapter\Annotation\Stories;
use Yandex\Allure\Adapter\Annotation\TestCaseId;
use Yandex\Allure\Adapter\Event\StepCanceledEvent;
use Yandex\Allure\Adapter\Event\StepFailedEvent;
use Yandex\Allure\Adapter\Event\StepFinishedEvent;
use Yandex\Allure\Adapter\Event\StepStartedEvent;
use Yandex\Allure\Adapter\Event\TestCaseBrokenEvent;
use Yandex\Allure\Adapter\Event\TestCaseCanceledEvent;
use Yandex\Allure\Adapter\Event\TestCaseFailedEvent;
use Yandex\Allure\Adapter\Event\TestCaseFinishedEvent;
use Yandex\Allure\Adapter\Event\TestCaseStartedEvent;
use Yandex\Allure\Adapter\Event\TestSuiteFinishedEvent;
use Yandex\Allure\Adapter\Event\TestSuiteStartedEvent;
use Yandex\Allure\Adapter\Model\DescriptionType;
use Yandex\Allure\Adapter\Model\Provider;

/**
 * @author Eduard Sukharev <eduard.sukharev@opensoftdev.ru>
 */
class AllureFormatter implements FormatterInterface
{
    private $translator;

    private $parameters;

    private $uuid;

    /**
     * @var Exception|Throwable
     */
    private $exception;

    public function __construct()
    {
        $defaultLanguage = null;
        if (($locale = getenv('LANG')) && preg_match('/^([a-z]{2})/', $locale, $matches)) {
            $defaultLanguage = $matches[1];
        }

        $this->parameters = new ParameterBag(array(
            'language' => $defaultLanguage,
            'output' => 'build' . DIRECTORY_SEPARATOR . 'allure-results',
            'ignored_tags' => array(),
            'severity_tag_prefix' => 'severity_',
            'issue_tag_prefix' => 'bug_',
            'test_id_tag_prefix' => 'test_',
            'delete_previous_results' => true,
        ));
    }

    /**
     * Set formatter translator.
     *
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Checks if current formatter has parameter.
     *
     * @param string $name
     *
     * @return Boolean
     */
    public function hasParameter($name)
    {
        return $this->parameters->has($name);
    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * Returns parameter name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->parameters->get($name);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'beforeSuite',
            'afterSuite',
            'beforeScenario',
            'afterScenario',
            'beforeOutlineExample',
            'afterOutlineExample',
            'beforeStep',
            'afterStep',
        );

        return array_combine($events, $events);
    }

    /**
     * @param SuiteEvent $suiteEvent
     */
    public function beforeSuite(SuiteEvent $suiteEvent)
    {
        AnnotationProvider::addIgnoredAnnotations(array());

        $this->prepareOutputDirectory(
            $this->parameters->get('output'),
            $this->parameters->get('delete_previous_results')
        );
        $now = new DateTime();
        $event = new TestSuiteStartedEvent(sprintf('TestSuite-%s', $now->format('Y-m-d_His')));

        $this->uuid = $event->getUuid();

        Allure::lifecycle()->fire($event);
    }

    public function afterSuite(SuiteEvent $suiteEvent)
    {
        Allure::lifecycle()->fire(new TestSuiteFinishedEvent($this->uuid));
    }

    /**
     * @param ScenarioEvent $scenarioEvent
     */
    public function beforeScenario(ScenarioEvent $scenarioEvent)
    {
        $scenario = $scenarioEvent->getScenario();
        $annotations = array_merge(
            $this->parseFeatureAnnotations($scenarioEvent->getScenario()->getFeature()),
            $this->parseScenarioAnnotations($scenario)
        );
        $annotationManager = new AnnotationManager($annotations);

        $scenarioName = sprintf('%s:%d', $scenario->getFile(), $scenario->getLine());
        $event = new TestCaseStartedEvent($this->uuid, $scenarioName);
        $annotationManager->updateTestCaseEvent($event);

        Allure::lifecycle()->fire($event->withTitle($scenario->getTitle()));
    }

    public function beforeOutlineExample(OutlineExampleEvent $outlineExampleEvent)
    {
        $scenarioOutline = $outlineExampleEvent->getOutline();

        $scenarioName = sprintf(
            '%s:%d [%d]',
            $scenarioOutline->getFile(),
            $scenarioOutline->getLine(),
            $outlineExampleEvent->getIteration()
        );
        $event = new TestCaseStartedEvent($this->uuid, $scenarioName);

        $annotations = array_merge(
            $this->parseFeatureAnnotations($scenarioOutline->getFeature()),
            $this->parseScenarioAnnotations($scenarioOutline),
            $this->parseExampleAnnotations($scenarioOutline, $outlineExampleEvent->getIteration())
        );
        $annotationManager = new AnnotationManager($annotations);
        $annotationManager->updateTestCaseEvent($event);

        Allure::lifecycle()->fire($event->withTitle($scenarioOutline->getTitle()));
    }

    /**
     * @param ScenarioEvent $scenarioEvent
     */
    public function afterScenario(ScenarioEvent $scenarioEvent)
    {
        $this->processScenarioResult($scenarioEvent->getResult());
    }

    /**
     * @param OutlineExampleEvent $outlineExampleEvent
     */
    public function afterOutlineExample(OutlineExampleEvent $outlineExampleEvent)
    {
        $this->processScenarioResult($outlineExampleEvent->getResult());
    }

    /**
     * @param StepEvent $stepEvent
     */
    public function beforeStep(StepEvent $stepEvent)
    {
        $step = $stepEvent->getStep();
        $event = new StepStartedEvent($step->getText());
        $event->withTitle(sprintf('%s %s', $step->getType(), $step->getText()));

        Allure::lifecycle()->fire($event);
    }

    public function afterStep(StepEvent $stepEvent)
    {
        switch ($stepEvent->getResult()) {
            case StepEvent::FAILED:
                $this->exception = $stepEvent->getException();
                $this->addFailedStep();
                break;
            case StepEvent::UNDEFINED:
                $this->exception = $stepEvent->getException();
                $this->addFailedStep();
                break;
            case StepEvent::PENDING:
            case StepEvent::SKIPPED:
                $this->addCanceledStep();
                break;
            case StepEvent::PASSED:
            default:
                $this->exception = null;
        }

        $this->addFinishedStep();
    }

    /**
     * @param string $outputDirectory
     * @param boolean $deletePreviousResults
     */
    private function prepareOutputDirectory($outputDirectory, $deletePreviousResults)
    {
        if (!file_exists($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        if ($deletePreviousResults) {
            $files = glob($outputDirectory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_BRACE);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (is_null(Provider::getOutputDirectory())) {
            Provider::setOutputDirectory($outputDirectory);
        }
    }

    /**
     * @param integer $result
     */
    protected function processScenarioResult($result)
    {
        switch ($result) {
            case StepEvent::FAILED:
                $this->addTestCaseFailed();
                break;
            case StepEvent::UNDEFINED:
                $this->addTestCaseBroken();
                break;
            case StepEvent::PENDING:
            case StepEvent::SKIPPED:
                $this->addTestCaseCancelled();
                break;
            case StepEvent::PASSED:
            default:
                $this->exception = null;
        }

        $this->addTestCaseFinished();
    }

    /**
     * @param FeatureNode $featureNode
     *
     * @return array
     */
    private function parseFeatureAnnotations(FeatureNode $featureNode)
    {
        $feature = new Features();
        $feature->featureNames = array($featureNode->getTitle());

        $description = new Description();
        $description->type = DescriptionType::TEXT;
        $description->value = $featureNode->getDescription();

        return [
            $feature,
            $description,
        ];
    }

    /**
     * @param ScenarioNode $scenario
     *
     * @return array
     * @throws Exception
     */
    private function parseScenarioAnnotations(ScenarioNode $scenario)
    {
        $annotations = [];
        $story = new Stories();
        $story->stories = [];

        $issues = new Issues();
        $issues->issueKeys = [];

        $testId = new TestCaseId();
        $testId->testCaseIds = [];

        $ignoredTags = [];
        $ignoredTagsParameter = $this->getParameter('ignored_tags');
        if (is_string($ignoredTagsParameter)) {
            $ignoredTags = array_map('trim', explode(',', $ignoredTagsParameter));
        } elseif (is_array($ignoredTagsParameter)) {
            $ignoredTags = $ignoredTagsParameter;
        }
        foreach ($scenario->getTags() as $tag) {
            if (in_array($tag, $ignoredTags)) {
                continue;
            }
            if ($severityPrefix = $this->getParameter('severity_tag_prefix')) {
                if (stripos($tag, $severityPrefix) === 0) {
                    try {
                        $parsedSeverity = substr($tag, strlen($severityPrefix));

                        $severity = new Severity();
                        $severity->level = $parsedSeverity;

                        $annotations[] = $severity;

                        continue;
                    } catch (AllureException $e) {
                        // do nothing and parse it as if it were regular tag
                    }
                }
            }

            if ($issuePrefix = $this->getParameter('issue_tag_prefix')) {
                if (stripos($tag, $issuePrefix) === 0) {
                    $issues->issueKeys[] = substr($tag, strlen($issuePrefix));

                    continue;
                }
            }

            if ($testIdPrefix = $this->getParameter('test_id_tag_prefix')) {
                if (stripos($tag, $testIdPrefix) === 0) {
                    $testId->testCaseIds[] = substr($tag, strlen($testIdPrefix));

                    continue;
                }
            }

            $story->stories[] = $tag;
        }

        if ($story->getStories()) {
            array_push($annotations, $story);
        }

        if ($issues->getIssueKeys()) {
            array_push($annotations, $issues);
        }

        if ($testId->getTestCaseIds()) {
            array_push($annotations, $testId);
        }

        return $annotations;
    }

    /**
     * @param OutlineNode $scenarioOutline
     * @param integer $iteration
     *
     * @return array
     */
    private function parseExampleAnnotations(OutlineNode $scenarioOutline, $iteration)
    {
        $parameters = [];
        $examplesRow = $scenarioOutline->getExamples()->getHash();
        foreach ($examplesRow[$iteration] as $name => $value) {
            $parameter = new Parameter();
            $parameter->name = $name;
            $parameter->value = $value;
            $parameters[] = $parameter;
        }

        return $parameters;
    }

    private function addCanceledStep()
    {
        $event = new StepCanceledEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addFinishedStep()
    {
        $event = new StepFinishedEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addFailedStep()
    {
        $event = new StepFailedEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseFinished()
    {
        $this->exception;

        $event = new TestCaseFinishedEvent();
        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseCancelled()
    {
        $event = new TestCaseCanceledEvent();

        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseBroken()
    {
        $event = new TestCaseBrokenEvent();
        $event->withException($this->exception)->withMessage($this->exception->getMessage());

        Allure::lifecycle()->fire($event);
    }

    private function addTestCaseFailed()
    {
        $event = new TestCaseFailedEvent();
        $event->withException($this->exception)->withMessage($this->exception->getMessage());

        Allure::lifecycle()->fire($event);
    }
}