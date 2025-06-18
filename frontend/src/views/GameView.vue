<template>
  <div class="game-container">
    <!-- Only show overlay when no opponent is found -->
    <div v-if="!opponentFound" class="matchmaking-overlay">
      <div class="matchmaking-content">
        <h2>Finding opponent...</h2>
        <p>Searching for players (ELO: {{ userData?.elo || '1200' }} ±100)</p>
        <button @click="cancelMatchmaking" class="cancel-button">Cancel</button>
      </div>
    </div>

    <!-- Show game content only when opponent is found -->
    <template v-else>
      <img class="side-img left" src="../../../img/Chess360.png"/>
      <img class="side-img right" src="../../../img/Chess360.png"/>

      <div class="game-layout">
        <!-- Chess Board -->
        <ChessBoard ref="chessBoardRef"/>
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
                <button 
                  v-if="opponent && !isFriend && !isPending" 
                  @click="sendFriendRequest" 
                  class="befriend-button"
                >
                  Add Friend
                </button>
                <span v-else-if="isPending" class="pending-text">Friend Request Sent</span>
                <span v-else-if="isFriend" class="friend-text">Friends</span>
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
      </div>

      <NavigationBar @home="handleHome" />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watchEffect } from 'vue';
import { useRouter } from 'vue-router';
import { useUser } from '@/composables/useUser';
import ChessBoard from '@/components/ChessBoard.vue';
import NavigationBar from '@/components/NavigationBar.vue';

const router = useRouter();
const { userData } = useUser();
const chessBoardRef = ref(null);
const opponentFound = ref(false);
const opponent = ref(null);
const gameId = ref(null);  // Add this line to define gameId ref

const playerTime = ref(600);
const opponentTime = ref(600);
let playerTimer;
let opponentTimer;

const isFriend = ref(false);
const isPending = ref(false);
let matchmakingInterval;

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const startTimers = () => {
  playerTimer = setInterval(() => {
    if (playerTime.value > 0) {
      playerTime.value--;
    }
  }, 1000);

  opponentTimer = setInterval(() => {
    if (opponentTime.value > 0) {
      opponentTime.value--;
    }
  }, 1000);
};

onMounted(() => {
  startTimers();
});

onUnmounted(() => {
  clearInterval(playerTimer);
  clearInterval(opponentTimer);
});

const handleHome = () => router.push('/hub');

const checkFriendshipStatus = async () => {
  if (!opponent.value?.id || !userData.value?.id) return;
  
  try {
    const response = await fetch('http://localhost/php/checkFriendship.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        userId: userData.value.id,
        targetId: opponent.value.id
      })
    });
    const data = await response.json();
    isFriend.value = data.status === 'friends';
    isPending.value = data.status === 'pending';
  } catch (error) {
    console.error('Failed to check friendship status:', error);
  }
};

const sendFriendRequest = async () => {
  if (!opponent.value?.id || !userData.value?.id) return;

  try {
    const response = await fetch('http://localhost/php/sendFriendRequest.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        senderId: userData.value.id,
        receiverId: opponent.value.id
      })
    });
    const data = await response.json();
    if (data.status === 'success') {
      isPending.value = true;
      // Ready for Socket.IO - Emit event for real-time notification
      // socket.emit('friendRequest', { 
      //   from: userData.value.id, 
      //   to: opponent.value.id 
      // });
    }
  } catch (error) {
    console.error('Failed to send friend request:', error);
  }
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
      // Start polling for match
      matchmakingInterval = setInterval(checkForMatch, 2000);
    } else {
      throw new Error(data.message || 'Failed to join queue');
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
      body: JSON.stringify({
        userId: userData.value?.id
      })
    });
    
    const data = await response.json();
    if (data.status === 'matched') {
      clearInterval(matchmakingInterval);
      opponentFound.value = true;  // This will hide the loading screen
      opponent.value = data.opponent;
      gameId.value = data.gameId;  // Store the game ID for future use
      initializeGame();  // Add this function to set up the game
    }
  } catch (error) {
    console.error('Failed to check match:', error);
  }
};

const initializeGame = () => {
  // Initialize chess board and game state
  if (chessBoardRef.value) {
    chessBoardRef.value.initializeGame();
  }
};

const cancelMatchmaking = async () => {
  try {
    await fetch('http://localhost/php/leaveQueue.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        userId: userData.value?.id
      })
    });
    clearInterval(matchmakingInterval);
    router.push('/hub');
  } catch (error) {
    console.error('Failed to leave queue:', error);
  }
};

watchEffect(() => {
  if (opponent.value?.id) {
    checkFriendshipStatus();
  }
});

// Later for Socket.IO implementation:
// onMounted(() => {
//   socket.on('opponentJoined', (opponentData) => {
//     opponent.value = opponentData;
//   });
// });

onMounted(() => {
  if (userData.value?.id) {
    startMatchmaking();
  } else {
    router.push('/'); // Redirect to login if no user data
  }
});
</script>

<style scoped>

.side-img {
  position: absolute;
  width: 343px;
  height: 343px;
  top: 25%;
  z-index: 1;
}
.left {
  left: -172px;
}
.right {
  right: -172px;
}

.game-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
}

.game-layout {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
}

.game-board {
  flex: 1;
  background-color: var(--bg-color);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
}

.game-view {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.bottom-bar {
  height: 60px;
  background-color: var(--primary-color);
  display: flex;
  align-items: center;
  padding: 0  1rem;
  gap: 1rem;
  bottom: 50px
}

.nav-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
}

.icon {
  width: 40px;
  height: 40px;
}

.timer {
  color: #ffffff;
  font-size: 14px;
  font-weight: bold;
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

.window-controls {
  display: flex;
}

.control-button {
  background: none;
  border: none;
  color: #7a5fbf;
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
  color: #2a2a2a
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

.match-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.match-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #4a4a4a;
  color: #ffffff;
}

.match-players {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.vs {
  color: #666;
}

.current-player {
  color: #9370DB;
  font-weight: bold;
}

.match-result {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.9rem;
}

.match-result.win {
  background-color: #4CAF50;
}

.match-result.loss {
  background-color: #f44336;
}

.game-layout {
  display: flex;
  gap: 20px;
  justify-content: center;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
}

.player-window {
  width: 250px;
}

.opponent-window {
  margin-bottom: 20px;
}

.players-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.reset-btn:hover {
  background-color: rgba(147, 112, 219, 0.2);
  border-radius: 4px;
}

.befriend-button {
  margin-top: 10px;
  padding: 8px 16px;
  background-color: #9370DB;
  border: none;
  border-radius: 4px;
  color: white;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background-color 0.2s;
}

.befriend-button:hover {
  background-color: #7a5fbf;
}

.pending-text {
  margin-top: 10px;
  color: #ffd700;
  font-size: 0.9rem;
}

.friend-text {
  margin-top: 10px;
  color: #4CAF50;
  font-size: 0.9rem;
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
</style>