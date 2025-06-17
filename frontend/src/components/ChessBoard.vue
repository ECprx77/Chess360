<template>
  <div class="window-container">
    <div class="window-header">
      <div class="window-title">Chess360</div>
      <div class="window-controls">
        <button class="control-button minimize">−</button>
        <button class="control-button maximize">□</button>
        <button class="control-button close">×</button>
      </div>
    </div>
    <div class="window-content">
      <div class="chess-board">
        <div v-for="row in 8" :key="row" class="board-row">
          <div 
            v-for="col in 8" 
            :key="col" 
            :class="[
              'board-cell', 
              getCellColor(row, col),
              { 'legal-move': isLegalMove(row-1, col-1) }
            ]"
            @dragover.prevent
            @drop="handleDrop($event, row-1, col-1)"
          >
            <img 
              v-if="board[row-1] && board[row-1][col-1]"
              :src="getPieceImage(board[row-1][col-1])"
              :alt="board[row-1][col-1]"
              class="chess-piece"
              draggable="true"
              @dragstart="handleDragStart($event, row-1, col-1)"
              @click="handlePieceClick(row-1, col-1)"
            >
            <div v-if="isLegalMove(row-1, col-1)" class="move-indicator"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

// Initialize board with starting position
const initialPosition = [
  ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'],
  ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p'],
  Array(8).fill(''),
  Array(8).fill(''),
  Array(8).fill(''),
  Array(8).fill(''),
  ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P'],
  ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R']
];

const board = ref(initialPosition);
const draggedPiece = ref(null);
const legalMoves = ref([]);

const getPieceImage = (piece) => {
  if (!piece) return '';
  const color = piece === piece.toUpperCase() ? 'white' : 'black';
  const pieceName = piece.toLowerCase();
  const pieceTypes = {
    'k': 'king',
    'q': 'queen',
    'r': 'rook',
    'b': 'bishop',
    'n': 'knight',
    'p': 'pawn'
  };
  return `/pieces/${color}/${pieceTypes[pieceName]}.png`;
};

const getCellColor = (row, col) => {
  return (row + col) % 2 === 0 ? 'white-cell' : 'black-cell';
};

const getLegalMoves = async (row, col) => {
  const files = 'abcdefgh';
  const ranks = '87654321';
  const square = files[col] + ranks[row];
  
  try {
    const response = await fetch('http://localhost:8000/chess/game/legal-moves', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ square })
    });
    const data = await response.json();
    return data.legal_moves;
  } catch (error) {
    console.error('Failed to get legal moves:', error);
    return [];
  }
};

const isLegalMove = (row, col) => {
  const files = 'abcdefgh';
  const ranks = '87654321';
  const targetSquare = files[col] + ranks[row];
  
  return legalMoves.value.some(move => move.slice(2) === targetSquare);
};

const isCastlingMove = (moveUCI) => {
  const castlingMoves = {
    'e1g1': { from: 'h1', to: 'f1' }, // White kingside
    'e1c1': { from: 'a1', to: 'd1' }, // White queenside
    'e8g8': { from: 'h8', to: 'f8' }, // Black kingside
    'e8c8': { from: 'a8', to: 'd8' }  // Black queenside
  };
  return castlingMoves[moveUCI];
};

const updateBoardPosition = (fromRow, fromCol, toRow, toCol, piece) => {
  board.value[fromRow][fromCol] = '';
  board.value[toRow][toCol] = piece;
};

const handlePieceClick = async (row, col) => {
  // Clear previous legal moves if clicking the same piece
  if (draggedPiece.value?.row === row && draggedPiece.value?.col === col) {
    draggedPiece.value = null;
    legalMoves.value = [];
    return;
  }
  
  // Set new dragged piece and get legal moves
  draggedPiece.value = {
    row,
    col,
    piece: board.value[row][col]
  };
  legalMoves.value = await getLegalMoves(row, col);
};

const handleDragStart = async (event, row, col) => {
  draggedPiece.value = {
    row,
    col,
    piece: board.value[row][col]
  };
  event.dataTransfer.setData('text/plain', board.value[row][col]);
  
  // Get and show legal moves
  legalMoves.value = await getLegalMoves(row, col);
};

