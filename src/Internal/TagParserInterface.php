<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

interface TagParserInterface
{

    public function getIssueTag(string $tag): ?string;

    public function getTmsTag(string $tag): ?string;

    public function getSeverityTag(string $tag): ?string;

    public function isIgnoredTag(string $tag): bool;
}
