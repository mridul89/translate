Magento Translate Module
========================

This is a magento shell script to help magento module developers create their module's translation files.
It goes through a magento module files to search for strings marked for translation & then creates CSV files for them which you can package with the module.

Features include
----------------
* The strings in CSV are ordered alphabetically.
* Existing CSV is supported, & translation strings are merged.
* Already translated strings & not translated strings are grouped separately.
* It fully supports all of module's layout xml & design files too.
* It will warn you if it detects an invalid string marked for translation(eg. a variable is passed or nothing is passed).
* Fast & reliable, doesn't use regular expressions.

How to use
----------
Download the script & put it in your magento installation's shell directory
Go to your magento installation folder & type `php -f shell/translate.php -- -m <module_name>`
for example-
`php -f shell/translate.php -- -m Mage_Core`

Limitations
-----------
* While searching for template files that needs translation, it may also go through template files from other modules.
* It only goes through design files which are referenced directly in layout xml or code, or are in the common folders. This, though enough for most cases, may leave out some design files.
* The name of generated CSV file is always same as the module name.

TODO list
---------
* Make the name of generated CSV files the same as what is mentioned in module's `config.xml`
* Update the module's `config.xml` to reflect the availability of the newly generated translation file.
* Whatever you would like to suggest. We are looking for ways on how to improve this.
