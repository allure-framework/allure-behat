<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Qameta\Allure\AllureLifecycleInterface;
use Qameta\Allure\Model\ModelProviderChain;
use Qameta\Allure\Model\ResultFactoryInterface;
use Qameta\Allure\Model\TestResult;
use Qameta\Allure\Setup\LinkTemplateCollectionInterface;
use WeakMap;

final class TestLifecycle implements TestLifecycleInterface
{

    private ?SuiteInfo $currentSuite = null;

    private ?TestInfo $currentTest = null;

    private ?TestStartInfo $currentTestStart = null;

    /**
     * @var WeakMap<StepNode, StepStartInfo>
     */
    private WeakMap $stepStarts;

    public function __construct(
        private AllureLifecycleInterface $lifecycle,
        private ResultFactoryInterface $resultFactory,
        private LinkTemplateCollectionInterface $linkTemplates,
        private ThreadDetectorInterface $threadDetector,
        private TagParserInterface $tagConfig,
    ) {
        $this->stepStarts = new WeakMap();
    }

    private function getCurrentSuite(): SuiteInfo
    {
        return $this->currentSuite ?? throw new \RuntimeException("Current suite not found");
    }

    private function getCurrentTest(): TestInfo
    {
        return $this->currentTest ?? throw new \RuntimeException("Current test not found");
    }

    private function getCurrentTestStart(): TestStartInfo
    {
        return $this->currentTestStart ?? throw new \RuntimeException("Current test start not found");
    }

    public function switchToSuite(SuiteInfo $suiteInfo): self
    {
        $this->currentSuite = $suiteInfo;

        return $this;
    }

    public function resetSuite(): self
    {
        $this->currentSuite = null;

        return $this;
    }

    public function switchToScenario(FeatureNode $feature, ScenarioInterface $scenario): self
    {
        $thread = $this->threadDetector->getThread();
        $this->lifecycle->switchThread($thread);

        $this->currentTest = new TestInfo(
            $feature,
            $scenario,
            signature: "{$feature->getTitle()}:{$scenario->getTitle()}",
            feature: $feature->getTitle(),
            scenario: $scenario->getTitle(),
            dataLabel: null,
            host: $this->threadDetector->getHost(),
            thread: $thread,
        );

        return $this;
    }

    public function create(): self
    {
        $containerResult = $this->resultFactory->createContainer();
        $this->lifecycle->startContainer($containerResult);

        $testResult = $this->resultFactory->createTest();
        $this->lifecycle->scheduleTest($testResult, $containerResult->getUuid());

        $this->currentTestStart = new TestStartInfo(
            containerUuid: $containerResult->getUuid(),
            testUuid: $testResult->getUuid(),
        );

        return $this;
    }

    public function updateTest(): self
    {
        $modelProvider = new ModelProviderChain(
            ...FrameworkProvider::createForChain(),
            ...SuiteProvider::createForChain($this->getCurrentSuite()),
            ...ScenarioProvider::createForChain(
                $this->getCurrentTest()->getOriginalFeature(),
                $this->getCurrentTest()->getOriginalScenario(),
                $this->tagConfig,
                $this->linkTemplates,
            ),
        );
        $this->lifecycle->updateTest(
            fn (TestResult $t) => $t
                ->setName($modelProvider->getDisplayName())
                ->setFullName($modelProvider->getFullName())
                ->setDescription($modelProvider->getDescription())
                ->setDescriptionHtml($modelProvider->getDescriptionHtml())
                ->setLabels(...$modelProvider->getLabels())
                ->setLinks(...$modelProvider->getLinks())
                ->setParameters(...$modelProvider->getParameters()),
            $this->getCurrentTestStart()->getTestUuid(),
        );

        return $this;
    }

    public function startTest(): self
    {
        $this->lifecycle->startTest(
            $this->getCurrentTestStart()->getTestUuid(),
        );

        return $this;
    }

    public function stopTest(): self
    {
        $this->lifecycle->stopTest(
            $this->getCurrentTestStart()->getTestUuid(),
        );
        $this->lifecycle->stopContainer(
            $this->getCurrentTestStart()->getContainerUuid(),
        );
        $this->lifecycle->writeTest(
            $this->getCurrentTestStart()->getTestUuid(),
        );
        $this->lifecycle->writeContainer(
            $this->getCurrentTestStart()->getContainerUuid(),
        );
        $this->currentTest = null;
        $this->currentTestStart = null;

        return $this;
    }

    public function updateTestResult(): self
    {
        return $this;
    }

    public function startStep(StepNode $step): self
    {
        $stepResult = $this->resultFactory->createStep();
        $this->lifecycle->startStep(
            $stepResult,
            $this->getCurrentTestStart()->getTestUuid(),
        );

        $stepStart = new StepStartInfo(
            originalStep: $step,
            uuid: $stepResult->getUuid(),
        );
        $this->stepStarts[$step] = $stepStart;

        return $this;
    }
}
