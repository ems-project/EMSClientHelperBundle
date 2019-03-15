ClientHelperBundle
=============

Coding standards
----------------
PHP Code Sniffer is available via composer, the standard used is defined in phpcs.xml.diff:
````bash
composer phpcs
````

If your code is not compliant, you could try fixing it automatically:
````bash
composer phpcbf
````

PHPStan is run at level 4, you can check for errors locally using:
`````bash
composer phpstan
`````

Documentation
-------------

[Twig documentation](../master/Resources/doc/twig.md)

### CommonBundle

The ClientHelperBundle has a strong dependency on CommonBundle.
 
[Twig documentation for CommonBundle](https://github.comhttps://github.com/ems-project/EMSClientHelperBundle/blob/master/Resources/doc/twig.md)
