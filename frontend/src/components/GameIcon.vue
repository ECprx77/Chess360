<template>
  <div 
    class="game-icon" 
    :style="{ 
      transform: `translate(${position.x * gridSize}px, ${position.y * gridSize}px)`,
      cursor: isDragging ? 'grabbing' : 'grab'
    }"
    @mousedown.prevent="startDrag"
  >
    <img src="../../../img/Chess360.png" alt="Chess Game" class="icon-image">
    <div class="icon-label">Fisher Random</div>
  </div>
</template>

<script setup>
import { ref, onUnmounted, defineProps } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  initialX: { type: Number, default: 0 },
  initialY: { type: Number, default: 0 }
});

const router = useRouter();
const gridSize = 80;
const position = ref({ x: props.initialX, y: props.initialY });
const isDragging = ref(false);
let startX = 0;
let startY = 0;
let mouseDownTime = 0;
let parentRect = null;

function handleMouseMove(e) {
  if (!isDragging.value || !parentRect) return;
  
  const newX = Math.round((e.clientX - startX) / gridSize);
  const newY = Math.round((e.clientY - startY) / gridSize);
  
  // Constrain to parent div boundaries
  const maxX = Math.floor((parentRect.width - 70) / gridSize);
  const maxY = Math.floor((parentRect.height - 90) / gridSize);
  
  position.value = {
    x: Math.min(Math.max(newX, 0), maxX),
    y: Math.min(Math.max(newY, 0), maxY)
  };
}

function handleMouseUp(e) {
  const clickDuration = Date.now() - mouseDownTime;
  
  if (clickDuration < 200 && 
      Math.abs(e.clientX - (startX + position.value.x * gridSize)) < 5 && 
      Math.abs(e.clientY - (startY + position.value.y * gridSize)) < 5) {
    router.push('/game');
  }

  isDragging.value = false;
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('mouseup', handleMouseUp);
}

function startDrag(event) {
  mouseDownTime = Date.now();
  isDragging.value = true;
  
  // Get parent boundaries
  const element = event.target.closest('.game-icon');
  if (element) {
    parentRect = element.parentElement.getBoundingClientRect();
  }
  
  startX = event.clientX - position.value.x * gridSize;
  startY = event.clientY - position.value.y * gridSize;

  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('mouseup', handleMouseUp);
}

onUnmounted(() => {
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('mouseup', handleMouseUp);
});
</script>

<style scoped>
.game-icon {
  position: absolute;
  width: 70px;
  height: 90px;
  display: flex;
  flex-direction: column;
  align-items: center;
  user-select: none;
  z-index: 100;
}

.icon-image {
  width: 100px;
  height: 100px;
  border-radius: 8px;
  margin-bottom: 5px;
  z-index: 100;
}

.icon-label {
  color: #ffffff;
  font-size: 14px;
  margin-top: -5px;
  text-align: center;
  z-index: 100;
}
</style>