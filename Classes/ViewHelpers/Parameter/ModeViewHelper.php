<?php
namespace NamelessCoder\FluidParameters\ViewHelpers\Parameter;

use NamelessCoder\FluidParameters\ViewHelpers\ParameterViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Parameter Mode
 *
 * Use this to set the parameter handling mode. The following modes are possible:
 *
 * - "loose" (default) will allow any variables that are not declared as parameters.
 * - "strict" will report any variable that isn't declared as a parameter, as an error.
 *
 * The special variable "settings" will always be allowed regardless of "strict" mode.
 * If you do not use this ViewHelper, the parameter handling mode will be assumed "loose".
 *
 * Be aware: this ViewHelper must be used before any `f:parameter` ViewHelpers. If it is
 * used in a template that has no `f:parameter` usages, it is silently ignored.
 *
 * Usage examples:
 * --------------
 *
 *     <f:parameter.mode>strict</f:parameter.mode>
 *     <f:parameter.mode mode="strict" />
 *     {f:paramter.mode(mode: strict)}
 *
 */
class ModeViewHelper extends AbstractViewHelper
{
    public const OPTION_MODE = 'mode';

    public const MODE_LOOSE = 'loose';
    public const MODE_STRICT = 'strict';

    protected $escapeChildren = false;
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument(
            self::OPTION_MODE,
            'string',
            'Mode of parameter handling. Valid values: "strict", "loose". Default is "loose". Set to "strict" to '
            . 'report any undeclared variable as an error.',
            false,
            null
        );
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): void {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        /** @var array $options */
        $options = $viewHelperVariableContainer->get(
            ParameterViewHelper::class,
            ParameterViewHelper::OPTION_REGISTRY_VARIABLE
        ) ?? [];

        $mode = $arguments[self::OPTION_MODE] ?? $renderChildrenClosure() ?? self::MODE_LOOSE;

        $validModes = [self::MODE_STRICT, self::MODE_LOOSE];

        if (!in_array($mode, $validModes, true)) {
            throw new Exception(
                'Parameter-mode "' . $mode . '" is not one of the valid modes: ' . implode(', ', $validModes),
                1675440530
            );
        }

        $options[self::OPTION_MODE] = $mode;

        $viewHelperVariableContainer->addOrUpdate(
            ParameterViewHelper::class,
            ParameterViewHelper::OPTION_REGISTRY_VARIABLE,
            $options
        );
    }
}
