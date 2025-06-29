<template>
  <!-- Main hub view with desktop-style layout -->
  <div class="game-container">
    <!-- Decorative background images -->
    <img class="side-img left" src="../../../img/Chess360.png"/>
    <img class="side-img center" src="../../../img/Chess360.png"/>
    <img class="side-img right" src="../../../img/Chess360.png"/>

    <!-- Player profile window positioned on the right -->
    <div class="players-container">
      <div class="window-container player-window">
        <div class="window-header">
          <div class="window-title">You</div>
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

    <!-- Main game area with draggable game icon -->
    <div class="game-area">
      <GameIcon :initial-x="0" :initial-y="0" />
    </div>

    <!-- Bottom navigation bar -->
    <NavigationBar @home="handleHome" class="footer-nav" />
  </div>
</template>

<script setup>
/**
 * Hub View Component
 * 
 * Main application hub that provides a desktop-style interface
 * with user profile display, draggable game icon, and navigation.
 * Serves as the central landing page after login.
 */

import { useRouter } from 'vue-router';
import NavigationBar from '@/components/NavigationBar.vue';
import GameIcon from '@/components/GameIcon.vue';
import { useUser } from '@/composables/useUser';

const router = useRouter();
const { userData } = useUser();

/**
 * Handle navigation back to login page
 */
const handleHome = () => router.push('/');
</script>

<style scoped>
/**
 * Hub View Styles
 * 
 * Provides desktop-style layout with decorative elements,
 * user profile window, and game area positioning.
 */

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

.center {
  left: 48%;
  transform: translateX(-50%);
}

.right {
  right: -172px;
}

.game-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
  padding-bottom: 80px; /* Space for footer navigation */
}

.game-area {
  position: absolute;
  left: 15px;
  top: 15px;
  width: 80vw;
  height: 88vh;
  margin: 0 auto;
  border-radius: 8px;
  overflow: hidden;
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
  color: #2a2a2a;
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

.player-window {
  width: 250px;
  background-color: #2a2a2a;
}

.players-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.footer-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
}
</style>