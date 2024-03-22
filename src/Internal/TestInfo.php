<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;

final class TestInfo
{

    public function __construct(
        private FeatureNode $originalFeature,
        private ScenarioInterface $originalScenario,
        private string $signature,
        private ?string $feature = null,
        private ?string $scenario = null,
        private ?string $dataLabel = null,
        private ?string $host = null,
        private ?string $thread = null,
    ) {
    }

    public function getOriginalFeature(): FeatureNode
    {
        return $this->originalFeature;
    }

    public function getOriginalScenario(): ScenarioInterface
    {
        return $this->originalScenario;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getFeature(): ?string
    {
        return $this->feature;
    }

    public function getScenario(): ?string
    {
        return $this->scenario;
    }

    public function getDataLabel(): ?string
    {
        return $this->dataLabel;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getThread(): ?string
    {
        return $this->thread;
    }
}
