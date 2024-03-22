<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

final class SuiteInfo
{

    public function __construct(
        private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
