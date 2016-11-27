<?php
/**
 * Copyright (c) Eduard Sukharev
 * Apache 2.0 License. See LICENSE.md for full license text.
 */

namespace Allure\Behat\Adapter;

use Behat\Behat\Event\FeatureEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Behat\Event\SuiteEvent;
use Behat\Behat\Formatter\FormatterInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\Translator;
use Throwable;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\Annotation\AnnotationProvider;
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
use Yandex\Allure\Adapter\Model\Description;
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

    /**
     * @param string $outputDirectory XML files output directory
     * @param bool $deletePreviousResults Whether to delete previous results on return
     * @param array $ignoredAnnotations Extra annotaions to ignore in addition to standard PHPUnit annotations
     */
    public function __construct() {
        $defaultLanguage = null;
        if (($locale = getenv('LANG')) && preg_match('/^([a-z]{2})/', $locale, $matches)) {
            $defaultLanguage = $matches[1];
        }

        $this->parameters = new ParameterBag(array(
            'language'              => $defaultLanguage,
            'output'                => 'build' . DIRECTORY_SEPARATOR . 'allure-results',
            'ignored_annotations'   => array(),
            'delete_previous_results'   => true,
        ));

        AnnotationProvider::addIgnoredAnnotations($this->parameters->get('ignored_annotations'));
    }

    /**
     * @param $outputDirectory
     */
    private function prepareOutputDirectory($outputDirectory)
    {
        if (!file_exists($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        $files = glob($outputDirectory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_BRACE);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_null(Provider::getOutputDirectory())) {
            Provider::setOutputDirectory($outputDirectory);
        }
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
     * @param string $name
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
            'beforeFeature',
            'afterFeature',
            'beforeScenario',
            'afterScenario',
//            'beforeBackground', 'afterBackground', 'beforeOutline', 'afterOutline',
//            'beforeOutlineExample', 'afterOutlineExample',
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
        $this->prepareOutputDirectory($this->parameters->get('output'));
    }

    /**
     * @param FeatureEvent $featureEvent
     */
    public function beforeFeature(FeatureEvent $featureEvent)
    {
        $feature = $featureEvent->getFeature();
        $suiteName = $feature->getFile();
        $event = new TestSuiteStartedEvent($suiteName);
        $description = new Description(DescriptionType::TEXT, $feature->getDescription());
        $event->setDescription($description);
        $event->setTitle($feature->getTitle());

        $this->uuid = $event->getUuid();

        Allure::lifecycle()->fire($event);
    }

    /**
     * @param FeatureEvent $featureEvent
     */
    public function afterFeature(FeatureEvent $featureEvent)
    {
        Allure::lifecycle()->fire(new TestSuiteFinishedEvent($this->uuid));
    }

    /**
     * @param ScenarioEvent $scenarioEvent
     */
    public function beforeScenario(ScenarioEvent $scenarioEvent)
    {
        $scenario = $scenarioEvent->getScenario();
        $testTitle = $scenario->getTitle();
        $scenarioName = sprintf('%s: %s', $scenario->getKeyword(), $testTitle);
        $event = new TestCaseStartedEvent($this->uuid, $scenarioName);
        $event->setTitle($testTitle);

        Allure::lifecycle()->fire($event);
    }

    /**
     * @param ScenarioEvent $scenarioEvent
     */
    public function afterScenario(ScenarioEvent $scenarioEvent)
    {
        switch ($scenarioEvent->getResult()) {
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
            default:
                $this->exception = null;
        }

        $this->addTestCaseFinished();
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
            case StepEvent::SKIPPED:
                $this->addCanceledStep();
                break;
            case StepEvent::UNDEFINED:
                $this->exception = $stepEvent->getException();
                // break omitted intentionally
            case StepEvent::PENDING:
                $this->addCanceledStep();
                break;
            case StepEvent::FAILED:
                $this->exception = $stepEvent->getException();
                $this->addFailedStep();
                break;
            case StepEvent::PASSED:
            default:
                $this->addFinishedStep();
        }
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