<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use function gethostname;

final class ThreadDetector implements ThreadDetectorInterface
{

    private string|false|null $host = null;

    public function getHost(): ?string
    {
        if (!isset($this->host)) {
            $this->host = @gethostname();
        }

        return $this->host;
    }

    public function getThread(): ?string
    {
        return null;
    }
}
