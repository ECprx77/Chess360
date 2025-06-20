<template>
  <!-- Chess board container with window styling -->
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
      <!-- Chess board with dynamic perspective based on player color -->
      <div class="chess-board" :class="{ 'black-perspective': isBlackPlayer }">
        <div v-for="(row, rowIndex) in board" :key="rowIndex" class="board-row">
          <div v-for="(piece, colIndex) in row" 
               :key="colIndex" 
               class="board-cell"
               :class="[getCellColor(rowIndex, colIndex), { 'legal-move': isLegalMove(rowIndex, colIndex) }]"
               @dragenter.prevent
               @dragover.prevent
               @drop="handleDrop($event, rowIndex, colIndex)">
            <!-- Visual indicator for legal moves -->
            <div v-if="isLegalMove(rowIndex, colIndex)" class="move-indicator"></div>
            <!-- Chess piece with drag functionality -->
            <img v-if="piece"
                 :src="getPieceImage(piece)"
                 class="chess-piece"
                 :draggable="true"
                 @dragstart="handleDragStart($event, rowIndex, colIndex)"
                 @click="handlePieceClick(rowIndex, colIndex)" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Chess Board Component
 * 
 * Handles the interactive chess board display, piece movement via drag and drop,
 * legal move validation, and real-time communication with the game server.
 * Supports both white and black perspectives and Chess960 positions.
 */

import { ref, computed, defineExpose, defineProps, onMounted, onUnmounted, watch, defineEmits } from 'vue';
import { io } from 'socket.io-client';

// Component props for player color and game identification
const props = defineProps({
  playerColor: {
    type: String,
    default: 'white',
    validator: (value) => ['white', 'black'].includes(value)
  },
  gameId: {
    type: String,
    default: ''
  }
});

// Emit game over events to parent component
const emit = defineEmits(['game-over']);

// Computed property to determine if player is black (affects board perspective)
const isBlackPlayer = computed(() => props.playerColor === 'black');

// Reactive state for game management
const isMyTurn = ref(props.playerColor === 'white');
const board = ref([]);
const draggedPiece = ref(null);
const legalMoves = ref([]);
const socket = ref(null);
const socketReady = ref(false);

/**
 * Get the image path for a chess piece based on its type and color
 * @param {string} piece - The piece character (K, Q, R, B, N, P or lowercase)
 * @returns {string} Path to the piece image
 */
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

/**
 * Determine cell color for alternating board pattern
 * @param {number} row - Row index
 * @param {number} col - Column index
 * @returns {string} CSS class for cell color
 */
const getCellColor = (row, col) => {
  return (row + col) % 2 === 0 ? 'white-cell' : 'black-cell';
};

/**
 * Get rank labels based on player perspective
 * @param {boolean} isBlack - Whether to use black perspective
 * @returns {string} Rank labels string
 */
const getRanks = (isBlack = false) => {
  const ranks = '87654321';
  return isBlack ? ranks.split('').reverse().join('') : ranks;
};

/**
 * Check if a square is a legal move for the currently dragged piece
 * @param {number} row - Target row
 * @param {number} col - Target column
 * @returns {boolean} Whether the move is legal
 */
const isLegalMove = (row, col) => {
  if (!draggedPiece.value || !isMyTurn.value || !legalMoves.value) return false;
  
  const files = 'abcdefgh';
  const ranks = getRanks(props.playerColor === 'black');
  const targetSquare = files[col] + ranks[row];
  
  return legalMoves.value.some(move => {
    const moveTarget = move.slice(2, 4);
    return moveTarget === targetSquare;
  });
};

/**
 * Check if a move is a castling move and return rook movement details
 * @param {string} moveUCI - Move in UCI format
 * @returns {Object|null} Castling details or null if not castling
 */
const isCastlingMove = (moveUCI) => {
  const castlingMoves = {
    'e1g1': { from: 'h1', to: 'f1' }, // White kingside
    'e1c1': { from: 'a1', to: 'd1' }, // White queenside
    'e8g8': { from: 'h8', to: 'f8' }, // Black kingside
    'e8c8': { from: 'a8', to: 'd8' }  // Black queenside
  };
  return castlingMoves[moveUCI];
};

/**
 * Handle piece drop on a target square
 * @param {Event} event - Drop event
 * @param {number} targetRow - Target row
 * @param {number} targetCol - Target column
 */
