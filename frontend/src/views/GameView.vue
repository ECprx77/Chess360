<template>
  <div class="game-container">
    <div class="game-area" v-show="isGameStarted">
      <ChessBoard 
        ref="chessBoardRef"
        :player-color="playerColor || 'white'"
        :game-id="gameId || ''"
      />
    </div>

    <div v-show="!isGameStarted" class="searching-message">
      Searching for opponent...
    </div>
    
    <template v-if="isGameStarted">
      <div class="players-container">
        <!-- Opponent Info Window -->
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

        <!-- Player Info Window -->
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

    <NavigationBar @home="handleHome" :showTimer="false" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import ChessBoard from '@/components/ChessBoard.vue';
import NavigationBar from '@/components/NavigationBar.vue';
import { useUser } from '@/composables/useUser';

const router = useRouter();
const { userData } = useUser();
const chessBoardRef = ref(null);
const opponent = ref(null);
const gameId = ref('');  // Initialize as empty string
const playerColor = ref('white');  // Initialize with default color
let matchmakingInterval;

const playerTime = ref(600);
const opponentTime = ref(600);
const isGameStarted = ref(false);

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

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

// Update the checkForMatch function
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
      // Clear interval immediately when match is found
      if (matchmakingInterval) {
        clearInterval(matchmakingInterval);
        matchmakingInterval = null;
      }

      // Remove from queue
      await fetch('http://localhost/php/leaveQueue.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: userData.value?.id })
      });

      // Set game state
      gameId.value = String(data.gameId);
      opponent.value = data.opponent;
      playerColor.value = data.isWhite ? 'white' : 'black';
      isGameStarted.value = true;
      
      await nextTick();

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
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
}

.players-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: fixed;
  right: 20px;
  top: 20px;
}

.window-container {
  background-color: #2a2a2a;
  border: 1px solid #9370DB;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.player-window {
  width: 250px;
}

.window-header {
  background-color: #333333;
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 10px;
  border-bottom: 1px solid #9370DB;
}

.window-title {
  color: #ffffff;
  font-size: 14px;
}

.window-content {
  padding: 15px;
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
}

.username {
  margin: 0;
  color: #9370DB;
  font-size: 1.2rem;
}

.elo-rating {
  margin-top: 0.5rem;
  color: #ffffff;
}

.timer {
  color: #ffffff;
  font-weight: bold;
}

.game-area {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.position-info {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #2a2a2a;
  padding: 8px 16px;
  border-radius: 4px;
  border: 1px solid #9370DB;
  color: white;
}

.searching-message {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: rgba(42, 42, 42, 0.9);
  padding: 20px 40px;
  border-radius: 8px;
  border: 1px solid #9370DB;
  color: #ffffff;
  font-size: 18px;
  font-weight: bold;
  z-index: 1000;
}
</style>