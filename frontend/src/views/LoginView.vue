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
      <form class="form-box" @submit.prevent="handleLogin">
        <input v-model="email" type="email" placeholder="Email" class="input-box" required/>
        <input v-model="password" type="password" placeholder="Password" class="input-box" required/>
        <div class="buttons">
          <button type="submit" class="button login">Login</button>
          <button type="button" @click="handleRegister" class="button register">Register</button>
        </div> 
      </form>
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

async function handleLogin() {
  try {
    console.log('Login attempted');
    console.log('Current route:', router.currentRoute.value.path);
    const result = await router.push('/game');
    console.log('Navigation result:', result);
  } catch (error) {
    console.error('Navigation failed:', error);
  }
}

// async function handleLogin() {
//   try {
//     const response = await fetch('http://localhost/Chess360/backend/api/php/login.php', {
//       method: 'POST',
//       headers: {
//         'Content-Type': 'application/json',
//       },
//       body: JSON.stringify({
//         email: email.value,
//         password: password.value
//       })
//     });

//     const data = await response.json();

//     if (data.status === 'success') {
//       // Store user data in localStorage or Vuex store
//       localStorage.setItem('user_id', data.user_id);
//       router.push('/game'); // Redirect to game page
//     } else {
//       alert(data.message);
//     }
//   } catch (error) {
//     console.error('Login error:', error);
//     alert('Login failed. Please try again.');
//   }
// }

// async function handleRegister() {
//   try {
//     const response = await fetch('http://localhost/Chess360/backend/api/php/register.php', {
//       method: 'POST',
//       headers: {
//         'Content-Type': 'application/json',
//       },
//       body: JSON.stringify({
//         email: email.value,
//         password: password.value
//       })
//     });

//     const data = await response.json();
//     let tempmail = email.value;
//     let tempword = password.value;

//     if (data.status === 'success') {
//       alert('Registration successful! logging you in...');
//       try {
//         const response = await fetch('http://localhost/Chess360/backend/api/php/login.php', {
//           method: 'POST',
//           headers: {
//             'Content-Type': 'application/json',
//           },
//           body: JSON.stringify({
//             email: tempmail,
//             password: tempword
//           })
//         });

//         const data = await response.json();

//         if (data.status === 'success') {
//           email.value = '';
//           password.value = '';
//           localStorage.setItem('user_id', data.user_id);
//           router.push('/game');
//         } else {
//           alert(data.message);
//         }
//       } catch (error) {
//         console.error('Login error:', error);
//         alert('Login failed. Please try again.');
//       }

//     } else {
//       alert(data.message);
//     }
//   } catch (error) {
//     console.error('Registration error:', error);
//     alert('Registration failed. Please try again.');
//   }
// }
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
  top: 318px;
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
  top: 300px;
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
</style>
