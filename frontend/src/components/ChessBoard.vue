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
      <div class="chess-board" :class="{ 'black-perspective': isBlackPlayer }">
        <div v-for="(row, rowIndex) in board" :key="rowIndex" class="board-row">
          <div v-for="(piece, colIndex) in row" 
               :key="colIndex" 
               class="board-cell"
               :class="[getCellColor(rowIndex, colIndex), { 'legal-move': isLegalMove(rowIndex, colIndex) }]"
               @dragenter.prevent
               @dragover.prevent
               @drop="handleDrop($event, rowIndex, colIndex)">
            <div v-if="isLegalMove(rowIndex, colIndex)" class="move-indicator"></div>
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
import { ref, computed, defineExpose, defineProps, onMounted, onUnmounted, watch } from 'vue';
import { io } from 'socket.io-client';

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

// Create computed value for color checks using props
const isBlackPlayer = computed(() => props.playerColor === 'black');

// Initialize board with starting position
const isMyTurn = ref(props.playerColor === 'white');
const board = ref([]);
const draggedPiece = ref(null);
const legalMoves = ref([]);
const socket = ref(null);
const socketReady = ref(false);  // Add this flag

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

const getRanks = (isBlack = false) => {
  const ranks = '87654321';
  return isBlack ? ranks.split('').reverse().join('') : ranks;
};

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

const isCastlingMove = (moveUCI) => {
  const castlingMoves = {
    'e1g1': { from: 'h1', to: 'f1' }, // White kingside
    'e1c1': { from: 'a1', to: 'd1' }, // White queenside
    'e8g8': { from: 'h8', to: 'f8' }, // Black kingside
    'e8c8': { from: 'a8', to: 'd8' }  // Black queenside
  };
  return castlingMoves[moveUCI];
};

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

  // Check if this is a castling move
  const castling = isCastlingMove(moveUCI);
  if (castling) {
    const [rookFromRow, rookFromCol] = getRookPosition(castling.from);
    const [rookToRow, rookToCol] = getRookPosition(castling.to);
    handleCastling(rookFromRow, rookFromCol, rookToRow, rookToCol, draggedPiece.value.piece);
  }

  // Make optimistic update
  const newBoard = JSON.parse(JSON.stringify(board.value));
  newBoard[targetRow][targetCol] = draggedPiece.value.piece;
  newBoard[fromRow][fromCol] = '';
  board.value = newBoard;

  socket.value.emit('make_move', { move: moveUCI }, (response) => {
    console.log('Move response:', response);
    if (response?.error) {
      console.error('Move error:', response.error);
      // Move failed, revert the piece
      const revertBoard = JSON.parse(JSON.stringify(board.value));
      revertBoard[fromRow][fromCol] = draggedPiece.value.piece;
      revertBoard[targetRow][targetCol] = '';
      board.value = revertBoard;
    }
    draggedPiece.value = null;
    legalMoves.value = [];
  });
};

// Update socket event handlers in onMounted
onMounted(async () => {
  // Initialize Socket.IO connection first
  socket.value = io('http://localhost:8000', {
    transports: ['websocket', 'polling'],
    withCredentials: true
  });
  
  socket.value.on('connect', () => {
    console.log('Connected to game server, socket id:', socket.value.id);
    socketReady.value = true;
    
    if (props.gameId) {
      socket.value.emit('join_game', {
        gameId: props.gameId,
        color: props.playerColor
      });
    }
  });
  
  // Update the game_joined handler in onMounted
  socket.value.on('game_joined', (data) => {
    console.log('Joined game:', data);
    // Set initial turn based on color and game state
    isMyTurn.value = (props.playerColor === 'white' && data.is_white_turn) || 
                     (props.playerColor === 'black' && !data.is_white_turn);
    console.log(`Turn initialized: ${isMyTurn.value}, color: ${props.playerColor}, is_white_turn: ${data.is_white_turn}`);
    updateBoardFromFen(data.fen);
  });
  
  // Update the move_made handler
  socket.value.on('move_made', (data) => {
    console.log('Move made event received:', data);
    if (data.fen) {
      // Parse the FEN string into board position
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
      
      // Force reactive update with new array
      board.value = props.playerColor === 'black' ? [...position].reverse() : [...position];
      
      // Update turn
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

  socket.value.on('legal_moves', (data) => {
    console.log('Received legal moves:', data);
    if (data && Array.isArray(data.legal_moves)) {
      legalMoves.value = data.legal_moves;
    } else {
      console.error('Invalid legal moves response:', data);
      legalMoves.value = [];
    }
  });
});

const handlePieceClick = async (row, col) => {
  const piece = board.value[row][col];
  if (!piece) return;
  
  const isWhitePiece = piece === piece.toUpperCase();
  if ((props.playerColor === 'white' && !isWhitePiece) || 
      (props.playerColor === 'black' && isWhitePiece) ||
      !isMyTurn.value) {
    console.log('Not your turn or piece');
    return;
  }
  
  draggedPiece.value = { row, col, piece };
  const files = 'abcdefgh';
  const ranks = getRanks(props.playerColor === 'black');
  const square = files[col] + ranks[row];
  
  console.log('Getting legal moves for:', piece, 'at', square);
  socket.value.emit('get_legal_moves', { square });
};

const handleDragStart = async (event, row, col) => {
  const piece = board.value[row][col];
  const isWhitePiece = piece === piece.toUpperCase();
  
  if ((props.playerColor === 'white' && !isWhitePiece) || 
      (props.playerColor === 'black' && isWhitePiece) ||
      !isMyTurn.value) {
    event.preventDefault();
    return;
  }
  
  draggedPiece.value = { row, col, piece };
  event.dataTransfer.setData('text/plain', '');
  
  const files = 'abcdefgh';
  const ranks = getRanks(props.playerColor === 'black');
  const square = files[col] + ranks[row];
  
  console.log('Getting legal moves for:', piece, 'at', square);
  socket.value.emit('get_legal_moves', { square });
};

// Remove getLegalMovesAsync function since we're not using callbacks anymore
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
      // Always place rook on f-file (next to king's final position)
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
      // Always place rook on d-file (next to king's final position)
      updateBoardPosition(rankIndex, rookCol, rankIndex, toCol + 1, piece === 'K' ? 'R' : 'r');
    }
  }
};

const getRookPosition = (square) => {
  const files = 'abcdefgh';
  const ranks = '87654321';
  const col = files.indexOf(square[0]);
  const row = ranks.indexOf(square[1]);
  return [row, col];
};

const updateBoardPosition = (fromRow, fromCol, toRow, toCol, piece) => {
  board.value[fromRow][fromCol] = '';
  board.value[toRow][toCol] = piece;
};

const updateBoardFromFen = (fen) => {
  if (!fen) {
    console.warn('No FEN provided to updateBoardFromFen');
    return;
  }
  
  console.log('Updating board with FEN:', fen);
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
  
  // Force Vue to react to the board change
  board.value = props.playerColor === 'black' ? [...position].reverse() : [...position];
  console.log('Board updated:', board.value);
};

onUnmounted(() => {
  if (socket.value) {
    socket.value.disconnect();
  }
});

// Create a watch effect for gameId
watch(() => props.gameId, (newGameId) => {
  if (newGameId && socketReady.value && socket.value) {
    socket.value.emit('join_game', {
      gameId: newGameId,
      color: props.playerColor
    });
  }
});

defineExpose({
  updateBoardFromFen
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
  -webkit-user-drag: element;
  transition: transform 0.1s ease-in-out;
}

.chess-piece:active {
  cursor: grabbing;
  transform: scale(1.1);
}
</style>