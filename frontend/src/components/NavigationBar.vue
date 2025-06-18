<template>
  <div>
    <div class="bottom-bar">
      <button class="nav-btn" @click="handleHome">
        <img src="../../../img/home.png" alt="Home" class="icon">
      </button>
      <button class="nav-btn" @click="toggleProfile">
        <img src="../../../img/profile.png" alt="Profile" class="icon">
      </button>
      <button class="nav-btn" @click="toggleFriends">
        <img src="../../../img/friends.png" alt="friends" class="icon">
      </button>
    </div>

    <!-- Friends Modal -->
    <div v-if="showFriendsModal" class="modal-overlay">
      <!-- Existing Friends List Window -->
      <div class="window-container friends-list-window">
        <div class="window-header">
          <div class="window-title">Friends List</div>
          <div class="window-controls">
            <button class="control-button close" @click="toggleFriends">×</button>
          </div>
        </div>
        <div class="window-content">
          <div v-if="friends.length === 0" class="empty-state">
            You have no friends :( 
            <br>
            Go make some!
          </div>
          <ul v-else class="friends-list">
            <li v-for="friend in friends" :key="friend.id" class="friend-item">
              {{ friend.username }}
              <button class="invite-button">Invite</button>
            </li>
          </ul>
        </div>
      </div>

      <!-- Add Friend Window -->
      <div class="window-container search-window">
        <div class="window-header">
          <div class="window-title">Add Friend</div>
        </div>
        <div class="window-content">
          <div class="search-box">
            <input 
              v-model="searchUsername" 
              type="text" 
              placeholder="Search by username"
              class="search-input"
              @keyup.enter="searchUser"
            >
            <button @click="searchUser" class="search-button">Search</button>
          </div>
          <div v-if="searchResult" class="search-result">
            <div class="friend-item">
              {{ searchResult.username }}
              <button 
                @click="sendFriendRequest(searchResult.id)" 
                :disabled="searchResult.isPending"
                class="invite-button"
              >
                {{ searchResult.isPending ? 'Pending' : 'Add Friend' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Friend Requests Window -->
      <div class="window-container requests-window">
        <div class="window-header">
          <div class="window-title">Friend Requests</div>
        </div>
        <div class="window-content">
          <div v-if="friendRequests.length === 0" class="empty-state">
            No pending friend requests
          </div>
          <ul v-else class="friends-list">
            <li v-for="request in friendRequests" :key="request.id" class="friend-item">
              {{ request.username }}
              <div class="request-buttons">
                <button @click="acceptFriendRequest(request.id)" class="accept-button">Accept</button>
                <button @click="rejectFriendRequest(request.id)" class="reject-button">Reject</button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Profile Modal with Match History -->
    <div v-if="showProfileModal" class="modal-overlay">
      <!-- Profile Info Window -->
      <div class="window-container profile-window">
        <div class="window-header">
          <div class="window-title">Profile</div>
          <div class="window-controls">
            <button class="control-button close" @click="toggleProfile">×</button>
          </div>
        </div>
        <div class="window-content profile-content">
          <div class="profile-image">
            <img src="../../../img/default-avatar.png" alt="Profile" class="avatar">
          </div>
          <div class="profile-info">
            <h2 class="username">{{ userData?.username || 'Loading...' }}</h2>
            <div class="elo-rating">ELO: {{ userData?.elo || '1200' }}</div>
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
          <div v-if="matchHistory.length === 0" class="empty-state">
            No matches played yet.
            <br>
            Time to start your chess journey!
          </div>
          <ul v-else class="match-list">
            <li v-for="match in matchHistory" :key="match.id" class="match-item">
              <div class="match-players">
                <span :class="{'current-player': match.player1_id === userData?.id}">
                  {{ match.player1_username }} ({{ match.player1_elo }})
                </span>
                <span class="vs">vs</span>
                <span :class="{'current-player': match.player2_id === userData?.id}">
                  {{ match.player2_username }} ({{ match.player2_elo }})
                </span>
              </div>
              <div class="match-result" :class="match.winner_id === userData?.id ? 'win' : 'loss'">
                {{ match.winner_id === userData?.id ? 'Victory' : 'Defeat' }}
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Search Window -->
    <div v-if="showSearchWindow" class="modal-overlay">
      <div class="window-container search-window">
        <div class="window-header">
          <div class="window-title">Search Friends</div>
          <div class="window-controls">
            <button class="control-button close" @click="toggleSearch">×</button>
          </div>
        </div>
        <div class="window-content">
          <div class="search-box">
            <input type="text" v-model="searchQuery" placeholder="Search by username..." class="search-input">
            <button class="search-button" @click="searchFriends">Search</button>
          </div>
          <div v-if="searchResults.length === 0" class="empty-state">
            No results found.
            <br>
            Try a different username.
          </div>
          <ul v-else class="friends-list">
            <li v-for="user in searchResults" :key="user.id" class="friend-item">
              {{ user.username }}
              <div class="request-buttons">
                <button class="accept-button" @click="sendFriendRequest(user.id)">Add</button>
                <button class="reject-button" @click="ignoreUser(user.id)">Ignore</button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Requests Window -->
    <div v-if="showRequestsWindow" class="modal-overlay">
      <div class="window-container requests-window">
        <div class="window-header">
          <div class="window-title">Friend Requests</div>
          <div class="window-controls">
            <button class="control-button close" @click="toggleRequests">×</button>
          </div>
        </div>
        <div class="window-content">
          <div v-if="friendRequests.length === 0" class="empty-state">
            No new friend requests.
            <br>
            Check back later!
          </div>
          <ul v-else class="friends-list">
            <li v-for="request in friendRequests" :key="request.id" class="friend-item">
              {{ request.username }}
              <div class="request-buttons">
                <button class="accept-button" @click="acceptFriendRequest(request.id)">Accept</button>
                <button class="reject-button" @click="declineFriendRequest(request.id)">Decline</button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, defineEmits } from 'vue';
import { useUser } from '@/composables/useUser';

const emit = defineEmits(['home']);
const { userData } = useUser();

const showFriendsModal = ref(false);
const showProfileModal = ref(false);
const showSearchWindow = ref(false);
const showRequestsWindow = ref(false);

const handleHome = () => emit('home');
const toggleProfile = () => showProfileModal.value = !showProfileModal.value;
const toggleFriends = () => showFriendsModal.value = !showFriendsModal.value;
const toggleSearch = () => showSearchWindow.value = !showSearchWindow.value;
const toggleRequests = () => showRequestsWindow.value = !showRequestsWindow.value;

// Initialize empty states
const friends = ref([]);
const matchHistory = ref([]);
const searchResults = ref([]);
const friendRequests = ref([]);
const searchQuery = ref('');
const searchUsername = ref('');
const searchResult = ref(null);

// Fetch friends from database
const fetchFriends = async () => {
  try {
    const response = await fetch('http://localhost/php/getFriends.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: userData.value?.id })
    });
    const data = await response.json();
    friends.value = data.friends || [];
  } catch (error) {
    console.error('Failed to fetch friends:', error);
    friends.value = [];
  }
};

// Fetch match history from database
const fetchMatchHistory = async () => {
  try {
    const response = await fetch('http://localhost/php/getMatchHistory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: userData.value?.id })
    });
    const data = await response.json();
    matchHistory.value = data.matches || [];
  } catch (error) {
    console.error('Failed to fetch match history:', error);
    matchHistory.value = [];
  }
};

