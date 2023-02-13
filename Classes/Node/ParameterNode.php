<?php
namespace NamelessCoder\FluidParameters\Node;

use NamelessCoder\FluidParameters\Parameter\ParameterHandler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class ParameterNode extends ViewHelperNode implements NodeInterface
{
    public function __construct()
    {
        $this->uninitializedViewHelper = new ParameterHandler();
    }

    public function evaluate(RenderingContextInterface $renderingContext): string
    {
        ParameterHandler::validateParameterPresence($renderingContext);
        return '';
    }

    public function removeChildNodes(): void
    {
        $this->childNodes = [];
        $this->uninitializedViewHelper->setChildNodes([]);
    }
}
