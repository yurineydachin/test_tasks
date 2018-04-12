Run

Set all params before run CLI php script:
php solution.php <url> <start_time> <end_time> <places>

e.g.
php solution.php http://www.mocky.io/v2/58ff37f2110000070cf5ff16 2017-11-20T09:30 2017-11-23T19:30 3

By default const USE_CACHE=true - it means CLI will try to save endpoint response on disk and use it in next run.



No frameworks or foreign components.



Nest steps:

* add some tests for main function filterProductList with different params to check all cases
* if json stream is very big and need a lot of memory - use stream reader or generator or some other tricks
* add php docs and comments in code
* check bentchmarks and find more faster solution
* maybe for future it is better to convert all rows to object Product and use here OOP way)