// Search for friends by username
const searchFriends = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    return;
  }

  try {
    const response = await fetch('http://localhost/php/searchFriends.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query: searchQuery.value.trim(), userId: userData.value?.id })
    });
    const data = await response.json();
    searchResults.value = data.results || [];
  } catch (error) {
    console.error('Failed to search friends:', error);
    searchResults.value = [];
  }
};

// Search user by username
const searchUser = async () => {
  if (!searchUsername.value) return;
  
  try {
    const response = await fetch('http://localhost/php/searchUser.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        username: searchUsername.value,
        userId: userData.value?.id 
      })
    });
    const data = await response.json();
    if (data.status === 'success') {
      searchResult.value = data.user;
    }
  } catch (error) {
    console.error('Failed to search user:', error);
  }
};

// Send a friend request
const sendFriendRequest = async (recipientId) => {
  try {
    const response = await fetch('http://localhost/php/sendFriendRequest.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ senderId: userData.value?.id, recipientId })
    });
    const data = await response.json();
    if (data.success) {
      toggleSearch();
      fetchFriends();
    } else {
      console.error('Failed to send friend request:', data.message);
    }
  } catch (error) {
    console.error('Error sending friend request:', error);
  }
};

// Ignore a user (do not send friend request)
const ignoreUser = (userId) => {
  // For now, just log the action
  console.log(`Ignored user with ID: ${userId}`);
};

