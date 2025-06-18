import { ref, onMounted } from 'vue';

export function useUser() {
  const userData = ref(null);

  const loadUserData = () => {
    const data = localStorage.getItem('userData');
    if (data) {
      userData.value = JSON.parse(data);
    }
  };

  onMounted(() => {
    loadUserData();
  });

  return {
    userData,
    loadUserData
  };
}