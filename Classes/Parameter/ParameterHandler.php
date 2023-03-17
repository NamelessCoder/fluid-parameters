<?php
namespace NamelessCoder\FluidParameters\Parameter;

use NamelessCoder\FluidParameters\ViewHelpers\Parameter\ModeViewHelper;
use NamelessCoder\FluidParameters\ViewHelpers\ParameterViewHelper;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class ParameterHandler extends AbstractViewHelper
{
    public function render(): void
    {
        self::validateParameterPresence($this->renderingContext);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        self::validateParameterPresence($renderingContext);
        return '';
    }

    public static function validateParameterPresence(RenderingContextInterface $renderingContext): void
    {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        $variableProvider = $renderingContext->getVariableProvider();
        /** @var array $variables */
        $variables = $variableProvider->getAll();
        /** @var ParameterDefinition[] $definitions */
        $definitions = $viewHelperVariableContainer->get(
            ParameterViewHelper::class,
            ParameterViewHelper::PARAMETER_REGISTRY_VARIABLE
        );
        /** @var array $options */
        $options = $viewHelperVariableContainer->get(
            ParameterViewHelper::class,
            ParameterViewHelper::OPTION_REGISTRY_VARIABLE
        ) ?? [];

        foreach ($definitions as $name => $definition) {
            if ($definition->isRequired() && !array_key_exists($name, $variables)) {
                throw new Exception('Required variable "' . $name . '" was not passed', 1675268804);
            }
            $type = $definition->getType();
            $value = $variables[$name] ?? $definition->getDefaultValue();
            try {
                $value = self::cast($value, $type);
            } catch (Exception $exception) {
                throw new Exception(
                    'Parameter "' . $name . '": ' . $exception->getMessage(),
                    1677595613
                );
            }

            if (!empty($value) && !empty(($oneOf = $definition->getOneOf()))) {
                // A non-empty value was assigned and the parameter lists allowed values. Verify that the non-empty
                // value is one of the exact allowed values. The "not empty" check is added to allow parameters which
                // have a default value of NULL (implied optional) but still list allowed values, to be seen as valid
                // even when NULL (or a corresponding empty value) is assigned. Such an optional parameter will only be
                // validated against "oneOf" allowed values if it either has a non-empty default value OR was passed
                // to the template with a non-empty value.
                $oneOf = array_map(
                    function ($value) use ($type) {
                        return self::cast($value, $type);
                    },
                    $oneOf
                );
                if (!in_array($value, $oneOf, true)) {
                    throw new Exception(
                        'Parameter "' . $name . '" with value ' . json_encode($value) .
                        ' was not one of the allowed values: ' . json_encode($oneOf),
                        1675268818
                    );
                }
            }

            $variableProvider->add($name, $value);
            unset($variables[$name]);
        }

        // Validate that no unexpected variables were assigned; ignoring the special always-present "settings" variable.
        unset($variables['settings']);
        if (!empty($variables) && ($options[ModeViewHelper::OPTION_MODE] ?? null) === ModeViewHelper::MODE_STRICT) {
            throw new Exception(
                'Unxpected (undefined) template variable(s) encountered: ' . implode(', ', array_keys($variables)),
                1675268804
            );
        }

        $viewHelperVariableContainer->remove(
            ParameterViewHelper::class,
            ParameterViewHelper::PARAMETER_REGISTRY_VARIABLE
        );
        $viewHelperVariableContainer->remove(
            ParameterViewHelper::class,
            ParameterViewHelper::OPTION_REGISTRY_VARIABLE
        );
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function cast($value, string $type, ?string $subType = null)
    {
        $subType = null;
        if (strpos($type, '[]') !== false) {
            /** @var string $subType */
            $subType = substr($type, 0, -2);
            $type = 'array';
        }
        switch ($type) {
            case 'int':
            case 'integer':
                $value = is_scalar($value) || is_null($value) ? (int) $value : $value;
                break;
            case 'float':
            case 'double':
            case 'decimal':
                $value = $value = is_scalar($value) || is_null($value) ? (float) $value : $value;
                break;
            case 'string':
                $value = is_scalar($value) || is_null($value) ? (string) $value : $value;
                break;
            case 'array':
                if (is_string($value)) {
                    $value = array_map('trim', explode(',', $value));
                }
                if ($subType !== null && is_iterable($value)) {
                    $newArray = [];
                    foreach ($value as $key => $subValue) {
                        $newArray[$key] = self::cast($subValue, $subType);
                    }
                    $value = $newArray;
                } else {
                    $value = (array) $value;
                }
                break;
            case 'bool':
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'DateTime':
                if (is_numeric($value)) {
                    $value = \DateTime::createFromFormat('U', (string) $value);
                } elseif (is_string($value)) {
                    $value = new \DateTime($value);
                }
                break;
            case 'object':
                // Fall-through is intentional; default handling is to check for specific type of object by class name
            default:
                if ($value === null) {
                    // Do nothing: value is null and null is an acceptable value of an object or specific class.
                } elseif (class_exists($type) && !is_object($value)) {
                    $value = new $type($value);
                } elseif (is_object($value) && $type !== 'object') {
                    $class = get_class($value);
                    $fqn = $class;
                    $sqn = strpos($class, '\\') !== false ? substr($class, strrpos($class, '\\') + 1) : $class;
                    if ($type !== $sqn && $type !== $fqn && !is_a($value, $type, true)) {
                        throw new Exception(
                            'Argument is not of expected type "' . $type . '". Found: ' . $fqn,
                            1677595613
                        );
                    }
                } else {
                    $value = (object) $value;
                }
                break;
        }
        return $value;
    }

    public function compile(
        $argumentsName,
        $closureName,
        &$initializationPhpCode,
        ViewHelperNode $node,
        TemplateCompiler $compiler
    ) {
        return self::class . '::validateParameterPresence($renderingContext)';
    }
}
