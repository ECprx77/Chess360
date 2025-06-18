<template>
  <div class="game-container">
    <!-- Show loading state while matchmaking -->
    <div v-if="!opponentFound" class="matchmaking-overlay">
      <div class="matchmaking-content">
        <h2>Finding opponent...</h2>
        <p>Searching for players (ELO: {{ userData?.elo || '1200' }} ±100)</p>
        <button @click="cancelMatchmaking" class="cancel-button">Cancel</button>
      </div>
    </div>

    <div class="players-container">
      <!-- Opponent Info Window -->
      <div class="window-container player-window opponent-window">
        <div class="window-header">
          <div class="window-title">{{ opponent?.username || 'Waiting...' }}</div>
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

    <div class="game-area">
      <ChessBoard ref="chessBoardRef" />
    </div>

    <NavigationBar @home="handleHome" :showTimer="false" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import ChessBoard from '@/components/ChessBoard.vue';
import NavigationBar from '@/components/NavigationBar.vue';
import { useUser } from '@/composables/useUser';

const router = useRouter();
const { userData } = useUser();
const chessBoardRef = ref(null);
const opponentFound = ref(false);
const opponent = ref(null);
let matchmakingInterval;

const playerTime = ref(600);
const opponentTime = ref(600);

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

const checkForMatch = async () => {
  try {
    const response = await fetch('http://localhost/php/checkMatch.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: userData.value?.id })
    });
    
    const data = await response.json();
    if (data.status === 'matched') {
      clearInterval(matchmakingInterval);
      opponentFound.value = true;
      opponent.value = data.opponent;
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

onMounted(() => {
  if (userData.value?.id) {
    startMatchmaking();
  } else {
    router.push('/');
  }
});

onUnmounted(() => {
  clearInterval(matchmakingInterval);
  cancelMatchmaking();
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

.matchmaking-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.matchmaking-content {
  text-align: center;
  color: white;
}

.cancel-button {
  margin-top: 20px;
  padding: 10px 20px;
  background-color: #f44336;
  border: none;
  border-radius: 4px;
  color: white;
  cursor: pointer;
}

.cancel-button:hover {
  background-color: #da190b;
}

.game-area {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>