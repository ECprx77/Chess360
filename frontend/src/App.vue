<template>
  <!-- Main application router view -->
  <router-view></router-view>
</template>

<script setup>
</script>

<style>
:root {
  --primary-color: #9370DB;  /* Purple theme color */
  --bg-color: #4A4A4A;       /* Dark background color */
}

/* Global body and html styling */
html, body {
  margin: 0;
  padding: 0;
  background-color: var(--bg-color);
  font-family: Arial, sans-serif;
  overflow: hidden;  /* Prevents scrolling on all pages */
}
</style>