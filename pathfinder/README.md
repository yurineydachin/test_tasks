Fast test and simple of use you may see in index.php file. There are format of input set of boarding cards and example how to use one of path finder alhoritm.
To run the example do in console. As result in console you will see an optimize route to final destination.
php index.php

Input format of boarding cards:
* type: <string> - type of transportation
* to:   <string> - name of destination/arrivals
* from: <string> - name of departure point
* text: <string> - any other info about transportations: gate, seat, luggage, etc...

How to run tests
Run command in console:
phpunit test.php

Detail description
You have to provide boarding cards set as array (from DB or CSV file - does not matter) in format as in tests or index.php. Then process all boarding cards and use PointsRegistry object for configure PathFinder object. And finaly run searching route between start (A) and finish (B) points.
There are some possible results:
* Route not found - RouteNotFoundExeption
* One or both points not found in PointsRegistry - UnknownPointExeption
* If start equals finish - EqualPointExeption
* Success route war returned

To show short info about route just print route object as string.
To show full info about route do route->print(); You will see something like this
> 1. Take Flight SU141 to Shanghai. Gate 31, Seat 43A, any luggage free)
> 2. Take Airport bus 332 to Hangzhou. Seat 18

How to extend code:
* if you would like to implement different search method, just create new class like PathFinder witch uses other classes.
* If you would like to add some more detail info to boading card, at first you may write in "text" field, or add one more. But it is not affected search alhoritm if you do not change it.
* You may calculate all routes from any point to any point, so you will always use saved route. But it need some calculation time and memory.
* 2 hours not enough to implement all ideas (
