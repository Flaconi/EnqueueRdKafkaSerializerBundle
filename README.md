# Flaconi Enqueue RdKafka Serializer Bundle

[![Latest version](https://img.shields.io/packagist/v/flaconi/coding-standard.svg?style=flat-square&colorB=007EC6)](https://packagist.org/packages/flaconi/coding-standard)
[![Downloads](https://img.shields.io/packagist/dt/flaconi/coding-standard.svg?style=flat-square&colorB=007EC6)](https://packagist.org/packages/flaconi/coding-standard)
[![Travis build status](https://img.shields.io/travis/Flaconi/phpcs-coding-standard/master.svg?label=travis&style=flat-square)](https://travis-ci.org/Flaconi/phpcs-coding-standard)
[![Code coverage](https://img.shields.io/coveralls/Flaconi/phpcs-coding-standard/master.svg?style=flat-square)](https://coveralls.io/github/Flaconi/phpcs-coding-standard?branch=master)
![PHPStan](https://img.shields.io/badge/style-level%207-brightgreen.svg?style=flat-square&label=phpstan)

Flaconi Coding Standard for [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) provides sniffs that fall for now in one category:

* Test - improving the code style of phpunit test cases

## Table of contents

1. [Sniffs included in this standard](#sniffs-included-in-this-standard)
    - [Test - improving the code style of phpunit test cases](#test---improving-the-code-style-of-phpunit-test-cases)
2. [Installation](#installation)
3. [How to run the sniffs](#how-to-run-the-sniffs)
    - [Choose which sniffs to run](#choose-which-sniffs-to-run)
    - [Using all sniffs from the standard](#using-all-sniffs-from-the-standard)
4. [Fixing errors automatically](#fixing-errors-automatically)
5. [Suppressing sniffs locally](#suppressing-sniffs-locally)
6. [Contributing](#contributing)

## Sniffs included in this standard

🔧 = [Automatic errors fixing](#fixing-errors-automatically)


### Test - improving the code style of phpunit test cases

#### FlaconiCodingStandard.Test.UseMethodPrefixInTestcase 🔧

* Checks for `@test` and force to use `test` Prefix


#### FlaconiCodingStandard.Test.UseStaticCallsForAssertInTestcase 🔧

Reports usage of non static assert method class

## Installation

The recommended way to install Slevomat Coding Standard is [through Composer](http://getcomposer.org).

```JSON
{
	"require-dev": {
		"flaconi/coding-standard": "^1.0"
	}
}
```

## How to run the sniffs

You can choose one of two ways to run only selected sniffs from the standard on your codebase:

### Choose which sniffs to run

Mention Slevomat Coding Standard in your project's `ruleset.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="AcmeProject">
	<rule ref="vendor/flaconi/coding-standard/src/ruleset.xml"><!-- relative path to your ruleset.xml -->
		<!-- sniffs to exclude -->
	</rule>
</ruleset>
```

When running `phpcs` [on the command line](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Usage), use the `--sniffs` option to list all the sniffs you want to use separated by a comma:

```
vendor/bin/phpcs --standard=ruleset.xml \
--sniffs=FlaconiCodingStandard.Test.UseStaticCallsForAssertInTestcase,FlaconiCodingStandard.Test.UseMethodPrefixInTestcase \
--extensions=php --encoding=utf-8 --tab-width=4 -sp src tests
```

Or write your own ruleset.xml by referencing the selected sniffs. This is a sample ruleset.xml:

```xml
<?xml version="1.0"?>
<ruleset name="AcmeProject">
	<config name="installed_paths" value="../../flaconi/coding-standard"/><!-- relative path from PHPCS source location -->
	<rule ref="FlaconiCodingStandard.Test.UseMethodPrefixInTestcase"/>
	<!-- other sniffs to include -->
</ruleset>
```

Then run the `phpcs` executable the usual way:

```
vendor/bin/phpcs --standard=ruleset.xml --extensions=php --tab-width=4 -sp src tests
```

## Fixing errors automatically

Sniffs in this standard marked by the 🔧 symbol support [automatic fixing of coding standard violations](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Fixing-Errors-Automatically). To fix your code automatically, run phpcbf instead of phpcs:

```
vendor/bin/phpcbf --standard=ruleset.xml --extensions=php --tab-width=4 -sp src tests
```

Always remember to back up your code before performing automatic fixes and check the results with your own eyes as the automatic fixer can sometimes produce unwanted results.

## Contributing

To make this repository work on your machine, clone it and run these two commands in the root directory of the repository:

```
composer install
bin/phing
```

After writing some code and editing or adding unit tests, run phing again to check that everything is OK:

```
bin/phing
```

We are always looking forward for your bugreports, feature requests and pull requests. Thank you.
