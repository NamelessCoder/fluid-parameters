<?php
namespace NamelessCoder\FluidParameters\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface;

class RenderingTest extends TestCase
{
    private RenderingContextInterface $renderingContext;
    private ViewInterface $view;

    protected function setUp(): void
    {
        $this->renderingContext = new RenderingContext();
        $this->renderingContext->getViewHelperResolver()->addNamespace(
            'f',
            'NamelessCoder\\FluidParameters\\ViewHelpers'
        );
        $this->renderingContext->getTemplatePaths()->setPartialRootPaths([__DIR__ . '/../Fixtures/']);
        $this->view = new TemplateView($this->renderingContext);
        parent::setUp();
    }

    public function testRenderingWithLooseParameterModeIgnoresUndeclaredVariables(): void
    {
        $out = $this->view->renderPartial('SimpleWithLooseParameterMode', null, ['test' => 'Test', 'unknown' => 'foo']);
        self::assertSame('Test', trim($out));
    }

    public function testRenderingWithMissingRequiredVariableThrowsException(): void
    {
        self::expectExceptionCode(1675268804);
        self::expectExceptionMessage('Required variable "test" was not passed');
        $this->view->renderPartial('SimpleWithRequiredVariable', null, []);
    }

    public function testRenderingWithMismatchedValueOfEnumeratedVariable(): void
    {
        self::expectExceptionCode(1675268818);
        self::expectExceptionMessage(
            'Parameter "test" with value "invalid" was not one of the allowed values: ["a","b","c"]'
        );
        $this->view->renderPartial('SimpleWithEnumeratedVariable', null, ['test' => 'invalid']);
    }

    public function testRenderingWithLooseParameterModeThrowsErrorOnUndeclaredVariable(): void
    {
        self::expectExceptionCode(1675268804);
        self::expectExceptionMessage('Unxpected (undefined) template variable(s) encountered: unknown');
        $this->view->renderPartial('SimpleWithStrictParameterMode', null, ['test' => 'Test', 'unknown' => 'foo']);
    }

    public function testRenderingWithInvalidParameterModeThrowsException(): void
    {
        self::expectExceptionCode(1675440530);
        self::expectExceptionMessage('Parameter-mode "invalid" is not one of the valid modes: strict, loose');
        $this->view->renderPartial('SimpleWithInvalidParameterMode', null, []);
    }
}
