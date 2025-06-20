<template>
  <!-- Main game view with chess board and player information -->
  <div class="game-container">
    <!-- Chess board component (shown when game starts) -->
    <div class="game-area" v-show="isGameStarted">
      <ChessBoard 
        ref="chessBoardRef"
        :player-color="playerColor || 'white'"
        :game-id="gameId || ''"
        @game-over="onGameOver"
      />
    </div>

    <!-- Matchmaking status message -->
    <div v-show="!isGameStarted" class="searching-message">
      Searching for opponent...
    </div>
    
    <!-- Player information windows (shown when game starts) -->
    <template v-if="isGameStarted">
      <!-- Opponent information window (left side) -->
      <div class="opponent-container">
        <div class="window-container player-window opponent-window">
          <div class="window-header">
            <div class="window-title">{{ opponent?.username || 'Opponent' }}</div>
            <div class="timer">{{ formatTime(opponentTime) }}</div>
          </div>
          <div class="window-content profile-content">
            <div class="profile-image">
              <img src="../../../img/default-avatar.png" alt="Opponent" class="avatar">
            </div>
            <div class="profile-info">
              <h2 class="username">{{ opponent?.username || 'Waiting...' }}</h2>
              <div class="elo-rating">ELO: {{ opponent?.elo || '---' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Current player information window (right side) -->
      <div class="player-container">
        <div class="window-container player-window">
          <div class="window-header">
            <div class="window-title">You</div>
            <div class="timer">{{ formatTime(playerTime) }}</div>
          </div>
          <div class="window-content profile-content">
            <div class="profile-image">
              <img src="../../../img/default-avatar.png" alt="You" class="avatar">
            </div>
            <div class="profile-info">
              <h2 class="username">{{ userData?.username || 'Loading...' }}</h2>
              <div class="elo-rating">ELO: {{ userData?.elo || '1200' }}</div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Game over modal with result display -->
    <div v-if="showGameOverModal" class="modal-overlay">
      <div class="window-container game-over-window">
        <div class="window-header">
          <div class="window-title">Game Over</div>
        </div>
        <div class="window-content game-over-content">
          <h2 class="game-over-title">{{ gameOverMessage }}</h2>
          <p class="game-over-status">Status: {{ gameOverData.status }}</p>
          <button @click="closeGameOverModal" class="close-button">Return to Hub</button>
        </div>
      </div>
    </div>

    <!-- Bottom navigation bar -->
    <NavigationBar @home="handleHome" :showTimer="false" class="footer-nav" />
  </div>
</template>

<script setup>
/**
 * Game View Component
 * 
 * Handles the complete chess game experience including matchmaking,
 * game state management, player information display, and game over handling.
 * Manages the transition from searching for opponents to active gameplay.
 */

import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { useRouter } from 'vue-router';
import ChessBoard from '@/components/ChessBoard.vue';
import NavigationBar from '@/components/NavigationBar.vue';
import { useUser } from '@/composables/useUser';

const router = useRouter();
const { userData } = useUser();

// Component references and reactive state
const chessBoardRef = ref(null);
const opponent = ref(null);
const gameId = ref('');  // Initialize as empty string
const playerColor = ref('white');  // Initialize with default color
let matchmakingInterval;

// Game state and timing
const playerTime = ref(600);
const opponentTime = ref(600);
const isGameStarted = ref(false);
const showGameOverModal = ref(false);
const gameOverData = ref(null);
const gameCompleted = ref(false);

/**
 * Computed property for game over message based on result
 */
const gameOverMessage = computed(() => {
  if (!gameOverData.value) return '';
  const { status, winnerId } = gameOverData.value;
  if (status === 'draw') {
    return "It's a Draw!";
  }
  if (winnerId === userData.value?.id) {
    return 'You Won!';
  }
  return 'You Lost!';
});

/**
 * Format time in seconds to MM:SS display format
 * @param {number} seconds - Time in seconds
 * @returns {string} Formatted time string
 */
const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

/**
 * Handle game over event from chess board
 * @param {Object} data - Game over data from server
 */
const onGameOver = (data) => {
  gameOverData.value = data;
  showGameOverModal.value = true;
  gameCompleted.value = true;
};

/**
 * Close game over modal and handle game completion
 * Sends game end data to server and navigates back to hub
 */
const closeGameOverModal = async () => {
  showGameOverModal.value = false;
  if (gameOverData.value) {
    try {
      await fetch('http://localhost/php/endGame.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          gameId: gameOverData.value.gameId,
          status: gameOverData.value.status,
          winnerId: gameOverData.value.winnerId
        })
      });
    } catch (error) {
      console.error('Failed to end game:', error);
    }
  }
  router.push('/hub');
};

/**
 * Start matchmaking process by joining the queue
 */
const startMatchmaking = async () => {
  try {
    const response = await fetch('http://localhost/php/joinQueue.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        userId: userData.value?.id,
        elo: userData.value?.elo || 1200
      })
    });
    
    const data = await response.json();
    if (data.status === 'success') {
      matchmakingInterval = setInterval(checkForMatch, 2000);
    }
  } catch (error) {
    console.error('Failed to join queue:', error);
    router.push('/hub');
  }
};

/**
 * Check for available matches in the queue
 * Handles match found scenario and game initialization
 */
