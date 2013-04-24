Magento Translate Module
========================

This is a magento shell script to help magento module developers create their module's translation files.
It goes through a magento module files to search for strings marked for translation & then creates CSV files for them which you can package with the module.

Features include-
1) The strings in CSV are ordered alphabetically.
2) Existing CSV is supported, & translation strings are merged.
3) Already translated strings & not translated strings are grouped separately.
4) It fully supports all of module's layout xml & design files too.
5) It will warn you if it detects an invalid string marked for translation(eg. a variable is passed or nothing is passed).
6) Fast & reliable, doesn't use regular expressions.

How to use-
Download the script & put it in your magento installation's shell directory
Go to your magento installation folder & type `php -f shell/translate.php -- -m <module_name>`
for example-
`php -f shell/translate.php -- -m Mage_Core`

Limitations-
1) While searching for template files that needs translation, it may also go through template files from other modules.
2) It only goes through design files which are referenced directly in layout xml or code, will leave them if not.
3) The name of generated CSV file is always same as the module name.

TODO list-
1) Make the name of generated CSV files the same as what is mentioned in module's `config.xml`
2) Update the module's `config.xml` to reflect the availability of the newly generated translation file.
3) Whatever you would like to suggest. We are looking for ways on how to improve this.
