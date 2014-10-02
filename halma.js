var kBoardWidth = 9;
var kBoardHeight= 9;
var kPieceWidth = 50;
var kPieceHeight= 50;
var kPixelWidth = 1 + (kBoardWidth * kPieceWidth);
var kPixelHeight= 1 + (kBoardHeight * kPieceHeight);

var gCanvasElement;
var gDrawingContext;
var gPattern;

var gPieces;
var gNumPieces;
var gSelectedPieceIndex;
var gSelectedPieceHasMoved;
var gMoveCount;
var gMoveCountElem;
var gGameInProgress;

function Cell(row, column) {
    this.row = row;
    this.column = column;
}

function getCursorPosition(e) {
    /* returns Cell with .row and .column properties */
    var x;
    var y;
    if (e.pageX != undefined && e.pageY != undefined) {
        x = e.pageX;
        y = e.pageY;
    } else {
        x = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
        y = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
    }
    x -= gCanvasElement.offsetLeft;
    y -= gCanvasElement.offsetTop;
    x = Math.min(x, kBoardWidth * kPieceWidth);
    y = Math.min(y, kBoardHeight * kPieceHeight);
    var cell = new Cell(Math.floor(y/kPieceHeight), Math.floor(x/kPieceWidth));
    return cell;
}

function halmaOnClick(e) {
    var cell = getCursorPosition(e);
    for (var i = 0; i < gNumPieces; i++) {
        if ((gPieces[i].row == cell.row) && (gPieces[i].column == cell.column)) {
            clickOnPiece(i);
            return;
        }
    }
    clickOnEmptyCell(cell);
}

function clickOnEmptyCell(cell) {
    if (gSelectedPieceIndex == -1) {
        return;
    }
    var rowDiff = Math.abs(cell.row - gPieces[gSelectedPieceIndex].row);
    var columnDiff = Math.abs(cell.column - gPieces[gSelectedPieceIndex].column);
    if ((rowDiff <= 1) && (columnDiff <= 1)) {
        /* we already know that this click was on an empty square,
           so that must mean this was a valid single-square move */
        gPieces[gSelectedPieceIndex].row = cell.row;
        gPieces[gSelectedPieceIndex].column = cell.column;
        gMoveCount += 1;
        gSelectedPieceIndex = -1;
        gSelectedPieceHasMoved = false;
        drawBoard();
        return;
    }
    if ((((rowDiff == 2) && (columnDiff == 0)) ||
     ((rowDiff == 0) && (columnDiff == 2)) ||
     ((rowDiff == 2) && (columnDiff == 2))) && 
    isThereAPieceBetween(gPieces[gSelectedPieceIndex], cell)) {
        /* this was a valid jump */
        if (!gSelectedPieceHasMoved) {
            gMoveCount += 1;
        }
        gSelectedPieceHasMoved = true;
        gPieces[gSelectedPieceIndex].row = cell.row;
        gPieces[gSelectedPieceIndex].column = cell.column;
        drawBoard();
        return;
    }
    gSelectedPieceIndex = -1;
    gSelectedPieceHasMoved = false;
    drawBoard();
}

function clickOnPiece(pieceIndex) {
    if (gSelectedPieceIndex == pieceIndex) {
        return;
    }
    gSelectedPieceIndex = pieceIndex;
    gSelectedPieceHasMoved = false;
    drawBoard();
}

function isThereAPieceBetween(cell1, cell2) {
    /* note: assumes cell1 and cell2 are 2 squares away
       either vertically, horizontally, or diagonally */
    var rowBetween = (cell1.row + cell2.row) / 2;
    var columnBetween = (cell1.column + cell2.column) / 2;
    for (var i = 0; i < gNumPieces; i++) {
        if ((gPieces[i].row == rowBetween) &&
            (gPieces[i].column == columnBetween)) {
            return true;
        }
    }
    return false;
}

// Return whether or not the given piece is located in the target finish area.
function isPieceInTarget(piece) {
    var startColumn = 6,
        endColumn = 8,
        startRow = 0,
        endRow = 2;
    if (piece.column >= startColumn && piece.column <= endColumn && 
        piece.row >= startRow && piece.row <= endRow) {
        return true;
    } else {
        return false;
    }
}

function isTheGameOver() {
    for (var i = 0; i < gNumPieces; i++) {
        if (!isPieceInTarget(gPieces[i])) {
            return false;
        }
    }
    return true;
}

