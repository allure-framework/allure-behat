<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;

interface TestLifecycleInterface
{

    public function switchToSuite(SuiteInfo $suiteInfo): self;

    public function resetSuite(): self;

    public function switchToScenario(FeatureNode $feature, ScenarioInterface $scenario): self;

    public function create(): self;

    public function updateTest(): self;

    public function startTest(): self;

    public function updateTestResult(): self;

    public function startStep(StepNode $step): self;
}
