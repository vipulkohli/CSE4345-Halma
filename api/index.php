<?php 
// Uses the Slim PHP REST Framework: http://slimframework.com/
require 'Slim/Slim.php';
$app = new Slim();

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

// Class to encapsulate the list of points in a path, and helper functions
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
}

/**
 * Compares two numbers $a and $b
 * @return if $a > $b, return 1; if $a < $b, return -1; if $a = $b, return 0
 */
function compare($a, $b) {
    return ($a == $b) ? 0 : (($a > $b) ? 1 : -1);
}

/**
 * HW 10: Halma AI v1.1
 */
function getMove() {
    // Parse the JSON input
    $board = json_decode(Slim::getInstance()->request()->getBody());
    $boardSize = $board->boardSize;
    $pieces = decodePieces($board->pieces, true);
    $target = decodePieces($board->destinations, false);

    // Pick a destination cell from the target area
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

    // Compile a list of every possible move path
    foreach ($pieces as &$piece) {
        // Only consider pieces that still need to reach destination
        if ($piece->used) {
            // Start the path with the chosen piece's location
            $unfinishedMovePaths = array();
            $initialPath = new Path($piece);
            array_push($unfinishedMovePaths, $initialPath);

            // Iterate until all the paths have an endpoint
            while (count($unfinishedMovePaths) > 0) {
                // Array for compiling an updated list of paths without endpoints
                $modifiedUnfinishedMovePaths = array();

                // Get each unfinished path one step closer to finished
                foreach ($unfinishedMovePaths as $path) {
                    // Get the last two points in the path
                    $lastCell = $path->getLastCell();
                    $previousCell = $path->getPreviousCell();

                    // Generate a list of cells that can be reached from the
                    // the last cell in the path
                    $possibleMoves = getPossibleSingleMovesFromPiece($lastCell, 
                                                                     $pieces, 
                                                                     $boardSize, 
                                                                     $previousCell);

                    // If there are no possible moves, mark the path as finished.
                    // Otherwise, append the possible moves to the current path.
                    if (count($possibleMoves) == 0) {
                        array_push($finishedMovePaths, $path);
                    } else {
                        // Make a new path option for each possible move
                        foreach ($possibleMoves as $move) {
                            $pathCopy = clone $path;
                            // Add the move to the path
                            $pathCopy->addCell($move);
                            // If the move is the endpoint, mark path as finished
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

    // Determine the best move to make
    $path = getBestPath($finishedMovePaths, $destination);

    // Print out the chosen best move as JSON in the format:
    // {"from": {"x": 22, "y": 30}, "to": [{"x": 24, "y": 30}, {"x": 26, "y": 30}]}
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
function decodePieces($jsonCells, $used = true) {
    $arrayCells = array();

    foreach ($jsonCells as $jsonCell) {
        array_push($arrayCells, new Cell($jsonCell->x, $jsonCell->y, $used));
    }

    return $arrayCells;
}

// Calculate the distance between two cells.
function distanceBetweenCells($loc1, $loc2) {
    return sqrt(pow($loc2->x - $loc1->x, 2) + pow($loc2->y - $loc1->y, 2));
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

// Return the best path out of the list of paths to reach the destination
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
