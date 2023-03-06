<?php
namespace NamelessCoder\FluidParameters\Parameter;

use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;

class ParameterDefinition extends ArgumentDefinition
{
    private array $oneOf = [];
    private array $options = [];

    public function __construct(
        string $name,
        string $type,
        ?string $description = null,
        bool $required = false,
        array $oneOf = [],
        array $options = [],
        $defaultValue = null,
        ?bool $escape = null
    ) {
        parent::__construct($name, $type, (string) $description, $required, $defaultValue, $escape);
        $this->oneOf = $oneOf;
        $this->options = $options;
    }

    public function getOneOf(): array
    {
        return $this->oneOf;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
