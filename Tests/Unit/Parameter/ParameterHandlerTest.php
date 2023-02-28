<?php
namespace NamelessCoder\FluidParameters\Tests\Unit\Parameter;

use NamelessCoder\FluidParameters\Node\ParameterNode;
use NamelessCoder\FluidParameters\Parameter\ParameterDefinition;
use NamelessCoder\FluidParameters\Parameter\ParameterHandler;
use NamelessCoder\FluidParameters\ViewHelpers\ParameterViewHelper;
use PHPUnit\Framework\TestCase;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

class ParameterHandlerTest extends TestCase
{
    /**
     * @dataProvider getCastingTestValues
     * @param mixed $expected
     * @param mixed $input
     * @param string $type
     */
    public function testCasting($expected, $input, string $type): void
    {
        $this->runCastingTest($expected, $input, $type, false);
    }

    /**
     * @dataProvider getCastingTestValues
     * @param mixed $expected
     * @param mixed $input
     * @param string $type
     */
    public function testCastingWithStatic($expected, $input, string $type): void
    {
        $this->runCastingTest($expected, $input, $type, true);
    }

    private function runCastingTest($expected, $input, string $type, bool $static): void
    {
        $parameterDefinition = new ParameterDefinition('test', $type);

        $renderingContext = new RenderingContext();
        $renderingContext->getViewHelperVariableContainer()->add(
            ParameterViewHelper::class,
            ParameterViewHelper::PARAMETER_REGISTRY_VARIABLE,
            ['test' => $parameterDefinition]
        );
        $renderingContext->getVariableProvider()->add('test', $input);

        if ($static) {
            $closure = function() {};
            ParameterHandler::renderStatic([], $closure, $renderingContext);
        } else {
            $subject = new ParameterHandler();
            $subject->setRenderingContext($renderingContext);
            $subject->render();
        }

        $output = $renderingContext->getVariableProvider()->get('test');

        switch ($type) {
            case 'object':
            case 'DateTime':
            case ParameterHandler::class:
                self::assertEquals($expected, $output);
                break;
            default:
                self::assertSame($expected, $output);
                break;
        }
    }

    public function getCastingTestValues(): array
    {
        return [
            'int' => [1, '1', 'int'],
            'integer' => [1, '1', 'integer'],
            'float' => [1.5, '1.5', 'float'],
            'double' => [1.5, '1.5', 'double'],
            'decimal' => [1.5, '1.5', 'decimal'],
            'bool (true)' => [true, '1', 'bool'],
            'boolean (true)' => [true, '1', 'boolean'],
            'bool (false)' => [false, '0', 'bool'],
            'boolean (false)' => [false, '0', 'boolean'],
            'array' => [['a', 'b'], ['a', 'b'], 'array'],
            'array (CSV)' => [['a', 'b'], 'a,b', 'array'],
            'object' => [new \stdClass(), new \stdClass(), 'object'],
            'object (from array)' => [(object) ['foo' => 'bar'], ['foo' => 'bar'], 'object'],
            'DateTime (number)' => [\DateTime::createFromFormat('U', 12345678), 12345678, 'DateTime'],
            'DateTime (string)' => [new \DateTime('2023-02-09 12:00'), '2023-02-09 12:00', 'DateTime'],
            'DateTime (instance)' => [new \DateTime('2023-02-09 12:00'), new \DateTime('2023-02-09 12:00'), 'DateTime'],
            'class name (instance)' => [new ParameterHandler(), new ParameterHandler(), ParameterHandler::class],
            'class name (string)' => [new ParameterHandler(), 'unused', ParameterHandler::class],
        ];
    }

    public function testThrowsExceptionWhenCastingEncountersIncompatibleObjectType(): void
    {
        self::expectExceptionCode(1677595613);
        $this->runCastingTest(null, new \DateTime('now'), ParameterHandler::class, false);
    }

    public function testCompile(): void
    {
        $subject = new ParameterHandler();
        $init = 'init';
        $compiled = $subject->compile('arg', 'closure', $init, new ParameterNode(), new TemplateCompiler());
        self::assertSame(ParameterHandler::class . '::validateParameterPresence($renderingContext)', $compiled);
        self::assertSame('init', $init);
    }
}
