<template>
  <div class="login-page">
    <div class="background-blur"/>


    <img class="side-img left" src="../../../img/Chess360.png"/>
    <img class="side-img right" src="../../../img/Chess360.png"/>

    <div class="header">
      <div class="title">Chess360</div>
      <img class="logo" src="../../../img/Chess360.png"/>
    </div>

    <div class="login-container">
      <img class="icon" src="../../../img/home.png"/>
      <form class="form-box" @submit.prevent="">
      <form class="form-box" @submit.prevent="">
        <input v-model="email" type="email" placeholder="Email" class="input-box" required/>
        <input v-model="password" type="password" placeholder="Password" class="input-box" required/>
        <div class="buttons">
          <button type="button" @click="handleLogin" class="button login">Login</button>
          <button type="button" @click="showRegisterPopup = true" class="button register">Register</button>
        </div> 
      </form>
    </div>

    <!-- Register Popup -->
    <div v-if="showRegisterPopup" class="popup-overlay">
      <div class="popup-container">
        <div class="popup-header">
          <h2>Create Account</h2>
          <button class="close-button" @click="showRegisterPopup = false">×</button>
        </div>
        <form class="popup-form" @submit.prevent="handleRegisterSubmit">
          <input v-model="registerData.username" type="text" placeholder="Username" required/>
          <input v-model="registerData.email" type="email" placeholder="Email" required/>
          <input v-model="registerData.password" type="password" placeholder="Password" required/>
          <input v-model="registerData.confirmPassword" type="password" placeholder="Confirm Password" required/>
          <button type="submit" class="register-button">Create Account</button>
        </form>
      </div>
    </div>

    <div class="footer-blur"/>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')

// refs for registration
const showRegisterPopup = ref(false)
const registerData = ref({
  username: '',
  email: '',
  password: '',
  confirmPassword: ''
})

async function handleLogin() {
  try {
    const response = await fetch('http://localhost/php/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: email.value,
        password: password.value
      })
    });

    const data = await response.json();

    if (data.status === 'success') {
      // Store user data in localStorage
      localStorage.setItem('userData', JSON.stringify({
        id: data.user.id,
        username: data.user.username,
        email: data.user.email,
        elo: data.user.elo || 1200 // Default ELO if not provided
      }));
      
      await router.push('/hub');
    } else {
      alert(data.message || 'Login failed');
    }
  } catch (error) {
    console.error('Login error:', error);
    alert('Login failed. Please check your connection and try again.');
  }
}

async function handleRegisterSubmit() {
  if (registerData.value.password !== registerData.value.confirmPassword) {
    alert('Passwords do not match!')
    return
  }

  try {
    const response = await fetch('http://localhost/php/register.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        username: registerData.value.username,
        email: registerData.value.email,
        password: registerData.value.password
      })
    });

    const data = await response.json();

    if (data.status === 'success') {
      alert('Registration successful! Please log in.');
      showRegisterPopup.value = false;
      email.value = registerData.value.email;
      password.value = registerData.value.password;
    } else {
      alert(data.message);
    }
  } catch (error) {
    console.error('Registration error:', error);
    alert('Registration failed. Please try again.');
  }
}
</script>

<style scoped>
.login-page {
  position: relative;
  width: 100vw;
  height: 100vh;
  background: #524D46;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.background-blur {
  position: absolute;
  width: 100%;
  height: 1000px;
  top: 0;
  left: 0;
  background: rgba(126, 126, 126, 0.2);
  backdrop-filter: blur(7.5px);
  z-index: 0;
}

.footer-blur {
  position: absolute;
  bottom: 0;
  width: 100%;

  height: 70px;
  background: #9875CD;
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
  box-shadow: 4px 4px 4px rgba(0, 0, 0, 0.25);
  filter: blur(2px);
}

.side-img {
  position: absolute;
  width: 343px;
  height: 343px;
  top: 25%;
  top: 25%;
  filter: blur(2px);
  z-index: 1;
}
.left {
  left: -172px;
}
.right {
  right: -172px;
}

.header {
  position: absolute;
  top: 17.5px;
  left: 50%;
  transform: translateX(-50%);
  width: 406.25px;
  height: 125px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 2;
}
.title {
  font-size: 50px;
  color: #9875CD;
  width: 281.25px;
  text-align: center;
}
.logo {
  width: 200px;
  height: 200px;
}

.login-container {
  position: absolute;
  top: 30%;
  top: 30%;
  left: 50%;
  transform: translateX(-50%);
  width: 406.25px;
  height: 300px; 
  background: #7C43D3;
  border-top-right-radius: 25px; 
  border-bottom-right-radius: 25px;
  border-bottom-left-radius: 25px;
  z-index: 2;
  padding-top: 50px; 
}

.icon {
  position: absolute;
  top: -70px;
  left: 0%;
  transform: translateX(-50%);
  width: 100px;
  height: 100px;
  z-index: 1;
}

.form-box {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  padding: 0 25px; 
}
.input-box {
  width: 100%;
  height: 56.25px;
  padding: 0 15px;
  margin-bottom: 25px;
  font-size: 18.75px; 
  justify-content: space-between;
  border: none;
  background: #D9D9D9;
  border-top-left-radius: 28px;
  border-top-right-radius: 28px;
  border-bottom-left-radius: 28px;
  color: #524D46;
}
.input-box:focus {
  outline: none;
}

.buttons {
  display: flex;
  justify-content: space-around;
  width: 100%;
  margin-top: 12.5px; /* 20 / 1.6 */
}
.button {
  width: 118.75px; /* 190 / 1.6 */
  height: 43.125px; /* 69 / 1.6 */
  background: #D9D9D9;
  border-radius: 12.5px; /* 20 / 1.6 */
  color: #9875CD;
  font-size: 18.75px; /* 30 / 1.6 */
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
  transition: background 0.3s ease;
}
.button:hover {
  background: #c0b4e0;
}

.popup-overlay {
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

.popup-container {
  width: 400px;
  background: #7C43D3;
  border-radius: 25px;
  padding: 20px;
  position: relative;
}

.popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.popup-header h2 {
  color: #D9D9D9;
  margin: 0;
}

.close-button {
  background: none;
  border: none;
  color: #D9D9D9;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
}

.popup-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.popup-form input {
  width: 90%;
  height: 56.25px;
  padding: 0 15px;
  font-size: 18.75px;
  border: none;
  background: #D9D9D9;
  border-radius: 28px;
  color: #524D46;
}

.popup-form input:focus {
  outline: none;
}

.register-button {
  width: 100%;
  height: 43.125px;
  background: #D9D9D9;
  border-radius: 12.5px;
  color: #9875CD;
  font-size: 18.75px;
  border: none;
  cursor: pointer;
  transition: background 0.3s ease;
}

.register-button:hover {
  background: #c0b4e0;
}
</style>
