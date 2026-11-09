<?php

declare(strict_types=1);

namespace Study\SitePackage\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRenderingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];
    protected array $testExtensionsToLoad = ['study/site-package'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/content.csv');
        $sitePath = Environment::getConfigPath() . '/sites/study';
        GeneralUtility::mkdir_deep($sitePath);
        file_put_contents($sitePath . '/config.yaml', Yaml::dump([
            'rootPageId' => 1,
            'base' => 'https://study.example.test/',
            'dependencies' => ['study/site-package'],
            'languages' => [[
                'title' => 'English', 'enabled' => true, 'languageId' => 0,
                'base' => '/', 'locale' => 'en-US', 'hreflang' => 'en-US',
                'navigationTitle' => 'English', 'flag' => 'global',
            ]],
        ], 5));
    }

    #[Test]
    public function contentRendersInItsAssignedArea(): void
    {
        $xpath = $this->renderPage();
        $main = $this->textAt($xpath, '//main');
        $sidebar = $this->textAt($xpath, '//aside');

        self::assertStringContainsString('Main first body', $main);
        self::assertStringContainsString('Main later body', $main);
        self::assertStringNotContainsString('Sidebar body', $main);
        self::assertStringContainsString('Sidebar body', $sidebar);
        self::assertStringNotContainsString('Main first body', $sidebar);
    }

    #[Test]
    public function mainContentFollowsEditorSorting(): void
    {
        $xpath = $this->renderPage();
        $headings = $xpath->query('//main//h2');
        self::assertNotFalse($headings);
        self::assertCount(2, $headings);
        self::assertSame('Main first', trim($this->textAt($xpath, '(//main//h2)[1]')));
        self::assertSame('Main later', trim($this->textAt($xpath, '(//main//h2)[2]')));
    }

    #[Test]
    public function hiddenOtherPageAndUnmappedContentAreExcluded(): void
    {
        $body = $this->textAt($this->renderPage(), '//body');
        self::assertStringNotContainsString('Hidden body', $body);
        self::assertStringNotContainsString('Other page body', $body);
        self::assertStringNotContainsString('Unmapped body', $body);
    }

    #[Test]
    public function eachPageUsesItsOwnRecords(): void
    {
        $body = $this->textAt($this->renderPage('/other'), '//body');
        self::assertStringContainsString('Other page body', $body);
        self::assertStringNotContainsString('Main first body', $body);
        self::assertStringNotContainsString('Sidebar body', $body);
    }

    #[Test]
    public function emptyAreasRenderWithoutAnException(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')->delete('tt_content', ['pid' => 1]);
        $xpath = $this->renderPage();
        self::assertSame('', trim($this->textAt($xpath, '//main')));
        self::assertSame('', trim($this->textAt($xpath, '//aside')));
        self::assertStringContainsString('Hello from our site package', $this->textAt($xpath, '//header'));
    }

    #[Test]
    public function pageIncludesThePublishedStylesheet(): void
    {
        $xpath = $this->renderPage();
        $links = $xpath->query('//link[@rel="stylesheet" and contains(@href, "/Css/site.css")]');
        self::assertNotFalse($links);
        self::assertCount(1, $links);
        $link = $links->item(0);
        self::assertInstanceOf(\DOMElement::class, $link);
        $path = parse_url($link->getAttribute('href'), PHP_URL_PATH);
        self::assertIsString($path);
        self::assertFileExists(Environment::getPublicPath() . $path);
    }

    #[Test]
    public function menuShowsFirstLevelPagesInOrderUsingNavigationTitles(): void
    {
        $xpath = $this->renderPage();
        $links = $xpath->query('//nav[@aria-label="Main navigation"]//a');
        self::assertNotFalse($links);
        self::assertCount(2, $links);
        $labels = [];
        $paths = [];
        foreach ($links as $link) {
            self::assertInstanceOf(\DOMElement::class, $link);
            $labels[] = trim($link->textContent);
            $paths[] = parse_url($link->getAttribute('href'), PHP_URL_PATH);
        }
        self::assertSame(['About us', 'Other page'], $labels);
        self::assertSame(['/about', '/other'], $paths);
    }

    #[Test]
    public function menuExcludesHiddenUnlistedNestedPagesAndFolders(): void
    {
        $navigation = $this->textAt($this->renderPage(), '//nav');
        foreach (['Hidden page', 'Not in menu', 'Nested page', 'Records folder'] as $label) {
            self::assertStringNotContainsString($label, $navigation);
        }
        // Excluded from navigation does not mean the page is disabled.
        $this->renderPage('/unlisted');
    }

    #[Test]
    public function menuMarksOnlyTheCurrentPage(): void
    {
        $xpath = $this->renderPage('/other');
        $current = $xpath->query('//nav//a[@aria-current="page"]');
        self::assertNotFalse($current);
        self::assertCount(1, $current);
        self::assertSame('Other page', trim($this->textAt($xpath, '//nav//a[@aria-current="page"]')));
    }

    #[Test]
    public function nestedPageKeepsTheSiteMenuWithoutMarkingItsParentCurrent(): void
    {
        $xpath = $this->renderPage('/other/nested');
        $navigation = $this->textAt($xpath, '//nav');
        self::assertStringContainsString('About us', $navigation);
        self::assertStringContainsString('Other page', $navigation);
        self::assertStringNotContainsString('Nested page', $navigation);
        $current = $xpath->query('//nav//a[@aria-current="page"]');
        self::assertNotFalse($current);
        self::assertCount(0, $current);
    }

    #[Test]
    public function emptyMenuOmitsTheNavigationLandmark(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['nav_hide' => 1], ['pid' => 1]);
        $navigation = $this->renderPage()->query('//nav');
        self::assertNotFalse($navigation);
        self::assertCount(0, $navigation);
    }

    private function renderPage(string $path = '/'): \DOMXPath
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://study.example.test' . $path));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $html = (string)$response->getBody();
        self::assertStringNotContainsString('Oops, an error occurred', $html);

        $document = new \DOMDocument();
        // DOMDocument's HTML4 parser reports valid HTML5 elements as unknown tags.
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML($html));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return new \DOMXPath($document);
    }

    private function textAt(\DOMXPath $xpath, string $expression): string
    {
        $nodes = $xpath->query($expression);
        self::assertNotFalse($nodes);
        self::assertCount(1, $nodes);
        $node = $nodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $node);
        return $node->textContent;
    }
}
