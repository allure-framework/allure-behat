<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Qameta\Allure\Model\Label;
use Qameta\Allure\Model\ModelProviderInterface;
use Qameta\Allure\Model\ModelProviderTrait;

final class SuiteProvider implements ModelProviderInterface
{
    use ModelProviderTrait;

    public function __construct(
        private SuiteInfo $suiteInfo,
    ) {
    }

    public static function createForChain(SuiteInfo $suiteInfo): array
    {
        return [new self($suiteInfo)];
    }

    public function getLabels(): array
    {
        return [
            Label::parentSuite($this->suiteInfo->getName()),
        ];
    }
}
