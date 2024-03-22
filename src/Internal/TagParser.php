<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

final class TagParser implements TagParserInterface
{

    private array $ignoredTags;

    /**
     * @param string|null  $issueTagPrefix
     * @param string|null  $tmsTagPrefix
     * @param string|null  $severityKey
     * @param list<string> $ignoredTags
     */
    public function __construct(
        private ?string $issueTagPrefix,
        private ?string $tmsTagPrefix,
        private ?string $severityKey,
        array $ignoredTags,
    ) {
        $this->ignoredTags = \array_map(
            fn (string $tag): string => \strtolower($tag),
            $ignoredTags,
        );
    }

    public function getIssueTag(string $tag): ?string
    {
        return $this->tagWithPrefix($tag, $this->issueTagPrefix);
    }

    public function getTmsTag(string $tag): ?string
    {
        return $this->tagWithPrefix($tag, $this->tmsTagPrefix);
    }

    public function getSeverityTag(string $tag): ?string
    {
        return $this->tagWithPrefix($tag, $this->severityKey);
    }

    public function isIgnoredTag(string $tag): bool
    {
        return \in_array(\strtolower($tag), $this->ignoredTags);
    }

    private function tagWithPrefix(string $prefixedTag, ?string $prefix): ?string
    {
        return isset($prefix) && \stripos($prefixedTag, $prefix) === 0
            ? \substr($prefixedTag, \strlen($prefix))
            : null;
    }
}
