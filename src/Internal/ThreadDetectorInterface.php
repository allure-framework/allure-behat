<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

interface ThreadDetectorInterface
{

    public function getHost(): ?string;

    public function getThread(): ?string;
}
