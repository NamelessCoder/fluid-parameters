<?php
namespace NamelessCoder\FluidParameters\Reflection;

class TemplateReflection extends AbstractReflection
{
    /**
     * @var array<string, SectionReflection>
     */
    private array $sectionReflections;

    public function __construct(
        array $parameterDefinitions,
        ?string $description,
        string $parameterMode,
        array $sectionReflections
    ) {
        parent::__construct($parameterDefinitions, $description, $parameterMode);
        $this->sectionReflections = $sectionReflections;
    }

    /**
     * @return array<string, SectionReflection>
     */
    public function getSections(): array
    {
        return $this->sectionReflections;
    }

    public function fetchSection(string $name): ?SectionReflection
    {
        return $this->sectionReflections[$name] ?? null;
    }
}