const handleDrop = async (event, targetRow, targetCol) => {
  event.preventDefault();
  
  if (!draggedPiece.value || !isMyTurn.value) {
    console.log('Not your turn or no piece selected');
    return;
  }
  
  if (!isLegalMove(targetRow, targetCol)) {
    console.log('Illegal move attempted');
    draggedPiece.value = null;
    legalMoves.value = [];
    return;
  }
  
  const { row: fromRow, col: fromCol } = draggedPiece.value;
  const files = 'abcdefgh';
  const ranks = getRanks(props.playerColor === 'black');
  const moveUCI = files[fromCol] + ranks[fromRow] + files[targetCol] + ranks[targetRow];
  
  console.log('Attempting move:', moveUCI);

  // Handle special castling moves
  const castling = isCastlingMove(moveUCI);
  if (castling) {
    const [rookFromRow, rookFromCol] = getRookPosition(castling.from);
    const [rookToRow, rookToCol] = getRookPosition(castling.to);
    handleCastling(rookFromRow, rookFromCol, rookToRow, rookToCol, draggedPiece.value.piece);
  }

  // Optimistic board update for immediate visual feedback
  const newBoard = JSON.parse(JSON.stringify(board.value));
  newBoard[targetRow][targetCol] = draggedPiece.value.piece;
  newBoard[fromRow][fromCol] = '';
  board.value = newBoard;

  // Send move to server and handle response
  socket.value.emit('make_move', { move: moveUCI }, (response) => {
    console.log('Move response:', response);
    if (response?.error) {
      console.error('Move error:', response.error);
      // Revert board if move failed
      const revertBoard = JSON.parse(JSON.stringify(board.value));
      revertBoard[fromRow][fromCol] = draggedPiece.value.piece;
      revertBoard[targetRow][targetCol] = '';
      board.value = revertBoard;
    }
    draggedPiece.value = null;
    legalMoves.value = [];
  });
};

/**
 * Initialize Socket.IO connection and set up event handlers
 */
onMounted(async () => {
  // Establish connection to game server
  socket.value = io('http://localhost:8000', {
    transports: ['websocket', 'polling'],
    withCredentials: true
  });
  
  // Handle successful connection
  socket.value.on('connect', () => {
    console.log('Connected to game server, socket id:', socket.value.id);
    socketReady.value = true;
    
    // Join game room if game ID is provided
    if (props.gameId) {
      socket.value.emit('join_game', {
        gameId: props.gameId,
        color: props.playerColor
      });
    }
  });
  
  // Handle successful game join
  socket.value.on('game_joined', (data) => {
    console.log('Joined game:', data);
    // Determine initial turn based on player color and game state
    isMyTurn.value = (props.playerColor === 'white' && data.is_white_turn) || 
                     (props.playerColor === 'black' && !data.is_white_turn);
    console.log(`Turn initialized: ${isMyTurn.value}, color: ${props.playerColor}, is_white_turn: ${data.is_white_turn}`);
    updateBoardFromFen(data.fen);
  });
  
  // Handle opponent moves
  socket.value.on('move_made', (data) => {
    console.log('Move made event received:', data);
    if (data.fen) {
      // Parse FEN string and update board position
      const position = data.fen.split(' ')[0].split('/').map(row => 
        row.split('').reduce((acc, char) => {
          if (isNaN(char)) {
            acc.push(char);
          } else {
            acc.push(...Array(parseInt(char)).fill(''));
          }
          return acc;
        }, [])
      );
      
      // Update board with proper perspective
      board.value = props.playerColor === 'black' ? [...position].reverse() : [...position];
      
      // Update turn indicator
      isMyTurn.value = (props.playerColor === 'white' && data.is_white_turn) || 
                       (props.playerColor === 'black' && !data.is_white_turn);
      
      console.log('Board updated from move:', {
        fen: data.fen,
        newBoard: board.value,
        isMyTurn: isMyTurn.value,
        playerColor: props.playerColor
      });
    }
  });

  // Handle legal moves response from server
  socket.value.on('legal_moves', (data) => {
    console.log('Received legal moves:', data);
    if (data && Array.isArray(data.legal_moves)) {
      legalMoves.value = data.legal_moves;
    } else {
      console.error('Invalid legal moves response:', data);
      legalMoves.value = [];
    }
  });

  // Handle game over events
  socket.value.on('game_over', (data) => {
    console.log('Game over:', data);
    emit('game-over', data);
  });
});

/**
 * Handle piece click to get legal moves
 * @param {number} row - Row index of clicked piece
 * @param {number} col - Column index of clicked piece
 */
const handlePieceClick = async (row, col) => {
  const piece = board.value[row][col];
  if (!piece) return;
  
  // Validate piece ownership and turn
  const isWhitePiece = piece === piece.toUpperCase();
  if ((props.playerColor === 'white' && !isWhitePiece) || 
      (props.playerColor === 'black' && isWhitePiece) ||
      !isMyTurn.value) {
    console.log('Not your turn or piece');
    return;
  }
  
  // Set dragged piece and request legal moves
  draggedPiece.value = { row, col, piece };
  const files = 'abcdefgh';
  const ranks = getRanks(props.playerColor === 'black');
  const square = files[col] + ranks[row];
  
  console.log('Getting legal moves for:', piece, 'at', square);
  socket.value.emit('get_legal_moves', { square });
};

