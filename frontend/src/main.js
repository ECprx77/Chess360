/**
 * Chess360 Frontend Application Entry Point
 * 
 * This file initializes the Vue 3 application with Vue Router for navigation
 * between different views: Login, Hub (main menu), and Game.
 */

import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import GameView from './views/GameView.vue'
import LoginView from './views/LoginView.vue'
import HubView from './views/HubView.vue'

// Define application routes
const routes = [
  { 
    path: '/', 
    name: 'Login',
    component: LoginView 
  },
  { 
    path: '/game', 
    name: 'Game',
    component: GameView,
    meta: { requiresAuth: true }  // Requires authentication
  },
  { 
    path: '/hub', 
    name: 'Gamehub',
    component: HubView,
    meta: { requiresAuth: true }  // Requires authentication
  }
]

// Create Vue Router instance with HTML5 history mode
const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for debugging and future auth implementation
router.beforeEach((to, from, next) => {
  console.log('Navigating from:', from.path, 'to:', to.path);
  next();
});

// Initialize and mount Vue application
const app = createApp(App)
app.use(router)
app.mount('#app')