<?php
namespace NamelessCoder\FluidParameters\ViewHelpers;

use NamelessCoder\FluidParameters\Node\ParameterNode;
use NamelessCoder\FluidParameters\Parameter\ParameterDefinition;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\ViewHelpers\SectionViewHelper;

/**
 * Parameter for template or section
 *
 * Defines a parameter with a given data type and sets various instructions for
 * the parameter - whether or not is is required, what the default value is, which
 * values are allowed, etc.
 *
 * Using this ViewHelper triggers the parameter handling for the template, partial,
 * layout or section within either of these types of template files.
 */
class ParameterViewHelper extends AbstractViewHelper
{
    private const INSTANCE_REGISTRY_VARIABLE = 'parameterViewHelperNodes';
    private const SECTION_REGISTRY_VARIABLE = 'section';
    public const PARAMETER_REGISTRY_VARIABLE = 'parameters';
    public const OPTION_REGISTRY_VARIABLE = 'options';

    protected $escapeChildren = false;
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('name', 'string', 'Name of the parameter', true);
        $this->registerArgument('description', 'string', 'Description of the parameter');
        $this->registerArgument('type', 'string', 'Data type required', true);
        $this->registerArgument('required', 'bool', 'Is this parameter required?', false, false);
        $this->registerArgument('default', 'mixed', 'Optional default value of the parameter (default: null)');
        $this->registerArgument('oneOf', 'mixed', 'CSV string or array list of allowed values', false, []);
    }

    public function render(): string
    {
        return (string) self::renderStatic(
            $this->arguments,
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        self::registerParameter($renderingContext, $arguments);
        $renderChildrenClosure();
        return '';
    }

    public static function postParseEvent(
        ViewHelperNode $node,
        array $arguments,
        VariableProviderInterface $variableContainer
    ): void {
        /*
         * Manipulate the parsing state to add a virtual ViewHelper as child of this current ViewHelper,
         * while removing any previously added instances of the virtual ViewHelper from child nodes of
         * any preceding ParameterViewHelper nodes. Handle possible section context as well.
         *
         * This ensures that:
         *
         * - Only a single instance of ParameterNode exists in the parsing tree.
         * - An instance of ParameterNode is guaranteed to exist in the parsing tree, if at least
         *   one bn:parameter was declared.
         * - A fresh parameter collection is created when context switches from non-section to section,
         *   or from one section to another section.
         */
        self::handlePossibleSectionContext($variableContainer);
        self::createOrMoveParameterNode($node, $variableContainer);
    }

    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        ViewHelperNode $node,
        TemplateCompiler $compiler
    ): string {
        return sprintf(
            self::class  . '::registerParameter($renderingContext, %s) && %s()',
            $argumentsName,
            $closureName
        );
    }

    public static function registerParameter(
        RenderingContextInterface $renderingContext,
        array $arguments
    ): bool {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        /** @var array $parameters */
        $parameters = $viewHelperVariableContainer->get(self::class, self::PARAMETER_REGISTRY_VARIABLE) ?? [];

        $name = $arguments['name'];
        $parameters[$name] = self::createParameterDefinition($arguments);

        $viewHelperVariableContainer->addOrUpdate(self::class, self::PARAMETER_REGISTRY_VARIABLE, $parameters);

        return true;
    }

    public static function createParameterDefinition(array $arguments): ParameterDefinition
    {
        $arguments['oneOf'] = $arguments['oneOf'] ?? [];
        $oneOf = is_array($arguments['oneOf'])
            ? $arguments['oneOf']
            : array_map('trim', explode(',', (string) $arguments['oneOf']));
        return new ParameterDefinition(
            $arguments['name'],
            $arguments['type'],
            $arguments['description'] ?? null,
            (bool) ($arguments['required'] ?? false),
            $oneOf,
            $arguments['default'] ?? null,
            false
        );
    }

    private static function handlePossibleSectionContext(VariableProviderInterface $variableContainer): void
    {
        /** @var array $sections */
        $sections = $variableContainer->get('1457379500_sections') ?? [];
        $possibleSectionNode = array_pop($sections);
        $sectionNameFromContext = $variableContainer->get(self::SECTION_REGISTRY_VARIABLE);
        $currentSectionName = null;

        if ($possibleSectionNode instanceof ViewHelperNode
            && is_a($possibleSectionNode->getViewHelperClassName(), SectionViewHelper::class, true)
        ) {
            /** @var TextNode $nameArgumentNode */
            $nameArgumentNode = $possibleSectionNode->getArguments()['name'];
            $currentSectionName = $nameArgumentNode->getText();
            if ($currentSectionName !== $sectionNameFromContext) {
                // Parameters are declared within a section and the section name differs from the previously recorded
                // section name. This means we have switched context to a new section and we must flush all previously
                // recorded parameter definitions.
                $variableContainer->add(self::INSTANCE_REGISTRY_VARIABLE, []);
                $variableContainer->add(self::SECTION_REGISTRY_VARIABLE, $currentSectionName);
            }
        }
        $variableContainer->add(self::SECTION_REGISTRY_VARIABLE, $currentSectionName);
    }

    private static function createOrMoveParameterNode(
        NodeInterface $node,
        VariableProviderInterface $variableContainer
    ): void {
        $validationNode = null;
        /** @var ViewHelperNode[] $previousInstances */
        $previousInstances = $variableContainer->get(self::INSTANCE_REGISTRY_VARIABLE) ?? [];
        foreach ($previousInstances as $previousInstance) {
            $childNode = $previousInstance->getChildNodes()[0] ?? null;

            if ($childNode === null) {
                continue;
            }

            if (!$childNode instanceof ParameterNode) {
                $additionalInfo = null;
                if ($childNode instanceof ViewHelperNode) {
                    $additionalInfo = [
                        'class' => $childNode->getViewHelperClassName(),
                    ];
                }
                throw new Exception(
                    'Encountered an unexpected child node of f:parameter, type ' . get_class($childNode) .
                    (!empty($additionalInfo) ? ' ' . json_encode($additionalInfo) : ''),
                    1675260921
                );
            }

            // Unfortunately we need to be a bit abusive here and violate the visibility of
            // $previousInstance->childNodes. Any previously assigned child nodes have to be removed but none of the API
            // methods on ViewHelperNode will allow this.
            $childNodesProperty = new \ReflectionProperty($previousInstance, 'childNodes');
            $childNodesProperty->setAccessible(true);
            $childNodesProperty->setValue($previousInstance, []);

            $childNode->removeChildNodes();
            $validationNode = $childNode;
        }

        $node->addChildNode($validationNode ?? new ParameterNode());

        $previousInstances[] = $node;

        $variableContainer->add(self::INSTANCE_REGISTRY_VARIABLE, $previousInstances);
    }
}
