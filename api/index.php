<?php 
// Uses the Slim PHP REST Framework: http://slimframework.com/
require 'Slim/Slim.php';
$app = new Slim();

// http://yehj.floccul.us/halma/api/genJsonSumFromParms
$app->get('/genJsonSumFromParms', 'genJsonSumFromParms');
// http://yehj.floccul.us/halma/api/genJsonMoveFromParms
$app->get('/genJsonMoveFromParms', 'genJsonMoveFromParms');
// http://yehj.floccul.us/halma/api/genJsonMoveFromPixelParms/{pixelX}/{pixelY}
$app->get('/genJsonMoveFromPixelParms/:pixelX/:pixelY', 'genJsonMoveFromPixelParms');

$app->run();

// Class to encapsulate X and Y coordinates
class Location {
    private $x, $y;

    function Location($x = 0, $y = 0) {
        $this->x = $x;
        $this->y = $y;
    }

    function getX() {
        return $this->x;
    }

    function getY() {
        return $this->y;
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
    $sumx = $p->getX() + $d->getX() + $b->getX();
    $sumy = $p->getY() + $d->getY() + $b->getY();

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
    $xDiff = $d->getX() - $p->getX();
    $yDiff = $d->getY() - $p->getY();
    $moveX = compare($xDiff, 0);
    $moveY = compare($yDiff, 0);
    $newP = new Location($p->getX() + $moveX, $p->getY() + $moveY);

    // Check if the new X and Y are on top of the blocking piece.
    // If so, jump over it.
    if ($newP == $b) {
        $newP = new Location($newP->getX() + $moveX, $newP->getY() + $moveY);
    }

    // Show answer as a JSON string
    $answer = array('x' => $newP->getX(), 'y' => $newP->getY());
    echo json_encode($answer);
}

/**
 * HW 4 & 5: Functional Programming Style
 */
function genJsonMoveFromPixelParms($pixelX, $pixelY) {
    $numRows = 9;
    $numCols = 9;
    $cellPxSize = 50;
    $destination = new Location(6, 2);
    $blocker = new Location(4, 4);

    // define partial functions and pass in 'global' variables
    $convertPxToLoc = partial_function('convertPxToLoc', $numRows, $numCols, $cellPxSize);
    $moveToDestination = partial_function('moveToDestination', $destination, $blocker);

    echo locationToJson($moveToDestination($convertPxToLoc($pixelX, $pixelY)));
}

/**
 * Convert the pixel coordinates to the cell location on the game board
 * @param $numRows The number of rows in the game board
 * @param $numCols The number of columns in the game board
 * @param $cellPxSize The pixel width/height of a cell in the game board
 * @param $pixelX Pixel X coordinate of where the user clicked on the board
 * @param $pixelY Pixel Y coordinate of where the user clicked on the board
 * @return Cell location. NULL if bad parameters.
 */
function convertPxToLoc($numRows, $numCols, $cellPxSize, $pixelX, $pixelY) {
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
 * @param $destination X and Y coordinates of the destination location.
 * @param $destination X and Y coordinates of the destination location.
 * @param $location X and Y coordinates of the piece's current location.
 * @return New cell location.
 */
function moveToDestination($destination, $blocker, $location) {
    if (!$location || !$destination || !$blocker) {
        return NULL;
    }
    $xDiff = $destination->getX() - $location->getX();
    $yDiff = $destination->getY() - $location->getY();
    $moveX = compare($xDiff, 0);
    $moveY = compare($yDiff, 0);
    $newLocation = new Location($location->getX() + $moveX, 
                                $location->getY() + $moveY);
    if ($newLocation == $blocker) {
        $newLocation = new Location($newLocation->getX() + $moveX, 
                                    $newLocation->getY() + $moveY);
    }
    return $newLocation;
}

/**
 * Convert the location to a JSON string.
 * @param $location X and Y coordinates of a location.
 * @return JSON string representation of the location.
 */
function locationToJson($location) {
    $x = $location ? $location->getX() : NULL;
    $y = $location ? $location->getY() : NULL;
    $jsonArray = array('x' => $x, 'y' => $y);
    return json_encode($jsonArray);
}

/**
 * https://gist.github.com/jdp/2201912#file-partial_function-php
 */
function partial_function() {
    $applied_args = func_get_args();
    return function() use($applied_args) {
        return call_user_func_array('call_user_func', array_merge($applied_args, func_get_args()));
    };
}

?>
