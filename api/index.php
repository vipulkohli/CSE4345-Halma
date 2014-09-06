<?php 
// Uses the Slim PHP REST Framework: http://slimframework.com/
require 'Slim/Slim.php';
$app = new Slim();

// http://yehj.floccul.us/halma/api/genJsonSumFromParms
$app->get('/genJsonSumFromParms', 'genJsonSumFromParms');
// http://yehj.floccul.us/halma/api/genJsonMoveFromParms
$app->get('/genJsonMoveFromParms', 'genJsonMoveFromParms');

$app->run();

// Class to encapsulate X and Y coordinates
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
    // (px, py) = coordinates of Halma piece to move
    $p = getLocation('px', 'py');
    // (dx, dy) = desired destination of the Halma piece
    $d = getLocation('dx', 'dy');
    // (bx, by) = location of another piece on the board (not to be moved)
    $b = getLocation('bx', 'by');

    // Calculate sums of x's and y's
    $sumx = $p->x + $d->x + $b->x;
    $sumy = $p->y + $d->y + $b->y;

    // Show answer as a JSON string
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

    // Calculate the difference between the X's and Y's of the
    // destination and piece coordinates, and use that to determine
    // the new X and Y coordinates.
    $xDiff = $d->x - $p->x;
    $yDiff = $d->y - $p->y;
    $moveX = compare($xDiff, 0);
    $moveY = compare($yDiff, 0);
    $p->x += $moveX;
    $p->y += $moveY;

    // Check if the new X and Y are on top of the blocking piece.
    // If so, jump over it.
    if ($p == $b) {
        $p->x += $moveX;
        $p->y += $moveY;
    }

    // Show answer as a JSON string
    $answer = array('x' => $p->x, 'y' => $p->y);
    echo json_encode($answer);
}

/**
 * Compares two numbers $a and $b
 * @return if $a > $b, return 1; if $a < $b, return -1; if $a = $b, return 0
 */
function compare($a, $b) {
    return ($a == $b) ? 0 : (($a > $b) ? 1 : -1);
}

?>
