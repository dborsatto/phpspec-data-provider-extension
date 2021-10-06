# PhpSpec data provider extension

This extension allows you to create data providers for examples in specs. It is a fork of [madisoft/phpspec-data-provider-extension](https://github.com/madisoft/phpspec-data-provider-extension/tree/master), which is the final fork from a series started from [coduo/phpspec-data-provider-extension](https://github.com/coduo/phpspec-data-provider-extension). 

## Installation

```bash
composer require dborsatto/phpspec-data-provider-extension
```

## Usage

Enable extension in your `phpspec.yml` file:

```
extensions:
  DBorsatto\PhpSpec\DataProvider\DataProviderExtension: ~
```

Write a spec:

```php
<?php

declare(strict_types=1);

namespace spec\DBorsatto\ToString;

use PhpSpec\ObjectBehavior;

class StringLibrarySpec extends ObjectBehavior
{
    /**
     * @dataProvider positiveConversionExamples
     */
    public function it_convert_input_value_into_string($inputValue, $expectedValue): void
    {
        $this->beConstructedWith($inputValue);
        $this->__toString()
            ->shouldReturn($expectedValue);
    }

    public function positiveConversionExamples(): array
    {
        return [
            [1, '1'],
            [1.1, '1.1'],
            [new \DateTime, '\DateTime'],
            [['foo', 'bar'], 'Array(2)']
        ];
    }
}
```

Write the class for your spec:

```php
<?php

declare(strict_types=1);

namespace DBorsatto\ToString;

class StringLibrary
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        $type = gettype($this->value);
        switch ($type) {
            case 'array':
                return sprintf('Array(%d)', count($this->value));
            case 'object':
                return sprintf("\\%s", get_class($this->value));
            default:
                return (string) $this->value;
        }
    }
}
```

Run phpspec

```
$ vendor/bin/phpspec run -f pretty
```

You should get following output:

```
DBorsatto\ToString\String

  12  ✔ convert input value into string
  12  ✔ 1) it convert input value into string
  12  ✔ 2) it convert input value into string
  12  ✔ 3) it convert input value into string
  12  ✔ 4) it convert input value into string


1 specs
5 examples (5 passed)
13ms
```