function drawBoard() {
    if (gGameInProgress && isTheGameOver()) {
        endGame();
    }

    gDrawingContext.clearRect(0, 0, kPixelWidth, kPixelHeight);

    gDrawingContext.beginPath();
    
    /* vertical lines */
    for (var x = 0; x <= kPixelWidth; x += kPieceWidth) {
        gDrawingContext.moveTo(0.5 + x, 0);
        gDrawingContext.lineTo(0.5 + x, kPixelHeight);
    }
    
    /* horizontal lines */
    for (var y = 0; y <= kPixelHeight; y += kPieceHeight) {
        gDrawingContext.moveTo(0, 0.5 + y);
        gDrawingContext.lineTo(kPixelWidth, 0.5 +  y);
    }
    
    /* draw it! */
    gDrawingContext.strokeStyle = "#ccc";
    gDrawingContext.stroke();
    
    for (var i = 0; i < 9; i++) {
        drawPiece(gPieces[i], i == gSelectedPieceIndex);
    }

    gMoveCountElem.innerHTML = gMoveCount;

    saveGameState();
}

function drawPiece(p, selected) {
    var column = p.column;
    var row = p.row;
    var x = (column * kPieceWidth) + (kPieceWidth/2);
    var y = (row * kPieceHeight) + (kPieceHeight/2);
    var radius = (kPieceWidth/2) - (kPieceWidth/10);
    gDrawingContext.beginPath();
    gDrawingContext.arc(x, y, radius, 0, Math.PI*2, false);
    gDrawingContext.closePath();
    gDrawingContext.strokeStyle = "#000";
    gDrawingContext.stroke();
    // Color the piece black if selected, color it green if it's in the target area
    if (selected) {
        gDrawingContext.fillStyle = "#000";
        gDrawingContext.fill();
    }
    else if (isPieceInTarget(p)) {
        gDrawingContext.fillStyle = "#0f0";
        gDrawingContext.fill();
    }
}

if (typeof resumeGame != "function") {
    saveGameState = function() {
        return false;
    }
    resumeGame = function() {
        return false;
    }
}

function newGame() {
    gPieces = [new Cell(kBoardHeight - 3, 0),
           new Cell(kBoardHeight - 2, 0),
           new Cell(kBoardHeight - 1, 0),
           new Cell(kBoardHeight - 3, 1),
           new Cell(kBoardHeight - 2, 1),
           new Cell(kBoardHeight - 1, 1),
           new Cell(kBoardHeight - 3, 2),
           new Cell(kBoardHeight - 2, 2),
           new Cell(kBoardHeight - 1, 2)];
    gNumPieces = gPieces.length;
    gSelectedPieceIndex = -1;
    gSelectedPieceHasMoved = false;
    gMoveCount = 0;
    gGameInProgress = true;
    drawBoard();
}

function endGame() {
    gSelectedPieceIndex = -1;
    gGameInProgress = false;
}

function initGame(canvasElement, moveCountElement) {
    if (!canvasElement) {
        canvasElement = document.createElement("canvas");
        canvasElement.id = "halma_canvas";
        document.body.appendChild(canvasElement);
    }
    if (!moveCountElement) {
        moveCountElement = document.createElement("p");
        document.body.appendChild(moveCountElement);
    }
    gCanvasElement = canvasElement;
    gCanvasElement.width = kPixelWidth;
    gCanvasElement.height = kPixelHeight;
    gCanvasElement.addEventListener("click", halmaOnClick, false);
    gMoveCountElem = moveCountElement;
    gDrawingContext = gCanvasElement.getContext("2d");
    if (!resumeGame()) {
        newGame();
    }
}

function encodePiecesAsJson(pieces) {
    var piecesArray = [];
    for (var i = 0; i < pieces.length; i++) {
        var piece = {
            x: pieces[i].column,
            y: pieces[i].row
        };
        piecesArray.push(piece);
    }
    return JSON.stringify(piecesArray);
}

$(document).ready(function() {
    $("#nextMove").click(function() {
        var board = encodePiecesAsJson(gPieces);
        var upperLeftCell = '{"x":6,"y":0}';
        var lowerRightCell = '{"x":8,"y":2}';
        $.ajax({
            type: "POST",
            url: "api/getMove",
            data: { "board": board, 
                    "upperLeftCell": upperLeftCell, 
                    "lowerRightCell": lowerRightCell },
            success: function(move) {
                move = JSON.parse(move);
                for (var i = 0; i < gPieces.length; i++) {
                    if (gPieces[i].column === move[0].x && 
                        gPieces[i].row === move[0].y) {
                        clickOnPiece(i);
                    }
                }
                clickOnEmptyCell({"column": move[1].x, "row": move[1].y});
            }
        });
    });
});