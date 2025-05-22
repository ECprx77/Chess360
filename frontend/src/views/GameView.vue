<template>
  <div class="game-container">
    <div class="game-board">
      <!-- Chess board will go here -->
    </div>
    
    <div class="bottom-bar">
      <button class="nav-btn">
        <img src="/chess-logo.png" alt="Home" class="icon">
      </button>
      <button class="nav-btn">
        <img src="/profile.png" alt="Profile" class="icon">
      </button>
      <button class="nav-btn">
        <img src="/multiplayer.png" alt="Multiplayer" class="icon">
      </button>
      <div class="timer">00:00</div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.game-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.game-board {
  flex: 1;
  background-color: var(--bg-color);
}

.bottom-bar {
  height: 60px;
  background-color: var(--primary-color);
  display: flex;
  align-items: center;
  padding: 0 1rem;
  gap: 1rem;
}

.nav-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
}

.icon {
  width: 24px;
  height: 24px;
}

.timer {
  margin-left: auto;
  color: white;
  font-size: 1.2rem;
  font-weight: bold;
}
</style>