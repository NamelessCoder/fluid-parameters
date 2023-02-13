<?php
namespace NamelessCoder\FluidParameters\Parameter;

use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;

class ParameterDefinition extends ArgumentDefinition
{
    private array $oneOf = [];

    public function __construct(
        string $name,
        string $type,
        ?string $description = null,
        bool $required = false,
        array $oneOf = [],
        $defaultValue = null,
        ?bool $escape = null
    ) {
        parent::__construct($name, $type, (string) $description, $required, $defaultValue, $escape);
        $this->oneOf = $oneOf;
    }

    public function getOneOf(): array
    {
        return $this->oneOf;
    }
}
