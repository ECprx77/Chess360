/**
 * User State Management Composable
 * 
 * Provides reactive user data management using Vue 3 Composition API.
 * Handles loading and storing user information from localStorage.
 */

import { ref, onMounted } from 'vue';

export function useUser() {
  // Reactive reference to store user data
  const userData = ref(null);

  /**
   * Load user data from localStorage on component mount
   * Parses stored JSON data and updates the reactive userData reference
   */
  const loadUserData = () => {
    const data = localStorage.getItem('userData');
    if (data) {
      userData.value = JSON.parse(data);
    }
  };

  // Automatically load user data when component is mounted
  onMounted(() => {
    loadUserData();
  });

  return {
    userData,
    loadUserData
  };
}