// Accept a friend request
const acceptFriendRequest = async (requestId) => {
  try {
    const response = await fetch('http://localhost/php/acceptFriendRequest.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ requestId, userId: userData.value?.id })
    });
    const data = await response.json();
    if (data.success) {
      fetchFriends();
      fetchFriendRequests();
    } else {
      console.error('Failed to accept friend request:', data.message);
    }
  } catch (error) {
    console.error('Error accepting friend request:', error);
  }
};

// Decline a friend request
const declineFriendRequest = async (requestId) => {
  try {
    const response = await fetch('http://localhost/php/declineFriendRequest.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ requestId, userId: userData.value?.id })
    });
    const data = await response.json();
    if (data.success) {
      fetchFriendRequests();
    } else {
      console.error('Failed to decline friend request:', data.message);
    }
  } catch (error) {
    console.error('Error declining friend request:', error);
  }
};

// Fetch friend requests from database
const fetchFriendRequests = async () => {
  try {
    const response = await fetch('http://localhost/php/getFriendRequests.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: userData.value?.id })
    });
    const data = await response.json();
    friendRequests.value = data.requests || [];
  } catch (error) {
    console.error('Failed to fetch friend requests:', error);
    friendRequests.value = [];
  }
};

onMounted(() => {
  if (userData.value?.id) {
    fetchFriends();
    fetchMatchHistory();
    fetchFriendRequests();
  }
});
</script>

<style scoped>
.bottom-bar {
  height: 60px;
  background-color: var(--primary-color);
  display: flex;
  align-items: center;
  padding: 0 1rem;
  gap: 1rem;
  width: 100%;
  bottom: 0;
  position: absolute;
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


.reset-btn:hover {
  background-color: rgba(147, 112, 219, 0.2);
  border-radius: 4px;
}

/* Modal and Window Styles */
.modal-overlay {
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

.window-container {
  background-color: #2a2a2a;
  border: 1px solid #9370DB;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.window-header {
  background-color: #232323;
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

.profile-window {
  width: 300px;
  position: fixed;
  top: 5%;
  left: 25%;
}

.history-window {
  width: 500px;
  position: fixed;
  top: 35%;
  left: 40%;
}

.search-window {
  position: fixed;
  width: 300px;
  top: 15%;
  left: 45%;
}

.requests-window {
  position: fixed;
  width: 300px;
  top: 45%;
  left: 45%;
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
  color: #ffffff;
}

.username {
  margin: 0;
  color: #9370DB;
  font-size: 1.2rem;
}

.elo-rating {
  margin-top: 0.5rem;
  font-size: 1rem;
}

.match-list {
  list-style: none;
  padding: 0;
  margin: 0;
  min-height: 180px;
  max-height: 360px;
}

.match-item {
  color: #ffffff;
  background-color: #232323;
  margin-bottom: 2px;
}

.match-players {
  color: #ffffff;
}

.vs {
  color: #9370DB;
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

.friends-list {
  list-style: none;
  width: 400px;
  padding: 0;
  margin: 0;
}

.friend-item {
  color: #ffffff;
  background-color: #2a2a2a;
  margin-bottom: 2px;
  display: flex;
  justify-content:space-between
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

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #9370DB;
  font-size: 1.1rem;
  line-height: 1.5;
}

.search-box {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}

.search-input {
  flex: 1;
  padding: 8px;
  border: 1px solid #9370DB;
  border-radius: 4px;
  background: #1a1a1a;
  color: white;
}

.search-button {
  padding: 8px 15px;
  background: #9370DB;
  border: none;
  border-radius: 4px;
  color: white;
  cursor: pointer;
}

.search-button:hover {
  background: #7a5fbf;
}

.request-buttons {
  display: flex;
  gap: 8px;
}

.accept-button {
  background: #4CAF50;
  border: none;
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.reject-button {
  background: #f44336;
  border: none;
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.accept-button:hover {
  background: #45a049;
}

.reject-button:hover {
  background: #da190b;
}
.friends-list-window{
  position: absolute;
  left: 10vw;
}
</style>