const handleDrop = async (event, targetRow, targetCol) => {
  event.preventDefault();
  
  if (!draggedPiece.value) return;
  
  const { row: fromRow, col: fromCol } = draggedPiece.value;
  
  // Convert target position to UCI format
  const files = 'abcdefgh';
  const ranks = '87654321';
  const moveUCI = files[fromCol] + ranks[fromRow] + files[targetCol] + ranks[targetRow];
  
  // Check if move is legal using the global legalMoves ref
  if (!legalMoves.value.includes(moveUCI)) {
    console.log('Illegal move');
    draggedPiece.value = null;
    return;
  }
  
  try {
    const response = await fetch('http://localhost:8000/chess/game/move', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ move: moveUCI })
    });
    
    const data = await response.json();
    if (!data.error) {
      // Update king position
      updateBoardPosition(fromRow, fromCol, targetRow, targetCol, draggedPiece.value.piece);
      
      // Check if this is a castling move and update rook position
      const castling = isCastlingMove(moveUCI);
      if (castling) {
        const rookFrom = {
          row: parseInt(ranks.indexOf(castling.from[1])),
          col: files.indexOf(castling.from[0])
        };
        const rookTo = {
          row: parseInt(ranks.indexOf(castling.to[1])),
          col: files.indexOf(castling.to[0])
        };
        const rookPiece = board.value[rookFrom.row][rookFrom.col];
        updateBoardPosition(rookFrom.row, rookFrom.col, rookTo.row, rookTo.col, rookPiece);
      }
    }
  } catch (error) {
    console.error('Failed to make move:', error);
  }
  
  // Clear legal moves after drop
  legalMoves.value = [];
  draggedPiece.value = null;
};

// initialization function
const initializeGame = async (variant = "chess960") => {
  try {
    const response = await fetch('http://localhost:8000/chess/game/start', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ variant })
    });
    const data = await response.json();
    console.log('Game initialized:', data);
    
    // Convert FEN to board array
    const fenParts = data.fen.split(' ');
    const position = fenParts[0].split('/').map(row => 
      row.split('').reduce((acc, char) => {
        if (isNaN(char)) {
          acc.push(char);
        } else {
          acc.push(...Array(parseInt(char)).fill(''));
        }
        return acc;
      }, [])
    );
    
    // Update board with FEN position
    board.value = position;
    
    // Clear any existing state
    draggedPiece.value = null;
    legalMoves.value = [];
  } catch (error) {
    console.error('Failed to initialize game:', error);
  }
};

// Call initialization on component mount
onMounted(() => {
  initializeGame();
});
</script>

<style scoped>
.window-container {
  background-color: #2a2a2a;
  border: 1px solid #9370DB;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 500px;
  overflow: hidden;
}

.window-header {
  background-color: #333333;
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 10px;
  border-bottom: 1px solid #9370DB;
  user-select: none;
}

.window-title {
  color: #ffffff;
  font-size: 14px;
  font-weight: 500;
}

.window-controls {
  display: flex;
  gap: 8px;
}

.control-button {
  background: none;
  border: none;
  color: #ffffff;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.control-button:hover {
  background-color: #4a4a4a;
}

.close:hover {
  background-color: #9370DB;
}

.window-content {
  padding: 15px;
  background-color: #2a2a2a;
}

.chess-board {
  width: 470px;
  height: 470px;
  border: 2px solid #9370DB;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  background-color: #333333;
}

.board-row {
  display: flex;
  flex: 1;
}

.board-cell {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
}

.white-cell {
  background-color: #e9d8bc;
}

.black-cell {
  background-color: #9370DB;
}

.legal-move {
  position: relative;
}

.move-indicator {
  position: absolute;
  width: 20px;
  height: 20px;
  background-color: rgba(180, 143, 255, 0.7);
  border-radius: 50%;
  pointer-events: none;
  z-index: 1;
}

.chess-piece {
  position: relative;
  z-index: 2;
  width: 90%;
  height: 90%;
  object-fit: contain;
  user-select: none;
  cursor: grab;
}

.chess-piece:active {
  cursor: grabbing;
}
</style>