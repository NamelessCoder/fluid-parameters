<?php
namespace NamelessCoder\FluidParameters\Reflection;

use NamelessCoder\FluidParameters\Parameter\ParameterDefinition;
use NamelessCoder\FluidParameters\ViewHelpers\DescriptionViewHelper;
use NamelessCoder\FluidParameters\ViewHelpers\Parameter\ModeViewHelper;
use NamelessCoder\FluidParameters\ViewHelpers\ParameterViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\ViewHelpers\SectionViewHelper;

class ParameterExtractor
{
    private RenderingContextInterface $renderingContext;

    public function __construct(RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
    }

    public function parseTemplate(string $templatePathAndFilename): TemplateReflection
    {
        $fileContents = (string) file_get_contents($templatePathAndFilename);
        $parsingState = $this->renderingContext->getTemplateParser()->parse($fileContents);
        $registry = $parsingState->getVariableContainer()->get(ParameterViewHelper::PARAMETER_REGISTRY_VARIABLE);

        $rootNode = $parsingState->getRootNode();

        $sectionReflections = [];

        foreach ($rootNode->getChildNodes() as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === SectionViewHelper::class
            ) {
                $name = $childNode->getArguments()['name']->evaluate($this->renderingContext);
                $sectionParameterDefinitions = $this->extractParameterReflectionsFromNode($childNode);
                $sectionDescription = $this->extractDescriptionFromNode($childNode);
                $sectionParameterMode = $this->extractParameterModeFromNode($childNode);
                $sectionReflections[$name] = new SectionReflection(
                    $sectionParameterDefinitions,
                    $sectionDescription,
                    $sectionParameterMode
                );
            }
        }

        $parameterDefinitions = $this->extractParameterReflectionsFromNode($rootNode);
        $description = $this->extractDescriptionFromNode($rootNode);
        $parameterMode = $this->extractParameterModeFromNode($rootNode);

        return new TemplateReflection(
            $parameterDefinitions,
            $description,
            $parameterMode,
            $sectionReflections
        );
    }

    /**
     * @return array<string, ParameterDefinition>
     */
    private function extractParameterReflectionsFromNode(NodeInterface $node): array
    {
        $definitions = [];
        foreach ($node->getChildNodes() as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ParameterViewHelper::class
                && (($parameterViewHelper = $childNode->getUninitializedViewHelper())) instanceof ParameterViewHelper
            ) {
                $arguments = $this->extractViewHelperArguments($childNode);
                $parameterDefinition = $parameterViewHelper->createParameterDefinition($arguments);
                $name = $parameterDefinition->getName();

                $definitions[$name] = $parameterDefinition;
            }
        }
        return $definitions;
    }

    private function extractDescriptionFromNode(NodeInterface $node): ?string
    {
        foreach ($node->getChildNodes() as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === DescriptionViewHelper::class
                && (($textNode = $childNode->getChildNodes()[0])) instanceof TextNode
            ) {
                return trim($textNode->evaluate($this->renderingContext));
            }
        }
        return null;
    }

    private function extractParameterModeFromNode(NodeInterface $node): string
    {
        foreach ($node->getChildNodes() as $childNode) {
            if ($childNode instanceof ViewHelperNode
                && $childNode->getViewHelperClassName() === ModeViewHelper::class
            ) {
                $valueNode = $childNode->getArguments()['mode'] ?? $childNode->getChildNodes()[0];
                $value = $valueNode->evaluate($this->renderingContext);
                if (is_string($value)) {
                    return $value;
                }
            }
        }
        return ModeViewHelper::MODE_LOOSE;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractViewHelperArguments(ViewHelperNode $viewHelperNode): array
    {
        $arguments = [];
        foreach ($viewHelperNode->getArguments() as $name => $node) {
            $arguments[$name] = $node->evaluate($this->renderingContext);
        }
        return $arguments;
    }
}
