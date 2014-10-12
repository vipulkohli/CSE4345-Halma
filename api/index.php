<?php 
// Jessica Yeh
// http://lyle.smu.edu/~jyeh/4345/api/index.php/getMove

// Uses the Slim PHP REST Framework: http://slimframework.com/
require 'Slim/Slim.php';
$app = new Slim();
$app->post('/getMove', 'getMove');
$app->run();

/**
 * Cell -- Encapsulates X and Y coordinates and a generic marker flag.
 * @author Jessica Yeh <jyeh@smu.edu>
 */
class Cell {
    public $x, $y, $used;

    /**
     * Constructor for Cell. Sets variables.
     * @param int $x x-coordinate
     * @param int $y y-coordinate
     * @param bool $used marker flag
     */
    function Cell($x = 0, $y = 0, $used = false) {
        $this->x = $x;
        $this->y = $y;
        $this->used = $used;
    }
}

/**
 * Path -- Encapsulates the list of points in a path, and helper functions.
 * @author Jessica Yeh <jyeh@smu.edu>
 */
class Path {
    public $cells = array();

    /**
     * Constructor for Path. Adds the first point in the path.
     * @param Cell $start The first point in the path.
     */
    function Path($start) {
        $this->addCell($start);
    }

    /**
     * Returns the last point in the path.
     * @return Cell
     */
    function getLastCell() {
        return end((array_values($this->cells)));
    }

    /**
     * Returns the first point in the path.
     * @return Cell
     */
    function getFirstCell() {
        return $this->cells[0];
    }

    /**
     * Returns the second to last point in the path.
     * @return Cell
     */
    function getPreviousCell() {
        if (count($this->cells) < 2) {
            return NULL;
        } else {
            return $this->cells[count($this->cells)-2];
        }
    }

    /**
     * Adds a point to the end of the path.
     * @param Cell $cell The point to add to the path.
     */
    function addCell($cell) {
        array_push($this->cells, $cell);
    }

    /**
     * Returns a JSON version of the path.
     * @return string JSON string with a "from" and "to"
     */
    function __toString() {
        // Setup the arrays to hold "from" and "to" info    
        $from = array('x' => $this->getFirstCell()->x, 'y' => $this->getFirstCell()->y);
        $to = array();

        // Add path points to the "to" array
        for ($i = 1; $i < count($this->cells); $i++) {
            $toMove = array('x' => $this->cells[$i]->x, 'y' => $this->cells[$i]->y);
            array_push($to, $toMove);
        }

        return json_encode(array('from' => $from, 'to' => $to));
    }
}

/**
 * Given JSON post data with the Halma game board info, print a JSON string
 * with info on the best move to make.
 */
function getMove() {
    // Parse the JSON input
    $board = json_decode(Slim::getInstance()->request()->getBody());
    $boardSize = $board->boardSize;
    $pieces = decodePieces($board->pieces, true);
    $destinations = decodePieces($board->destinations, false);

    // Compile a list of every possible move path
    $paths = generatePossiblePaths($pieces, $boardSize);

    // Pick a destination cell from the target area
    $destination = pickDestinationCell($destinations, $pieces, $boardSize);
    
    // Determine the best move to make and print it out
    echo getBestPath($paths, $destination);
}

/**
 * From a list of all the cells in the destination area, pick one cell.
 * @param Cell[] $destinationArea The list of cells in the destination area.
 * @param Cell[] $pieces The list of all the pieces on the board.
 * @param int $boardSize The number of rows/columns in the game board.
 * @return Cell
 */
function pickDestinationCell($destinationArea, $pieces, $boardSize) {
    $destination = NULL;

    // Pick a destination cell from the destination area
    for ($i = 0; $i < count($destinationArea); $i++) {
        $destination = getTopRightDestination($destinationArea, $boardSize);

        // If a piece is in the destination cell, set some flags to mark as used
        foreach ($pieces as &$piece) {
            if (areCellsEqual($destination, $piece)) {
                $piece->used = false;
                $destination->used = true;
            }
        }

        // Stop looping if an empty destination cell is found
        if (!$destination->used) {
            break;
        }
    }

    return $destination;
}

/**
 * For each of the pieces on the board, generate all the possible paths that the
 * piece can make from its current location. Compiles all of the possible paths 
 * for each piece into one array.
 * 
 * @param Cell[] $pieces The list of all the pieces on the board.
 * @param int $boardSize The number of rows/columns in the game board.
 * @return Path[]
 */
function generatePossiblePaths($pieces, $boardSize) {
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

    return $finishedMovePaths;
}

/**
 * Turn a JSON array of cells into an array of cell objects.
 * @param JSONObject[] $jsonCells An array of JSON objects with coordinates.
 * @param bool $used Whether to mark each of the Cells as "used".
 * @return Cell[]
 */
