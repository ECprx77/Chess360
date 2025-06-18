<template>
  <div class="game-container">

    <img class="side-img left" src="../../../img/Chess360.png"/>
    <img class="side-img right" src="../../../img/Chess360.png"/>

    <div class="game-layout">
      <!-- Chess Board -->
      <ChessBoard ref="chessBoardRef"/>
      <div class="players-container">
        <!-- Opponent Info Window -->
        <div class="window-container player-window opponent-window">
          <div class="window-header">
            <div class="window-title">Opponent</div>
          </div>
          <div class="window-content profile-content">
            <div class="profile-image">
              <img src="../../../img/default-avatar.png" alt="Opponent" class="avatar">
            </div>
            <div class="profile-info">
              <h2 class="username">ChessWizard</h2>
              <div class="elo-rating">ELO: 1820</div>
            </div>
          </div>
        </div>

        <!-- Player Info Window -->
        <div class="window-container player-window">
          <div class="window-header">
            <div class="window-title">You</div>
          </div>
          <div class="window-content profile-content">
            <div class="profile-image">
              <img src="../../../img/default-avatar.png" alt="You" class="avatar">
            </div>
            <div class="profile-info">
              <h2 class="username">GrandMaster_Flash</h2>
              <div class="elo-rating">ELO: 1850</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <NavigationBar @home="handleHome" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import ChessBoard from '@/components/ChessBoard.vue';
import NavigationBar from '@/components/NavigationBar.vue';

const router = useRouter();
const chessBoardRef = ref(null);

const handleHome = () => router.push('/');

const resetGame = async () => {
  if (chessBoardRef.value) {
    await chessBoardRef.value.initializeGame();
  }
};
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
  margin-left: auto;
  color: white;
  font-size: 1.2rem;
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
</style>