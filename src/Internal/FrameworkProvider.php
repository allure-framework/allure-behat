<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Qameta\Allure\Model\Label;
use Qameta\Allure\Model\ModelProviderInterface;
use Qameta\Allure\Model\ModelProviderTrait;

final class FrameworkProvider implements ModelProviderInterface
{
    use ModelProviderTrait;

    /**
     * @return list<FrameworkProvider>
     */
    public static function createForChain(): array
    {
        return [new self()];
    }

    /**
     * @return list<Label>
     */
    public function getLabels(): array
    {
        return [
            Label::language(null),
            Label::framework('behat'),
        ];
    }
}
