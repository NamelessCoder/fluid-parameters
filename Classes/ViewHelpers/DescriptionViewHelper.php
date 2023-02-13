<?php
namespace NamelessCoder\FluidParameters\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Description ViewHelper
 *
 * Allows entering a description of the template/section that is not rendered when
 * the template is rendered, but can be extracted when extracting parameter definitions
 * from the template file or section.
 *
 * Usage examples:
 * ---------------
 *
 *     <f:description>
 *         This is a description of the template file.
 *     </f:description>
 *
 *     <f:section name="MySection">
 *         <f:description>
 *             This is a description of the section.
 *         </f:description>
 *     </f:section>
 *
 * WARNING: Do not use Fluid code within this Viewhelper, valid or invalid, including
 * using variables. Doing so will cut off the description so it contains only the content
 * leading up to the first variable or Fluid code.
 */
class DescriptionViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;
    protected $escapeOutput = false;

    public static function postParseEvent(
        ViewHelperNode $node,
        array $arguments,
        VariableProviderInterface $variableContainer
    ): void {
        $description = null;
        $childNode = $node->getChildNodes()[0] ?? null;
        if ($childNode instanceof TextNode) {
            $description = $childNode->getText();
        }
        $variableContainer->add('description-viewhelper-value', $description);
    }

    public function render(): string
    {
        return '';
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        return '';
    }
}
