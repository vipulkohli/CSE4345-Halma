<?php 
// Uses the Slim PHP REST Framework: http://slimframework.com/
require 'Slim/Slim.php';
$app = new Slim();

$app->get('/genJsonSumFromParms', 'genJsonSumFromParms');
$app->get('/genJsonMoveFromParms', 'genJsonMoveFromParms');
$app->run();

class Location {
    var $x = 0, $y = 0;
}

/**
 * @param $x HTTP GET parameter for X coordinate (string)
 * @param $y HTTP GET parameter for Y coordinate (string)
 * @return Location
 */
function getLocation($x, $y) {
    // Get the Slim request object
    $request = Slim::getInstance()->request();

    // Create a Location object and store X and Y in it
    $location = new Location();
    $location->x = $request->get($x);
    $location->y = $request->get($y);

    return $location;
}

/**
 * HW 3 Part A
 */
function genJsonSumFromParms() {
    $p = getLocation('px', 'py');
    $d = getLocation('dx', 'dy');
    $b = getLocation('bx', 'by');

    $sumx = $p->x + $d->x + $b->x;
    $sumy = $p->y + $d->y + $b->y;

    $answer = array('sumx' => $sumx, 'sumy' => $sumy);
    echo json_encode($answer);
}

/**
 * HW 3 Part B
 */
function genJsonMoveFromParms() {
    // (px, py) = coordinates of Halma piece to move
    $p = getLocation('px', 'py');
    // (dx, dy) = desired destination of the Halma piece
    $d = getLocation('dx', 'dy');
    // (bx, by) = location of another piece on the board (not to be moved)
    $b = getLocation('bx', 'by');

    // do stuff
}

?>
