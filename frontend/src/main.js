import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import GameView from './views/GameView.vue'
import LoginView from './views/LoginView.vue'
import HubView from './views/HubView.vue'

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
    meta: { requiresAuth: true }
  },
  { 
    path: '/hub', 
    name: 'Gamehub',
    component: HubView,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})


router.beforeEach((to, from, next) => {
  console.log('Navigating from:', from.path, 'to:', to.path);
  next();
});

const app = createApp(App)
app.use(router)
app.mount('#app')