<?php 
// Uses the Slim PHP REST Framework: http://slimframework.com/
require 'Slim/Slim.php';
$app = new Slim();

// http://yehj.floccul.us/halma/api/genJsonSumFromParms
$app->get('/genJsonSumFromParms', 'genJsonSumFromParms');
// http://yehj.floccul.us/halma/api/genJsonMoveFromParms
$app->get('/genJsonMoveFromParms', 'genJsonMoveFromParms');
// http://yehj.floccul.us/halma/api/genJsonMoveFromPixelParms
$app->get('/genJsonMoveFromPixelParms/:pixelX/:pixelY', 'genJsonMoveFromPixelParms');

$app->run();

// Class to encapsulate X and Y coordinates
class Location {
    var $x, $y;

    function Location($x = 0, $y = 0) {
        $this->x = $x;
        $this->y = $y;
    }
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
    $location = new Location($request->get($x), $request->get($y));

    return $location;
}

/**
 * Compares two numbers $a and $b
 * @return if $a > $b, return 1; if $a < $b, return -1; if $a = $b, return 0
 */
function compare($a, $b) {
    return ($a == $b) ? 0 : (($a > $b) ? 1 : -1);
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
 * HW 4: Functional Programming Style
 */
function genJsonMoveFromPixelParms($pixelX, $pixelY) {
    $numRows = 9;
    $numCols = 9;
    $cellPxSize = 50;
    $destination = new Location(6, 2);

    $location = convertPxToLoc($pixelX, $pixelY, $numRows, $numCols, $cellPxSize);
    $location = moveToDestination($location, $destination);
    echo locationToJson($location);
}

/**
 * Convert the pixel coordinates to the cell location on the game board
 * @param $pixelX Pixel X coordinate of where the user clicked on the board
 * @param $pixelY Pixel Y coordinate of where the user clicked on the board
 * @param $numRows The number of rows in the game board
 * @param $numCols The number of columns in the game board
 * @param $cellPxSize The pixel width/height of a cell in the game board
 * @return Cell location. NULL if bad parameters.
 */
function convertPxToLoc($pixelX, $pixelY, $numRows, $numCols, $cellPxSize) {
    try {
        $x = ceil($pixelX / $cellPxSize);
        $y = ceil($pixelY / $cellPxSize);
        if (($x < 0 || $x > $numCols - 1) || ($y < 0 || $y > $numRows - 1)) {
            throw new Exception('Location out of bounds.');
        }
        return new Location($x, $y);
    } catch (Exception $e) {
        return NULL;
    }
}

/**
 * Move the game piece towards the destination.
 * @param $location X and Y coordinates of the piece's current location.
 * @param $destination X and Y coordinates of the destination location.
 * @return New cell location.
 */
function moveToDestination($location, $destination) {
    if (!$location || !$destination) {
        return NULL;
    }
    $xDiff = $destination->x - $location->x;
    $yDiff = $destination->y - $location->y;
    $moveX = compare($xDiff, 0);
    $moveY = compare($yDiff, 0);
    return new Location($location->x + $moveX, $location->y + $moveY);
}

/**
 * Convert the location to a JSON string.
 * @param $location X and Y coordinates of a location.
 * @return JSON string representation of the location.
 */
function locationToJson($location) {
    $x = $location ? $location->x : NULL;
    $y = $location ? $location->y : NULL;
    $jsonArray = array('x' => $x, 'y' => $y);
    return json_encode($jsonArray);
}

?>
