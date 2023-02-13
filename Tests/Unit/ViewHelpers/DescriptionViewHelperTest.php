<?php
namespace NamelessCoder\FluidParameters\Tests\Unit\ViewHelpers;

use NamelessCoder\FluidParameters\Node\ParameterNode;
use NamelessCoder\FluidParameters\ViewHelpers\DescriptionViewHelper;
use PHPUnit\Framework\TestCase;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;

class DescriptionViewHelperTest extends TestCase
{
    public function testRenderOutputsEmptyString(): void
    {
        $subject = new DescriptionViewHelper();
        self::assertSame('', $subject->render());
    }

    public function testRenderStaticOutputsEmptyString(): void
    {
        self::assertSame('', DescriptionViewHelper::renderStatic([], function() {}, new RenderingContext()));
    }

    public function testPostParseEventWithoutChildNode(): void
    {
        $variableProvider = new StandardVariableProvider();
        $viewHelperNode = new ParameterNode();
        DescriptionViewHelper::postParseEvent($viewHelperNode, [], $variableProvider);
        self::assertSame(null, $variableProvider->get('description-viewhelper-value'));
    }

    public function testPostParseEventWithChildNode(): void
    {
        $variableProvider = new StandardVariableProvider();
        $viewHelperNode = new ParameterNode();
        $viewHelperNode->addChildNode(new TextNode('description'));
        DescriptionViewHelper::postParseEvent($viewHelperNode, [], $variableProvider);
        self::assertSame('description', $variableProvider->get('description-viewhelper-value'));
    }
}
