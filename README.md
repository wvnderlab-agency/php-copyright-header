# PHP Copyright-Header

```php
/**
 *  ################ 
 *  ##            ##     Copyright (c) 2025 Wvnderlab Agency
 *  ##                   
 *  ##   ##  ###  ##     ✉️ moin@wvnderlab.com
 *  ##    #### ####      🔗 https://wvnderlab.com
 *  #####  ##  ###   
 */
```

[![Latest Stable Version](https://poser.pugx.org/wvnderlab-agency/copyright-header/v/stable)](https://packagist.org/packages/wvnderlab-agency/copyright-header)
[![License](https://poser.pugx.org/wvnderlab-agency/copyright-header/license)](https://packagist.org/packages/wvnderlab-agency/copyright-header)

**Table of Contents**

- [Usage](#usage)
    - [Installation](#installation)
    - [With PHP-CS-Fixer](#with-php-cs-fixer)
- [Development](#development)

## Usage

### Installation

#### via Packagist

You can install the package via Composer by running the following command:

```shell
composer require --dev wvnderlab-agency/php-copyright-header
```

#### manually

If you prefer to install the package manually, you can add the following lines to your `composer.json` file:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/wvnderlab-agency/php-copyright-header"
    }
  ],
  "require-dev": {
    "wvnderlab-agency/php-copyright-header": "^0.1.0"
  }
}
```

If you want to update the package to the latest version, run the following command:

```shell
composer update wvnderlab-agency/php-copyright-header
```

### Configuration

#### with PHP-CS-Fixer

You can configure the package by creating a `php-cs-fixer.php` file in the root directory of your project. The following
example shows how to set up the package with PHP CS Fixer:

```php
<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use WvnderlabAgency\CopyrightHeader\CopyrightHeaderFixer;

if (!class_exists(CopyrightHeaderFixer::class)) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->exclude(['vendor']);

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'WvnderlabAgency/copyright_header' => true,
    ])
    ->setFinder($finder)
    ->registerCustomFixers([
        'WvnderlabAgency/copyright_header' => new CopyrightHeaderFixer()
    ]);
```

Add a script to your `composer.json` file to run the PHP CS Fixer with the configuration file:

```json
{
  "scripts": {
    "format": "./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php"
  }
}
```

Now you can run the following command to apply the code styles defined in the project:

```shell
composer format
```

## Development

### Project Structure

```
├── src/        # Package source code
└── tests/      # Unit tests
```

### Install Dependencies

Run the following command to install the required dependencies for the project:

```shell
composer install
```

### Apply Code-Styles

Run the following command to apply the code styles defined in the project:

```shell
composer format
```

### Analyze Code

Run the following command to analyze the code for potential issues:

```shell
composer analyze
```

### Run Unit Tests

Run the following command to execute the unit tests for the project:

```shell
composer test
```
