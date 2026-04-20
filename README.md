# Interactive Strategies Drupal coding standards

This package defines customized Code Sniffer rules for Drupal projects. It is primarily based on [drupal/coder](https://www.drupal.org/project/coder), with some sniffs excluded and others downgraded to warnings. Some additional sniffs not provided in drupal/coder have also been added.

## Installing the ruleset

Use Composer to install the ruleset and its dependencies as a package:

`composer require --dev interactivestrategies/drupal-cs`

## Making the IS rules the default for your project

Add a phpcs.xml.dist file in your project with content like this:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<ruleset name="an_is_project">
  <file>./dist/modules/custom</file>
  <file>./dist/profiles/custom</file>
  <file>./dist/themes/custom</file>

  <rule ref="ISDrupal"/>
</ruleset>
```

The <file> tag(s) define what paths the rules should be applied to/within.

## Upgrading to 3.x

The interactivestrategies/drupal-cs 3.x branch upgrades from drupal/coder 8.x to 9.x, and PHPCS 3.x to 4.x. Aside from adjustments required by the drupal/coder upgrade, no major changes were made in the ISDrupal ruleset itself.

For projects with a simple PHPCS configuration that simply uses the ISDrupal ruleset, unmodified, you should not have to make any configuration changes.

For projects with ruleset customizations, you may need to double check customized rules against changes in drupal/coder and this package.
