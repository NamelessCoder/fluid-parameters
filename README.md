[![Latest Stable Version](https://poser.pugx.org/namelesscoder/fluid-parameters/v/stable.svg?style=flat-square)](https://github.com/NamelessCoder/fluid-parameters)
[![Total Downloads](https://poser.pugx.org/namelesscoder/fluid-parameters/d/total?style=flat-square)](https://packagist.org/packages/namelesscoder/fluid-parameters)
[![Monthly Downloads](https://poser.pugx.org/namelesscoder/fluid-parameters/d/monthly?style=flat-square)](https://packagist.org/packages/namelesscoder/fluid-parameters)
[![Coverage Status](https://img.shields.io/coveralls/NamelessCoder/fluid-parameters/master.svg?style=flat-square)](https://coveralls.io/r/NamelessCoder/fluid-parameters)

# TYPO3 Fluid extension `fluid_parameters`

API to enable declaring parameters for a Fluid template, partial, layout or section within any of those types of files.

## What does it do?

In short: _allows you to declare required and optional parameters for a Fluid asset_. It does this by emulating a subset
of the features that would have been part of Fluid 3.0, namely `f:parameter`. On top of this it adds a couple of tricks
for convenience.

## Installation

To install, simply do `composer req namelesscoder/fluid-parameters`. That's it - no configuration is necessary. If you
use the package in a TYPO3 CMS installation this automatically adds `f:parameter`, `f:description` and `f:parameter.mode`
as ViewHelpers. If you are not within a TYPO3 CMS installation you nave to manually add the namespace
`{namespace f=\NamelessCoder\FluidParameters\ViewHelpers}` in templates where you want to use the feature.

## Feature set:

* Parameters can be declared for any Fluid asset; Templates, Layouts, Partials or Sections (within any of those three
  types of Fluid assets).
* Parameters can be required, in which case an error is thrown if the required variable is not assigned as template
  variable or passed to the section/partial with `arguments` on `f:render`.
* Parameters can be optional, in which case if they are not passed, they can be given a default value (precisely like
  a ViewHelper argument) and become assigned when rendering the asset.
* Parameters can be cast to the appropriate type like `string` or `integer`.
* Parameters can be given a list of allowed values. If you declare a parameter which allows for example values "foo"
  or "bar" but the variable value is "baz" when you render the asset, an error can be thrown. 
* Specifying a parameter with type `array` allows you to pass a CSV value as variable, to make it easier to pass array
  values as part of `arguments` on `f:render` without complex Fluid syntax - or directly consume values from records
  without exploding CSV values stored in the record's columns.
* References can be rendered, describing parameters for a given Fluid asset or section.
* Templates or sections can be provided with a description that can be rendered as part of the parameter reference.

Together, these features allow a developer who writes Fluid template files to ensure that necessary variables are
present when the template file is rendered and that optional variables can have a default value. And they allow an
integrator who renders the templates to be informed if a necessary variable is not present or does not have a compatible
data type - without having detailed knowledge of every template file's content.

## How to use:

**No configuration is required** - simply use the `f:parameter` ViewHelper in your Fluid template and the rest happens
automatically. The `f:parameter` ViewHelper can be used in any Layout, Template or Partial, and within sections defined
in any of those types of files. Declared parameters are **specific to the file or section** - as soon as you render
another file or section, only the parameters of that other file or section will be considered.

#### Adding a required parameter

```xml
<f:parameter name="title" type="string" required="1" />
<h3>{title}</h3>
```

This throws an error if `{title}` is not assigned as template variable when rendering the template.

#### Optional parameter

```xml
<f:parameter name="title" type="string" default="Default title" />
<h3>{title}</h3>
```

This does not throw an error if `{title}` is not assigned. Instead, it automatically assigns the variable with a value
of `Default title`.

#### Specific required value

```xml
<f:parameter name="color" type="string" oneOf="red,green,blue" default="red" />
<span class="color-{color]">Some text that's either red, green or blue</span>
```

This causes an error to be thrown if assigning variable `{color}` is not one of the exact values `red`, `green` or
`blue` - and selects `red` as the default value.

#### Undeclared variables

```xml
<f:parameter.mode>strict</f:parameter.mode>
<f:parameter name="title" type="string" required="1" />
```

Sets the parameter handling mode to `strict` which means that if this template is rendered with an undeclared variable,
an error is thrown:

```xml
<f:render partial="StrictRequirementsPartial" arguments="{title: 'My title', unknownvariable: 'Some value'}" />
```

Results in an error:

```xml
Unxpected (undefined) template variable(s) encountered: unknownvariable
```

By default, **undeclared variables are allowed**. By setting the mode to `strict` this behavior is changed.

**Note that `f:parameter.mode` must be used BEFORE any occurrences of `f:parameter` to have an effect.**

#### Usage within sections

```xml
<f:section name="MySection">
  <f:parameter.mode>strict</f:parameter.mode>
  <f:parameter name="title" type="string" required="1" />
  <f:parameter name="text" type="string" default="Default text" />
  
  <h3>{title}</h3>
  <p>{text}</p>
</f:section>
```

Has the exact same effect as declaring the parameter on a template, partial or layout - but only applies when rendering
the section. If the section is rendered with an undeclared variable assigned, an error is thrown (due to mode=`strict`).
If `{text}` is not assigned when rendering the section, it is automatically assigned with a value of `Default text`.

#### Describing a template

```xml
<f:description>
  This is a description of the template file.
  
  You must always assign the "title" variable when rendering this template. 
</f:description>
<f:parameter name="title" type="string" required="1" />
<h3>{title}</h3>
```

Output:

```xml
<h3>Value of title variable</h3>
```

Essentially, any content you use within `f:description` is not rendered as output when the template/section is rendered.
**Note: do not use any Fluid code within the description block!** If you do, the description text will be truncated and
only includes any text leading up to the first Fluid code. Invalid Fluid code within this block will still cause a
template parsing error!

## Extracting "Reflections" of parameters/descriptions

The package contains an API to extract information about which parameters apply to a given template, along with the
contents of the `f:description` block within the template. This can be used to build your own style guide or reference.

Consider the following example template file located at `/path/to/my/template.html`:

```xml
<f:description>
  Text from the description block in my template
</f:description>
<f:parameter.mode>strict</f:parameter.mode>
<f:parameter name="title" type="string" description="A text to become the title" />
<f:parameter name="text" type="string" description="A text to become the paragraph body" required="1" />
<f:parameter name="level" type="integer" description="Optional header level 1-9" oneOf="1,2,3,4,5,6,7,8,9" />

<f:if condition="{title]">
    <h{level}>{title}</h{level}>
    <p>{text}</p>
</f:if>

<f:section name="MySection">
    <f:description>
        Text from the description block in section "MySection" in my template
    </f:description>
    <f:parameter name="content" type="string" description="Content string to render in the section" required="1" />
    Additional content: {content}
</f:section>
```

Using the extraction API is fairly straight-forward:

```php
$templateFile = '/path/to/my/template.html';
$renderingContext = new \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext();
$extractor = new \NamelessCoder\FluidParameters\Reflection\ParameterExtractor($renderingContext);
$reflection = $extractor->parseTemplate($templateFile);
```

The `$reflection` variable now contains an instance of [TemplateReflection](https://github.com/NamelessCoder/fluid-parameters/blob/master/Classes/Reflection/TemplateReflection.php)
which holds properties describing the template. You can use this reflection instance to extract all metadata:

```php
<?php
function renderParameterDefinitions(array $parameterDefinitions): array
{
    $output = [];
    foreach ($parameterDefinitions as $name => $definition) {
        $output[] = '  Parameter: ' . $name;
        $output[] = '  Description: ' . $definition->getDescription();
        $output[] = '  Required: ' . ($definition->isRequired() ? 'Yes' : 'No');
        $output[] = '  Type: ' . $definition->getType();
        if (!$definition->isRequired()) {
            $output[] = '  Default: ' . var_export($definition->getDefaultValue(), true);        
        }
        if (!empty($definition->getOneOf())) {
            $output[] = '  Allowed values: ' . implode(', ', $definition->getOneOf());        
        }
        $output[] = PHP_EOL;
    }
    return $output;
}

$description = $reflection->getDescription();
$parameterMode = $reflection->getParameterMode();

$output = [
    'Template: ' . $templateFile,
    'Parameter mode: ' . $parameterMode,
    'Description:',
    '  ' . $description,
    'Parameters:',
    PHP_EOL,
];

$output = array_merge($output, renderParameterDefinitions($reflection->getParameterDefinitions()));
```

It is further possible to extract the same type of reference for each of the `f:section` nodes within the template file.
Each of the values returned from `$reflection->getSections()` is an instance of
[SectionReflection](https://github.com/NamelessCoder/fluid-parameters/blob/master/Classes/Reflection/SectionReflection.php):

```php
// You can extract a single known section and chain getters:
$mySectionDescription = $reflection->fetchSection('MySection')->getDescription();

// Or iterate over all sections within the template:
foreach ($reflection->getSections() as $sectionName => $sectionReflection) {
    $output[] = 'Template: ' . $templateFile . ', section: ' . $sectionName;
    $output[] = 'Parameter mode: ' . $parameterMode;
    $output[] = 'Description:';
    $output[] = $description;
    $output[] = 'Parameters:';
    $output = array_merge($output, renderParaterDefinitions($sectionReflection->getParameterDefinitions()));
    $output[] = PHP_EOL;
}

echo implode(PHP_EOL, $output);
```

Together this would produce an output like this:

```text
Template: /path/to/my/template.html
Parameter mode: strict
Description:
  Text from the description block in my template
Parameters:
  Parameter: title
  Description: A text to become the title
  Required: No
  Type: string
  Default: null
  
  Parameter: text
  Description: A text to become the paragraph body
  Required: Yes
  Type: string
  
  Parameter: level
  Description: Optional header level 1-9
  Required: No
  Type: integer
  Default: 1
  Allowed values: 1, 2, 3, 4, 5, 6, 7, 8, 9 

Template: /path/to/my/template.html, section: MySection
Parameter mode: loose
Description:
  Text from the description block in section "MySection" in my template
Parameters:
  Parameter: content
  Description: Content string to render in the section
  Required: Yes
  Type: string
```

The `$reflection` instance can of course also be assigned to a Fluid template to render the reference. Use Fluid's
standard iteration and variable access to achieve the exact output you want, e.g. `{reflection.description}`,
`<f:for each="{reflection.parameterDefinitions}" as="definition">...</f:for>` and so on - just like you would normally
iterate arrays and output variables.

## Differences between this extension and `fluid_components`

You may or may not already be aware of an extension ["Fluid Components"](https://github.com/sitegeist/fluid-components)
created by [sitegeist](https://sitegeist.de/) - these two extensions are somewhat similar in that they allow Fluid
templates to define required variables, but they are very different in their implementation philosophy.

These two extensions **can coexist peacefully** and can be mixed to some extent (sections rendered by Fluid Components
can be fitted with parameters declared by Fluid Parameters); however, parameters declared with one are not known to
the other. It is therefore a fully viable use case to use Fluid Components for some contexts and Fluid Parameters for
others, in the same project.

The following table shows the differences:

|                                                   | Fluid Parameters | Fluid Components    
|---------------------------------------------------|------------------|---------------------
| Supports declaring parameters for templates       | ✔                | ✔
| Supports default values for parameters/variables  | ✔                | ✔
| Supports validation of parameters' data type      | ✔                | ✔
| Supports reference / parameter documentation      | ✔                | ✔
| Zero-config                                       | ✔                | 
| High performance / light-weight                   | ✔                | 
| Works for any template                            | ✔                | 
| Works for individual sections                     | ✔                | 
| Can be rendered as native FLUIDTEMPLATE TS object | ✔                | 
| Compatible with Flux templates                    | ✔                | 
| Works for Fluid outside of TYPO3 CMS *            | ✔                |
| Requires special syntax to define rendering       |                  | ✔
| Supports custom data type casting                 |                  | ✔
| Works by overriding internal Fluid classes        |                  | ✔
| Makes template files emulate a ViewHelper         |                  | ✔
| Requires custom Fluid namespaces                  |                  | ✔
| Allows XSD integration for auto-completion        |                  | ✔
| Has a "living styleguide" implementation          |                  | ✔

(* If you manually register the namespace with `{namespace f=\NamelessCoder\FluidParameters\ViewHelpers}`)

In essence: use `Fluid Parameters` if you...

* Only need the ability to declare optional/required parameters and provide default values.
* Want the smallest possible integration that works without any overrides of internal Fluid classes.
* Do not want to write additional configuration.
* Don't care about XSD integration for auto-completion.
* Still want to use your templates as controller action templates or template file for a FLUIDTEMPLATE TS object.
* Want to use your template files as content element or page templates with required variables.
* Want to use the feature in Flux-based templates
* Want to use the feature in non-TYPO3-CMS projects that use Fluid

And use `Fluid Components` if you...

* Don't mind having to write additional configuration.
* Don't mind that internal Fluid classes are overridden with less performant replacements.
* Don't care that your affected templates cannot be rendered normally anymore.
* Need/want XSD integration for auto-completion purposes.
* Need/want a "living styleguide" (without needing to write the implementation yourself).

## Credits

This work was kindly sponsored by [Busy Noggin](http://busynoggin.com/).