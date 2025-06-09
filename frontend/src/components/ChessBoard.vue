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
            :class="['board-cell', getCellColor(row, col)]"
            @click="handleCellClick(row-1, col-1)"
          >
            <div v-if="board[row-1][col-1]" class="chess-piece">
              {{ getPieceSymbol(board[row-1][col-1]) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

// Initialize empty 8x8 board
const board = ref(Array(8).fill().map(() => Array(8).fill('')));
const selectedPiece = ref(null);
const gameState = ref(null);

// Initialize game through API
const initializeGame = async () => {
  try {
    const response = await fetch('http://localhost:8000/chess/game/start', {
      method: 'POST'
    });
    await response.json();
    await updateGameState();
  } catch (error) {
    console.error('Failed to initialize game:', error);
  }
};

// Get current game state from API
const updateGameState = async () => {
  try {
    const response = await fetch('http://localhost:8000/chess/game/board');
    const data = await response.json();
    updateBoardFromFEN(data.board.fen);
    gameState.value = data.board;
  } catch (error) {
    console.error('Failed to get game state:', error);
  }
};

// Make move through API
const makeMove = async (from, to) => {
  try {
    const move = `${from}${to}`;  // Convert to UCI format (e.g., "e2e4")
    const response = await fetch('http://localhost:8000/chess/game/move', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ move: move })
    });
    const data = await response.json();
    
    if (data.error) {
      alert('Invalid move!');
      return false;
    }
    
    await updateGameState();
    return true;
  } catch (error) {
    console.error('Failed to make move:', error);
    return false;
  }
};

// Get cell color based on position
const getCellColor = (row, col) => {
  return (row + col) % 2 === 0 ? 'white-cell' : 'black-cell';
};

// Convert piece notation to Unicode chess symbols
const getPieceSymbol = (piece) => {
  const symbols = {
    'k': '♔', 'q': '♕', 'r': '♖', 'b': '♗', 'n': '♘', 'p': '♙',
    'K': '♚', 'Q': '♛', 'R': '♜', 'B': '♝', 'N': '♞', 'P': '♟'
  };
  return symbols[piece] || '';
};

// Handle cell click for piece movement
const handleCellClick = async (row, col) => {
  if (!selectedPiece.value) {
    if (board.value[row][col]) {
      selectedPiece.value = { row, col };
    }
  } else {
    const fromSquare = convertToAlgebraic(selectedPiece.value.row, selectedPiece.value.col);
    const toSquare = convertToAlgebraic(row, col);
    
    const success = await makeMove(fromSquare, toSquare);
    if (!success) {
      selectedPiece.value = null;
      return;
    }
    
    selectedPiece.value = null;
  }
};

// Convert board position to algebraic notation (e.g., e2)
const convertToAlgebraic = (row, col) => {
  const files = 'abcdefgh';
  const ranks = '87654321';
  return files[col] + ranks[row];
};

// Update board from FEN string
const updateBoardFromFEN = (fen) => {
  const fenBoard = fen.split(' ')[0];
  const rows = fenBoard.split('/');
  const newBoard = [];
  
  for (const row of rows) {
    const boardRow = [];
    for (const char of row) {
      if (isNaN(char)) {
        boardRow.push(char);
      } else {
        for (let i = 0; i < parseInt(char); i++) {
          boardRow.push('');
        }
      }
    }
    newBoard.push(boardRow);
  }
  
  board.value = newBoard;
};

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

.chess-piece {
  font-size: 2em;
  user-select: none;
  color: #333333;
}
</style>