const checkForMatch = async () => {
  try {
    const response = await fetch('http://localhost/php/checkMatch.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: userData.value?.id })
    });
    
    const data = await response.json();
    console.log('Match check response:', data);

    if (data.status === 'matched') {
      // Stop matchmaking interval when match is found
      if (matchmakingInterval) {
        clearInterval(matchmakingInterval);
        matchmakingInterval = null;
      }

      // Remove player from queue
      await fetch('http://localhost/php/leaveQueue.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: userData.value?.id })
      });

      // Initialize game state with match data
      gameId.value = String(data.gameId);
      opponent.value = data.opponent;
      playerColor.value = data.isWhite ? 'white' : 'black';
      isGameStarted.value = true;
      
      await nextTick();

      // Initialize chess board with starting position
      if (data.fen) {
        console.log('Initializing board with FEN:', data.fen);
        if (chessBoardRef.value) {
          await chessBoardRef.value.updateBoardFromFen(data.fen);
        } else {
          console.error('Chess board ref not found');
        }
        return; // Exit function after initializing board
      }
      
      try {
        const gameResponse = await fetch('http://localhost:8000/chess/game/start', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ variant: 'chess960' })
        });
        
        if (!gameResponse.ok) {
          throw new Error(`HTTP error! status: ${gameResponse.status}`);
        }
        
        const gameData = await gameResponse.json();
        
        await fetch('http://localhost/php/updateGameFen.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            gameId: gameId.value,
            fen: gameData.fen 
          })
        });
        
        if (chessBoardRef.value) {
          console.log('Initializing board with new FEN:', gameData.fen);
          await chessBoardRef.value.updateBoardFromFen(gameData.fen);
        }
      } catch (error) {
        console.error('Failed to get/update initial position:', error);
      }
    }
  } catch (error) {
    console.error('Failed to check match:', error);
  }
};

const cancelMatchmaking = async () => {
  try {
    await fetch('http://localhost/php/leaveQueue.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: userData.value?.id })
    });
    clearInterval(matchmakingInterval);
    router.push('/hub');
  } catch (error) {
    console.error('Failed to leave queue:', error);
  }
};

const handleHome = () => router.push('/');

const handleGameEnd = async () => {
  if (!gameId.value || !userData.value?.id) return;
  
  // Don't mark as abandoned if game is already completed
  if (gameCompleted.value || showGameOverModal.value || gameOverData.value) {
    console.log('Game already completed, not marking as abandoned');
    return;
  }
  
  try {
    const response = await fetch('http://localhost/php/endGame.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        gameId: gameId.value,
        userId: userData.value.id,
        status: 'abandoned'
      })
    });
    
    const data = await response.json();
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}, message: ${data.message}`);
    }
    
    console.log('Game end response:', data);
  } catch (error) {
    console.error('Failed to end game:', error);
  }
};

const handleUnload = (event) => {
  if (isGameStarted.value) {
    // Show confirmation dialog
    event.preventDefault();
    event.returnValue = '';
    
    // End the game
    handleGameEnd();
  }
};

onMounted(() => {
  if (userData.value?.id) {
    startMatchmaking();
  } else {
    router.push('/');
  }
  
  // Add unload event listener
  window.addEventListener('beforeunload', handleUnload);
});

onUnmounted(() => {
  if (matchmakingInterval) {
    clearInterval(matchmakingInterval);
    matchmakingInterval = null;
    cancelMatchmaking();
  }
  
  // Remove unload event listener
  window.removeEventListener('beforeunload', handleUnload);
  
  // End game if it's still active
  if (isGameStarted.value && opponent.value) {
    handleGameEnd();
  }
});
</script>

<style scoped>
.game-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
  padding-bottom: 80px; /* Space for footer nav */
  box-sizing: border-box;
  position: relative;
}

.game-area {
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}

.opponent-container {
  position: fixed;
  top: 20px;
  left: 20px;
  z-index: 10;
}

.player-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10;
}

.player-window {
  width: 250px;
}

.opponent-window {
  margin-bottom: 0;
}

.window-container {
  background-color: #2a2a2a;
  border: 1px solid #9370DB;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

.window-content {
  padding: 15px;
  background-color: #2a2a2a;
}

.profile-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
}

.profile-image {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #9370DB;
}

.avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-info {
  text-align: center;
  color: #ffffff;
}

.username {
  margin: 0;
  color: #9370DB;
  font-size: 1.2rem;
}

.elo-rating {
  margin-top: 0.5rem;
  font-size: 1rem;
  color: #ffffff;
}

.timer {
  font-size: 18px;
  font-weight: bold;
  color: #ffffff;
}

.searching-message {
  font-size: 24px;
  color: #fff;
  text-align: center;
  margin-top: 50px;
}

.footer-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.game-over-window {
  width: 320px;
  text-align: center;
  border: 1px solid #9370DB;
  border-radius: 8px;
  background: #2a2a2a;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  overflow: hidden;
}

.game-over-content {
  padding: 1.5rem;
  background: #2a2a2a;
}

.game-over-title {
  font-size: 24px;
  margin-bottom: 10px;
  color: #9370DB;
}

.game-over-status {
  margin-bottom: 20px;
  color: #fff;
}

.close-button {
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
  border-radius: 5px;
  border: 1px solid #ccc;
  background-color: #f0f0f0;
}
</style>