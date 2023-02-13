<?php
namespace NamelessCoder\FluidParameters\Reflection;

use NamelessCoder\FluidParameters\Parameter\ParameterDefinition;

abstract class AbstractReflection
{
    /**
     * @var array<string, ParameterDefinition>
     */
    private array $parameterDefinitions;
    private ?string $description;
    private string $parameterMode;

    public function __construct(array $parameterDefinitions, ?string $description, string $parameterMode)
    {
        $this->parameterDefinitions = $parameterDefinitions;
        $this->description = $description;
        $this->parameterMode = $parameterMode;
    }

    /**
     * @return array<string, ParameterDefinition>
     */
    public function getParameterDefinitions(): array
    {
        return $this->parameterDefinitions;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getParameterMode(): string
    {
        return $this->parameterMode;
    }
}
