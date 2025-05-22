<template>
  <div class="login-container">
    <div class="logo">
      <h1>Chess360</h1>
      <img src="/chess-logo.png" alt="Chess Logo" class="chess-icon">
    </div>
    <div class="login-form">
      <input type="email" placeholder="Email..." v-model="email">
      <input type="password" placeholder="Password..." v-model="password">
      
      <div class="buttons">
        <button @click="login" class="btn">Login</button>
        <button @click="register" class="btn">Register</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')

const login = () => {
  // Add login logic here
  router.push('/game')
}

const register = () => {
  // Add registration logic here
}
</script>

<style scoped>
.login-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
}

.logo {
  text-align: center;
  margin-bottom: 2rem;
}

.logo h1 {
  color: var(--primary-color);
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.chess-icon {
  width: 60px;
  height: 60px;
}

.login-form {
  background-color: var(--primary-color);
  padding: 2rem;
  border-radius: 15px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 300px;
}

input {
  padding: 0.8rem;
  border: none;
  border-radius: 5px;
  background-color: white;
}

.buttons {
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.btn {
  padding: 0.8rem 1.5rem;
  border: none;
  border-radius: 5px;
  background-color: white;
  color: var(--primary-color);
  cursor: pointer;
  font-weight: bold;
  transition: opacity 0.2s;
}

.btn:hover {
  opacity: 0.9;
}
</style>