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

$app->post('/getMove', 'getMove');

$app->run();

// Class to encapsulate X and Y coordinates
class Cell {
    public $x, $y, $used;

    function Cell($x = 0, $y = 0, $used = false) {
        $this->x = $x;
        $this->y = $y;
        $this->used = $used;
    }
}

class Path {
    public $cells = array();

    function Path($start) {
        $this->addCell($start);
    }

    function getLastCell() {
        return end((array_values($this->cells)));
    }

    function getFirstCell() {
        return $this->cells[0];
    }

    function getPreviousCell() {
        if (count($this->cells) < 2) {
            return NULL;
        } else {
            return $this->cells[count($this->cells)-2];
        }
    }

    function addCell($cell) {
        array_push($this->cells, $cell);
    }

    function calcPathDistance() {
        return distanceBetweenCells($this->getFirstCell(), $this->getLastCell());
    }

    function calcPathDirection() {
        $start = $this->getFirstCell();
        $end = $this->getLastCell();
        $dy = $end->y - $start->y;
        $dx = $end->x - $start->x;
        if ($dx == 0) {
            if ($dy >= 0) {
                return 90;
            } else {
                return -90;
            }
        }
        return atan($dy/$dx);
    }
}

/**
 * @param $x HTTP GET parameter for X coordinate (string)
 * @param $y HTTP GET parameter for Y coordinate (string)
 * @return Cell
 */
function getCell($x, $y) {
    // Get the Slim request object
    $request = Slim::getInstance()->request();

    // Create a Cell object and store X and Y in it
    $cell = new Cell($request->get($x), $request->get($y));

    return $cell;
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
    $p = getCell('px', 'py');
    // (dx, dy) = desired destination of the Halma piece
    $d = getCell('dx', 'dy');
    // (bx, by) = cell of another piece on the board (not to be moved)
    $b = getCell('bx', 'by');

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
    $p = getCell('px', 'py');
    // (dx, dy) = desired destination of the Halma piece
    $d = getCell('dx', 'dy');
    // (bx, by) = cell of another piece on the board (not to be moved)
    $b = getCell('bx', 'by');

    // Calculate the difference between the X's and Y's of the
    // destination and piece coordinates, and use that to determine
    // the new X and Y coordinates.
    $xDiff = $d->x - $p->x;
    $yDiff = $d->y - $p->y;
    $moveX = compare($xDiff, 0);
    $moveY = compare($yDiff, 0);
    $newP = new Cell($p->x + $moveX, $p->y + $moveY);

    // Check if the new X and Y are on top of the blocking piece.
    // If so, jump over it.
    if ($newP == $b) {
        $newP = new Cell($newP->x + $moveX, $newP->y + $moveY);
    }

    // Show answer as a JSON string
    $answer = array('x' => $newP->x, 'y' => $newP->y);
    echo json_encode($answer);
}

/**
 * HW 4 & 5: Functional Programming Style
 */
