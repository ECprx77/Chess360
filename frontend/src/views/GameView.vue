<template>
  <div class="game-container">
    <div class="game-board">
      <div class="game-view">
        <ChessBoard :playerColor="'white'" />
      </div>
    </div>

    <!-- Friends Modal -->
    <div v-if="showFriendsModal" class="modal-overlay">
      <div class="window-container">
        <div class="window-header">
          <div class="window-title">Friends List</div>
          <div class="window-controls">
            <button class="control-button close" @click="showFriendsModal = false">×</button>
          </div>
        </div>
        <div class="window-content">
          <ul class="friends-list">
            <li v-for="friend in friends" :key="friend.id" class="friend-item">
              {{ friend.name }}
              <button class="invite-button">Invite</button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Profile Modal -->
    <div v-if="showProfileModal" class="modal-overlay">
      <!-- Profile Info Window -->
      <div class="window-container profile-window">
        <div class="window-header">
          <div class="window-title">Profile</div>
          <div class="window-controls">
            <button class="control-button close" @click="showProfileModal = false">×</button>
          </div>
        </div>
        <div class="window-content profile-content">
          <div class="profile-image">
            <img src="../../../img/default-avatar.png" alt="Profile" class="avatar">
          </div>
          <div class="profile-info">
            <h2 class="username">GrandMaster_Flash</h2>
            <div class="elo-rating">ELO: 1850</div>
          </div>
        </div>
      </div>

      <!-- Match History Window -->
      <div class="window-container history-window">
        <div class="window-header">
          <div class="window-title">Match History</div>
          <div class="window-controls">
            <button class="control-button minimize">−</button>
          </div>
        </div>
        <div class="window-content">
          <ul class="match-list">
            <li v-for="match in matchHistory" :key="match.id" class="match-item">
              <div class="match-players">
                <span :class="{'current-player': match.player1 === 'GrandMaster_Flash'}">
                  {{ match.player1 }} ({{ match.elo1 }})
                </span>
                <span class="vs">vs</span>
                <span :class="{'current-player': match.player2 === 'GrandMaster_Flash'}">
                  {{ match.player2 }} ({{ match.elo2 }})
                </span>
              </div>
              <div class="match-result" :class="match.winner">
                {{ match.winner === 'win' ? 'Victory' : 'Defeat' }}
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="bottom-bar">
      <button class="nav-btn" @click="handleHome">
        <img src="../../../img/home.png" alt="Home" class="icon">
      </button>
      <button class="nav-btn" @click="showProfileModal = true">
        <img src="../../../img/profile.png" alt="Profile" class="icon">
      </button>
      <button class="nav-btn" @click="showFriendsModal = true">
        <img src="../../../img/friends.png" alt="friends" class="icon">
      </button>
      <div class="timer">{{ formatTime(timeLeft) }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import ChessBoard from '@/components/ChessBoard.vue';

const router = useRouter();
const timeLeft = ref(600); 
let timerInterval;

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const handleHome = () => router.push('/');

const startTimer = () => {
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
    }
  }, 1000);
};

onMounted(() => {
  startTimer();
});

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
});

const showFriendsModal = ref(false); 
const friends = ref([
  { id: 1, name: 'Alice' },
  { id: 2, name: 'Bob' },
  { id: 3, name: 'Charlie' },
  { id: 4, name: 'Diana' },
  { id: 5, name: 'Eve' }
]);

const showProfileModal = ref(false);
const matchHistory = ref([
  {
    id: 1,
    player1: 'GrandMaster_Flash',
    player2: 'ChessWizard',
    elo1: 1850,
    elo2: 1820,
    winner: 'win'
  },
  {
    id: 2,
    player1: 'KnightRider',
    player2: 'GrandMaster_Flash',
    elo1: 1780,
    elo2: 1840,
    winner: 'loss'
  },
  {
    id: 3,
    player1: 'GrandMaster_Flash',
    player2: 'PawnStars',
    elo1: 1840,
    elo2: 1900,
    winner: 'win'
  },
  {
    id: 4,
    player1: 'BishopBash',
    player2: 'GrandMaster_Flash',
    elo1: 1860,
    elo2: 1830,
    winner: 'win'
  },
  {
    id: 5,
    player1: 'GrandMaster_Flash',
    player2: 'QueenQuest',
    elo1: 1850,
    elo2: 1840,
    winner: 'loss'
  }
]);
</script>

<style scoped>
.game-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden; 
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
  margin-left: auto;
  color: white;
  font-size: 1.2rem;
  font-weight: bold;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  min-width: 300px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.game-modes, .time-controls {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.modal-btn {
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  border: none;
  background-color: #4a90e2;
  color: white;
  cursor: pointer;
}

.close-btn {
  padding: 0.5rem;
  border: none;
  background-color: #e74c3c;
  color: white;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 1rem;
}

.modal-btn:hover, .close-btn:hover {
  opacity: 0.9;
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

.friends-list {
  list-style: none;
  width: 400px;
  padding: 0;
  margin: 0;
}

.friend-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #4a4a4a;
  color: #ffffff;
}

.friend-item:last-child {
  border-bottom: none;
}

.invite-button {
  background-color: #9370DB;
  border: none;
  color: #ffffff;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.invite-button:hover {
  background-color: #7a5fbf;
}

.profile-window {
  width: 300px;
  position: fixed;
  top: 5%;
  left: 30%;
  transform: translateX(-50%);
}

.history-window {
  width: 500px;
  position: fixed;
  top: 35%;
  left: 50%;
  transform: translateX(-50%);
}

.profile-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
}

.profile-image {
  width: 120px;
  height: 120px;
  border-radius: 60px;
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
  color: white;
}

.username {
  margin: 0;
  color: #9370DB;
  font-size: 1.5rem;
}

.elo-rating {
  margin-top: 0.5rem;
  font-size: 1.2rem;
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
</style>