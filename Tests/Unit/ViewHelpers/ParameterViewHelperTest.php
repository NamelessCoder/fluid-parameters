<?php
namespace NamelessCoder\FluidParameters\Tests\Unit\ViewHelpers;

use NamelessCoder\FluidParameters\Node\ParameterNode;
use NamelessCoder\FluidParameters\ViewHelpers\ParameterViewHelper;
use PHPUnit\Framework\TestCase;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;

class ParameterViewHelperTest extends TestCase
{
    public function testRenderOutputsEmptyString(): void
    {
        $subject = $this->getMockBuilder(ParameterViewHelper::class)
            ->onlyMethods(['renderChildren'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->setRenderingContext(new RenderingContext());
        $subject->setArguments(
            [
                'name' => 'test',
                'type' => 'string',
            ]
        );
        self::assertSame('', $subject->render());
    }

    public function testRenderStaticOutputsEmptyString(): void
    {
        $arguments = [
            'name' => 'test',
            'type' => 'string',
        ];
        self::assertSame('', ParameterViewHelper::renderStatic($arguments, function() {}, new RenderingContext()));
    }

    public function testPostParseEventWithInvalidChildNode(): void
    {
        $node = new ParameterNode();
        $node->addChildNode(new TextNode('foo'));

        $variableProvider = new StandardVariableProvider();
        $variableProvider->add(
            'parameterViewHelperNodes',
            [
                $node,
            ]
        );

        self::expectExceptionCode(1675260921);
        self::expectExceptionMessage('Encountered an unexpected child node of f:parameter, type ' . TextNode::class);
        ParameterViewHelper::postParseEvent($node, [], $variableProvider);
    }

    public function testPostParseEventWithInvalidViewHelperChildNode(): void
    {
        $viewHelperNode = $this->getMockBuilder(ViewHelperNode::class)
            ->onlyMethods(['getViewHelperClassName'])
            ->disableOriginalConstructor()
            ->getMock();
        $viewHelperNode->method('getViewHelperClassName')->willReturn('vh-class');

        $node = new ParameterNode();
        $node->addChildNode($viewHelperNode);

        $variableProvider = new StandardVariableProvider();
        $variableProvider->add(
            'parameterViewHelperNodes',
            [
                $node,
            ]
        );

        self::expectExceptionCode(1675260921);
        self::expectExceptionMessage(
            'Encountered an unexpected child node of f:parameter, type '
            . get_class($viewHelperNode)
            . ' {"class":"vh-class"}'
        );

        ParameterViewHelper::postParseEvent($node, [], $variableProvider);
    }
}