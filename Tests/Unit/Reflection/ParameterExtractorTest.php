<?php
namespace NamelessCoder\FluidParameters\Tests\Unit\Reflection;

use NamelessCoder\FluidParameters\Parameter\ParameterDefinition;
use NamelessCoder\FluidParameters\Reflection\ParameterExtractor;
use NamelessCoder\FluidParameters\Reflection\SectionReflection;
use NamelessCoder\FluidParameters\ViewHelpers\Parameter\ModeViewHelper;
use PHPUnit\Framework\TestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

class ParameterExtractorTest extends TestCase
{
    public function testExtractReflection(): void
    {
        $templateFile = __DIR__ . '/../../Fixtures/TemplateWithSections.html';

        $renderingContext = new RenderingContext();
        $renderingContext->getViewHelperResolver()->addNamespace('f', 'NamelessCoder\\FluidParameters\\ViewHelpers');

        $subject = new ParameterExtractor($renderingContext);
        $reflection = $subject->parseTemplate($templateFile);

        self::assertInstanceOf(
            ParameterDefinition::class,
            $reflection->getParameterDefinitions()['test'],
            'Parameter "test" of template does not have a corresponding ParameterDefinition'
        );
        self::assertSame(
            ['foo' => 'bar'],
            $reflection->getParameterDefinitions()['test']->getOptions(),
            'Parameter "test" of template does not have expected options array {"foo": "bar"}'
        );
        self::assertSame(
            2,
            count($reflection->getSections()),
            'Extracted reflection does not contain the expected number of sections'
        );
        self::assertInstanceOf(
            ParameterDefinition::class,
            $reflection->getParameterDefinitions()['test2'],
            'Parameter "test2" of template does not have a corresponding ParameterDefinition'
        );
        self::assertInstanceOf(
            SectionReflection::class,
            $reflection->fetchSection('section1'),
            'TemplateReflection did not contain expected SectionReflection for "section1"'
        );
        self::assertInstanceOf(
            SectionReflection::class,
            $reflection->fetchSection('section2'),
            'TemplateReflection did not contain expected SectionReflection for "section2"'
        );
        self::assertSame(
            'Fixture template file',
            $reflection->getDescription(),
            'Description on template level does not contain expected value'
        );
        self::assertSame(
            ModeViewHelper::MODE_STRICT,
            $reflection->fetchSection('section2')->getParameterMode(),
            'Parameter mode of section "section2" does not match expected "strict"'
        );
    }
}
