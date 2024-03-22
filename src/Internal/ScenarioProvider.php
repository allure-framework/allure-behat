<?php

declare(strict_types=1);

namespace Qameta\Allure\Behat\Internal;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\KeywordNodeInterface;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Qameta\Allure\Model\Label;
use Qameta\Allure\Model\Link;
use Qameta\Allure\Model\LinkType;
use Qameta\Allure\Model\ModelProviderInterface;
use Qameta\Allure\Model\ModelProviderTrait;
use Qameta\Allure\Setup\LinkTemplateCollectionInterface;

final class ScenarioProvider implements ModelProviderInterface
{
    use ModelProviderTrait;

    private ?array $links = null;

    private ?array $labels = null;

    private array $ignoredTags;

    public function __construct(
        private FeatureNode $feature,
        private ScenarioInterface $scenario,
        private TagParserInterface $tagConfig,
        private LinkTemplateCollectionInterface $linkTemplates,
    ) {
    }

    public static function createForChain(
        FeatureNode $feature,
        ScenarioInterface $scenario,
        TagParserInterface $tagConfig,
        LinkTemplateCollectionInterface $linkTemplates,
    ): array {
        return [
            new self(
                $feature,
                $scenario,
                $tagConfig,
                $linkTemplates,
            ),
        ];
    }

    public function getLinks(): array
    {
        if (!isset($this->links)) {
            $this->parseTags();
        }

        return $this->links;
    }

    public function getLabels(): array
    {
        if (!isset($this->labels)) {
            $this->parseTags();
        }

        return $this->labels;
    }

    private function parseTags(): void
    {
        $this->links = [];
        $this->labels = [];
        foreach ($this->scenario->getTags() as $tag) {
            if ($this->tagConfig->isIgnoredTag($tag)) {
                continue;
            }
            $issueTag = $this->tagConfig->getIssueTag($tag);
            if (isset($issueTag)) {
                $this->links[] = Link::issue(
                    $issueTag,
                    $this->linkTemplates->get(LinkType::issue())?->buildUrl($issueTag),
                );
                continue;
            }
            $tmsTag = $this->tagConfig->getTmsTag($tag);
            if (isset($tmsTag)) {
                $this->links[] = Link::tms(
                    $tmsTag,
                    $this->linkTemplates->get(LinkType::tms())?->buildUrl($tmsTag),
                );
                continue;
            }
            $severityTag = $this->tagConfig->getSeverityTag($tag);
            if (isset($severityTag)) {
                /**
                 * @todo Load severity from string
                 * @link https://github.com/allure-framework/allure-php-commons2/issues/20
                 */
                $this->labels[] = new Label(Label::SEVERITY, $severityTag);
                continue;
            }
            $this->labels[] = Label::story($tag);
        }
    }

    public function getDisplayName(): ?string
    {
        $featureName = $this->feature->getTitle() ?? '<unknown feature>';
        $scenarioName = $this->scenario->getTitle() ?? '<unknown scenario>';

        return "$featureName: $scenarioName";
    }

    public function getFullName(): ?string
    {
        return null;
    }
}