function genJsonMoveFromPixelParms($pixelX, $pixelY) {
    $numRows = 9;
    $numCols = 9;
    $cellPxSize = 50;
    $destination = new Cell(6, 2);
    $blocker = new Cell(4, 4);

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
        return new Cell($x, $y);
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
    $xDiff = $destination->x - $location->x;
    $yDiff = $destination->y - $location->y;
    $moveX = compare($xDiff, 0);
    $moveY = compare($yDiff, 0);
    $newLocation = new Cell($location->x + $moveX, 
                                $location->y + $moveY);
    if ($newLocation == $blocker) {
        $newLocation = new Cell($newLocation->x + $moveX, 
                                    $newLocation->y + $moveY);
    }
    return $newLocation;
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

/**
 * https://gist.github.com/jdp/2201912#file-partial_function-php
 */
function partial_function() {
    $applied_args = func_get_args();
    return function() use($applied_args) {
        return call_user_func_array('call_user_func', array_merge($applied_args, func_get_args()));
    };
}

/**
 * HW 10: Halma AI v1.1
 */
function getMove() {
    // For now, hard code board settings
    $boardSize = 9;

    $request = Slim::getInstance()->request();

    $board = json_decode($request->post("board"));
    $upperLeftCell = json_decode($request->post("upperLeftCell"));
    $lowerRightCell = json_decode($request->post("lowerRightCell"));

    $pieces = decodePieces($board);
    $target = decodeDestination($upperLeftCell, $lowerRightCell);

    // Pick a destination cell
    for ($i = 0; $i < count($target); $i++) {
        $destination = getTopRightDestination($target);
        foreach ($pieces as &$piece) {
            if (areCellsEqual($destination, $piece)) {
                $piece->used = false;
                $destination->used = true;
            }
        }
        if (!$destination->used) {
            break;
        }
    }

    $finishedMovePaths = array();

    foreach ($pieces as &$piece) {
        if ($piece->used) {
            $unfinishedMovePaths = array();
            $initialPath = new Path($piece);
            array_push($unfinishedMovePaths, $initialPath);

            while (count($unfinishedMovePaths) > 0) {
                $modifiedUnfinishedMovePaths = array();
                foreach ($unfinishedMovePaths as $path) {
                    $lastCell = $path->getLastCell();
                    $previousCell = $path->getPreviousCell();

                    $possibleMoves = getPossibleSingleMovesFromPiece($lastCell, 
                                                                     $pieces, 
                                                                     $boardSize, 
                                                                     $previousCell);
                    if (count($possibleMoves) == 0) {
                        array_push($finishedMovePaths, $path);
                    } else {
                        $newPaths = array();
                        foreach ($possibleMoves as $move) {
                            $pathCopy = clone $path;
                            $pathCopy->addCell($move);
                            if ($move->used) {
                                array_push($finishedMovePaths, $pathCopy);
                            } else {
                                array_push($modifiedUnfinishedMovePaths, $pathCopy);
                            }
                        }

                    }
                }
                $unfinishedMovePaths = $modifiedUnfinishedMovePaths;
            }
        }
    }

    $path = getBestPath($finishedMovePaths, $destination);

    $from = array('x' => $path->getFirstCell()->x, 'y' => $path->getFirstCell()->y);
    $to = array();
    for ($i = 1; $i < count($path->cells); $i++) {
        $toMove = array('x' => $path->cells[$i]->x, 'y' => $path->cells[$i]->y);
        array_push($to, $toMove);
    }
    $move = array('from' => $from, 'to' => $to);
    echo json_encode($move);
}

// Turn a JSON array of cells into an array of cell objects.
function decodePieces($jsonCells) {
    $arrayCells = array();

    foreach ($jsonCells as $jsonCell) {
        array_push($arrayCells, new Cell($jsonCell->x, $jsonCell->y, true));
    }

    return $arrayCells;
}

// Given JSON objects with (x, y) coordinates for upper left and lower right
// destination target area, convert it to an array of cells.
function decodeDestination($upperLeftCell, $lowerRightCell) {
    $arrayCells = array();

    $width = $lowerRightCell->x - $upperLeftCell->x;
    $height = $lowerRightCell->y - $upperLeftCell->y;
    for ($i = 0; $i <= $width; $i++) {
        for ($j = 0; $j <= $height; $j++) {
            $x = $upperLeftCell->x + $i;
            $y = $upperLeftCell->y + $j;
            array_push($arrayCells, new Cell($x, $y));
        }
    }

    return $arrayCells;
}

// Calculate the distance between two cells.
function distanceBetweenCells($loc1, $loc2) {
    return sqrt(pow($loc2->x - $loc1->x, 2) + pow($loc2->y - $loc1->y, 2));
}

// For a given list of pieces, return the top-right-most piece that isn't
// already in the destination.
function getTopRightPiece($cells) {
    $topRightCorner = new Cell(8, 0);
    $minDistance = PHP_INT_MAX;
    $topRightCell = NULL;

    foreach ($cells as $cell) {
        $distance = distanceBetweenCells($cell, $topRightCorner);
        if ($distance < $minDistance && $cell->used) {
            $minDistance = $distance;
            $topRightCell = $cell;
        }
    }

    return $topRightCell;
}

// For a given list of destination cells, return the top-right-most empty cell.
function getTopRightDestination($cells) {
    $topRightCorner = new Cell(8, 0);
    $minDistance = PHP_INT_MAX;
    $topRightCell = NULL;

    foreach ($cells as $cell) {
        $distance = distanceBetweenCells($cell, $topRightCorner);
        if ($distance < $minDistance && !$cell->used) {
            $minDistance = $distance;
            $topRightCell = $cell;
        }
    }

    return $topRightCell;
}

// Given a $movingPiece to move, return a list of all the possible Cells that
// the $movingPiece can move to, either by jumping or not jumping.
// $allPieces is a list of Cells with pieces, and $boardSize is the number
// of rows/columns in the board (assumes a square board).
// $previousLocation is the Cell that the $movingPiece had been in previously,
// and is used to make sure the piece doesn't infinitely move back and forth.
function getPossibleSingleMovesFromPiece($movingPiece, $allPieces, $boardSize, $previousCell = NULL) {
    $possibleMoves = array();

    // Look in 8 cells around $movingPiece, if it's empty and inside the board, 
    // then add the cell to list of possible moves. Also check the cells that
    // the piece can jump to.
    for ($xDiff = -1; $xDiff <= 1; $xDiff++) {
        for ($yDiff = -1; $yDiff <= 1; $yDiff++) {
            // Don't check the cell the piece is currently occupying
            if ($xDiff == 0 && $yDiff == 0) {
                continue;
            }

            // Calculate the coordinates of the cell to check.
            // $cell->used is true to indicate the end of the path.
            $x = $movingPiece->x + $xDiff;
            $y = $movingPiece->y + $yDiff;
            $cell = new Cell($x, $y, true);

            // If the $cell is not occupied by a piece and is inside of the
            // board's boundaries, add the $cell to the $possibleMoves list.
            // Otherwise, if the $cell is occupied, check the space that 
            // can be reached by jumping. If this $jumpCell is empty, then add
            // the $jumpCell to the list of $possibleMoves.
            if (!isCellXYInList($cell, $allPieces) && 
                isCellInsideBoard($cell, $boardSize) &&
                !areCellsEqual($cell, $previousCell)) {
                array_push($possibleMoves, $cell);
            } else {
                // Calculate the coordinates of the jump cell to check
                // $cell->used is true to indicate not the end of the path.
                $x = $movingPiece->x + $xDiff*2;
                $y = $movingPiece->y + $yDiff*2;
                $jumpCell = new Cell($x, $y, false);
                
                if (!isCellXYInList($jumpCell, $allPieces) && 
                    isCellInsideBoard($jumpCell, $boardSize) &&
                    !areCellsEqual($jumpCell, $previousCell)) {
                    array_push($possibleMoves, $jumpCell);
                }
            }
        }
    }

    return $possibleMoves;
}

// Return true if the given Cell's coordinates matches the coordinates of
// a piece in the $pieceList. Otherwise, return false.
function isCellXYInList($cell, $pieceList) {
    foreach ($pieceList as $piece) {
        if ($piece->x == $cell->x && $piece->y == $cell->y) {
            return true;
        }
    }
    return false;
}

// Return true if the Cell's (x, y) coordinates are inside the board boundaries.
function isCellInsideBoard($cell, $boardSize) {
    if (($cell->x >= 0 && $cell->x <= $boardSize-1) && 
        ($cell->y >= 0 && $cell->y <= $boardSize-1)) {
        return true;
    }
    return false;
}

// Return true if the (x, y) of $cellA matches the (x, y) of $cellB
function areCellsEqual($cellA, $cellB) {
    if ($cellA != NULL && $cellB != NULL) {
        if ($cellA->x == $cellB->x && $cellA->y == $cellB->y) {
            return true;
        }
    }
    return false;
}

function getBestPath($paths, $destination) {
    $maxDistance = -1;
    $bestPath = NULL;

    foreach ($paths as $path) {
        $pathLength = distanceBetweenCells($path->getFirstCell(), $path->getLastCell());
        $distanceToDestination = distanceBetweenCells($path->getLastCell(), $destination);
        if ($distanceToDestination != 0) {
            $optimalDistance = $pathLength / $distanceToDestination;
            if ($optimalDistance > $maxDistance) {
                $maxDistance = $optimalDistance;
                $bestPath = $path;
            }
        } else {
            $maxDistance = PHP_INT_MAX;
            $bestPath = $path;
        }
    }

    return $bestPath;
}

?>