function decodePieces($jsonCells, $used = true) {
    $arrayCells = array();

    // Create a Cell object for each cell in the JSON array
    foreach ($jsonCells as $jsonCell) {
        array_push($arrayCells, new Cell($jsonCell->x, $jsonCell->y, $used));
    }

    return $arrayCells;
}

/**
 * Calculate the distance between two cells.
 * @param Cell $loc1 One point in the distance calculation.
 * @param Cell $loc2 The other point in the distance calculation.
 * @return float The distance between $loc1 and $loc2.
 */
function distanceBetweenCells($loc1, $loc2) {
    return sqrt(pow($loc2->x - $loc1->x, 2) + pow($loc2->y - $loc1->y, 2));
}

/**
 * For a given list of destination cells, return the top-right-most empty cell.
 * @param Cell[] $cells The array of destination cells.
 * @param int $boardSize The number of rows/columns in the game board.
 * @return Cell The top-right-most empty (used is false) cell.
 */
function getTopRightDestination($cells, $boardSize) {
    $topRightCorner = new Cell($boardSize - 1, 0);
    $minDistance = PHP_INT_MAX;
    $topRightCell = NULL;

    // Determine the Cell that is closest to the top-right corner
    foreach ($cells as $cell) {
        $distance = distanceBetweenCells($cell, $topRightCorner);
        if ($distance < $minDistance && !$cell->used) {
            $minDistance = $distance;
            $topRightCell = $cell;
        }
    }

    return $topRightCell;
}


/**
 * Given a $movingPiece to move, return a list of all the possible Cells that
 * the $movingPiece can move to, either by jumping or not jumping.
 * $allPieces is a list of Cells with pieces, and $boardSize is the number
 * of rows/columns in the board (assumes a square board).
 * $previousLocation is the Cell that the $movingPiece had been in previously,
 * and is used to make sure the piece doesn't infinitely move back and forth.
 * @param Cell $movingPiece The piece that is moving.
 * @param Cell[] $allPieces All of the pieces on the board.
 * @param int $boardSize The number of rows/columns in the board.
 * @param Cell $previousCell The previous location (if any) of the $movingPiece.
 * @return Cell[] A list of Cells that the $movingPiece can move to.
 */
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

/**
 * Return true if the given Cell's coordinates matches the coordinates of
 * a piece in the $pieceList. Otherwise, return false.
 * @param Cell $cell The Cell we're checking to see if it's in the list.
 * @param Cell[] $cell The list of pieces from which to search for the Cell.
 * @return bool
 */
function isCellXYInList($cell, $pieceList) {
    foreach ($pieceList as $piece) {
        if ($piece->x == $cell->x && $piece->y == $cell->y) {
            return true;
        }
    }
    return false;
}

/**
 * Return true if the Cell's (x, y) coordinates are inside the board boundaries.
 * @param Cell $cell The Cell that we are checking.
 * @param int $boardSize Number of rows/columns of the game board.
 * @return bool
 */
function isCellInsideBoard($cell, $boardSize) {
    if (($cell->x >= 0 && $cell->x <= $boardSize-1) && 
        ($cell->y >= 0 && $cell->y <= $boardSize-1)) {
        return true;
    }
    return false;
}

/**
 * Return true if the (x, y) of $cellA matches the (x, y) of $cellB
 * @param Cell $cellA The first cell to compare.
 * @param Cell $cellB The second cell to compare.
 * @return bool
 */
function areCellsEqual($cellA, $cellB) {
    if ($cellA != NULL && $cellB != NULL) {
        if ($cellA->x == $cellB->x && $cellA->y == $cellB->y) {
            return true;
        }
    }
    return false;
}

/**
 * Return the best path out of the list of paths to reach the destination
 * @param Path[] $paths The list of Paths to choose from.
 * @param Cell $destination The destination Cell.
 * @return Path
 */
function getBestPath($paths, $destination) {
    $maxDistance = -1;
    $bestPath = NULL;

    // Iterate through the $paths to find the best $path
    foreach ($paths as $path) {
        $pathLength = distanceBetweenCells($path->getFirstCell(), $path->getLastCell());
        $distanceToDestination = distanceBetweenCells($path->getLastCell(), $destination);

        // Prevent divide-by-0 error when calculating the best path
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

/**
 * Compares two numbers, $a and $b.
 * @param float $a The left-hand-side number to compare.
 * @param float $b The right-hand-side number to compare.
 * @return bool if $a > $b, return 1; if $a < $b, return -1; if $a = $b, return 0
 */
function compare($a, $b) {
    return ($a == $b) ? 0 : (($a > $b) ? 1 : -1);
}

?>
