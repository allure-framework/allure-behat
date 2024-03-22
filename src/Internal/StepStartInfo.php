<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Behat\Gherkin\Node\StepNode;

final class StepStartInfo
{

    public function __construct(
        private StepNode $originalStep,
        private string $uuid,
    ) {
    }

    public function getOriginalStep(): StepNode
    {
        return $this->originalStep;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }
}