/**
 * Handle drag start event for piece movement
 * @param {Event} event - Drag start event
 * @param {number} row - Row index of dragged piece
 * @param {number} col - Column index of dragged piece
 */
const handleDragStart = async (event, row, col) => {
  const piece = board.value[row][col];
  const isWhitePiece = piece === piece.toUpperCase();
  
  // Validate piece ownership and turn
  if ((props.playerColor === 'white' && !isWhitePiece) || 
      (props.playerColor === 'black' && isWhitePiece) ||
      !isMyTurn.value) {
    event.preventDefault();
    return;
  }
  
  // Set dragged piece and request legal moves
  draggedPiece.value = { row, col, piece };
  event.dataTransfer.setData('text/plain', '');
  
  const files = 'abcdefgh';
  const ranks = getRanks(props.playerColor === 'black');
  const square = files[col] + ranks[row];
  
  console.log('Getting legal moves for:', piece, 'at', square);
  socket.value.emit('get_legal_moves', { square });
};

/**
 * Handle castling moves by moving the rook to the correct position
 * @param {number} fromRow - Rook's starting row
 * @param {number} fromCol - Rook's starting column
 * @param {number} toRow - Rook's target row
 * @param {number} toCol - Rook's target column
 * @param {string} piece - King piece character
 */
const handleCastling = (fromRow, fromCol, toRow, toCol, piece) => {
  const rankIndex = piece === 'K' ? 7 : 0;
  let rookCol;
  
  // Kingside castling (king moving towards h-file)
  if (toCol > fromCol) {
    // Find the rightmost rook
    for (let col = 7; col > fromCol; col--) {
      if (board.value[rankIndex][col]?.toLowerCase() === 'r') {
        rookCol = col;
        break;
      }
    }
    if (rookCol !== undefined) {
      // Place rook on f-file (next to king's final position)
      updateBoardPosition(rankIndex, rookCol, rankIndex, toCol - 1, piece === 'K' ? 'R' : 'r');
    }
  } 
  // Queenside castling (king moving towards a-file)
  else {
    // Find the leftmost rook
    for (let col = 0; col < fromCol; col++) {
      if (board.value[rankIndex][col]?.toLowerCase() === 'r') {
        rookCol = col;
        break;
      }
    }
    if (rookCol !== undefined) {
      // Place rook on d-file (next to king's final position)
      updateBoardPosition(rankIndex, rookCol, rankIndex, toCol + 1, piece === 'K' ? 'R' : 'r');
    }
  }
};

/**
 * Convert algebraic notation to board coordinates
 * @param {string} square - Square in algebraic notation (e.g., 'h1')
 * @returns {Array} [row, col] coordinates
 */
const getRookPosition = (square) => {
  const files = 'abcdefgh';
  const ranks = '87654321';
  const col = files.indexOf(square[0]);
  const row = ranks.indexOf(square[1]);
  return [row, col];
};

/**
 * Update board position by moving a piece
 * @param {number} fromRow - Starting row
 * @param {number} fromCol - Starting column
 * @param {number} toRow - Target row
 * @param {number} toCol - Target column
 * @param {string} piece - Piece to move
 */
const updateBoardPosition = (fromRow, fromCol, toRow, toCol, piece) => {
  board.value[fromRow][fromCol] = '';
  board.value[toRow][toCol] = piece;
};

/**
 * Update board state from FEN notation
 * @param {string} fen - FEN string representing board position
 */
const updateBoardFromFen = (fen) => {
  if (!fen) {
    console.warn('No FEN provided to updateBoardFromFen');
    return;
  }
  
  console.log('Updating board with FEN:', fen);
  // Parse FEN position part (before first space)
  const position = fen.split(' ')[0].split('/').map(row => 
    row.split('').reduce((acc, char) => {
      if (isNaN(char)) {
        acc.push(char);
      } else {
        acc.push(...Array(parseInt(char)).fill(''));
      }
      return acc;
    }, [])
  );
  
  // Update board with proper perspective
  board.value = props.playerColor === 'black' ? [...position].reverse() : [...position];
  console.log('Board updated:', board.value);
};

/**
 * Clean up Socket.IO connection on component unmount
 */
onUnmounted(() => {
  if (socket.value) {
    socket.value.disconnect();
  }
});

/**
 * Watch for game ID changes and rejoin game if needed
 */
watch(() => props.gameId, (newGameId) => {
  if (newGameId && socketReady.value && socket.value) {
    socket.value.emit('join_game', {
      gameId: newGameId,
      color: props.playerColor
    });
  }
});

// Expose methods for parent component use
defineExpose({
  updateBoardFromFen
});
</script>

<style scoped>
/**
 * Chess Board Component Styles
 * 
 * Provides window-style container with chess board layout,
 * piece styling, and interactive elements for drag and drop.
 */

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
  -webkit-user-drag: element;
  transition: transform 0.1s ease-in-out;
}

.chess-piece:active {
  cursor: grabbing;
  transform: scale(1.1);
}
</style>