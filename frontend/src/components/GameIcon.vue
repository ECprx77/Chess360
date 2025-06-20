<template>
  <!-- Draggable game icon with click-to-launch functionality -->
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
/**
 * Game Icon Component
 * 
 * Provides a draggable desktop-style icon for launching chess games.
 * Supports grid-based positioning and click-to-launch functionality.
 */

import { ref, onUnmounted, defineProps } from 'vue';
import { useRouter } from 'vue-router';

// Component props for initial positioning
const props = defineProps({
  initialX: { type: Number, default: 0 },
  initialY: { type: Number, default: 0 }
});

const router = useRouter();
const gridSize = 80;  // Grid size for snap-to-grid positioning
const position = ref({ x: props.initialX, y: props.initialY });
const isDragging = ref(false);
let startX = 0;
let startY = 0;
let mouseDownTime = 0;
let parentRect = null;

/**
 * Handle mouse movement during drag operation
 * @param {MouseEvent} e - Mouse move event
 */
function handleMouseMove(e) {
  if (!isDragging.value || !parentRect) return;
  
  // Calculate new position based on mouse movement
  const newX = Math.round((e.clientX - startX) / gridSize);
  const newY = Math.round((e.clientY - startY) / gridSize);
  
  // Constrain movement to parent container boundaries
  const maxX = Math.floor((parentRect.width - 70) / gridSize);
  const maxY = Math.floor((parentRect.height - 90) / gridSize);
  
  position.value = {
    x: Math.min(Math.max(newX, 0), maxX),
    y: Math.min(Math.max(newY, 0), maxY)
  };
}

/**
 * Handle mouse release to end drag or trigger click
 * @param {MouseEvent} e - Mouse up event
 */
function handleMouseUp(e) {
  const clickDuration = Date.now() - mouseDownTime;
  
  // Check if this was a click (short duration, minimal movement)
  if (clickDuration < 200 && 
      Math.abs(e.clientX - (startX + position.value.x * gridSize)) < 5 && 
      Math.abs(e.clientY - (startY + position.value.y * gridSize)) < 5) {
    router.push('/game');
  }

  // End drag operation
  isDragging.value = false;
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('mouseup', handleMouseUp);
}

/**
 * Start drag operation on mouse down
 * @param {MouseEvent} event - Mouse down event
 */
function startDrag(event) {
  mouseDownTime = Date.now();
  isDragging.value = true;
  
  // Get parent container boundaries for constraint calculation
  const element = event.target.closest('.game-icon');
  if (element) {
    parentRect = element.parentElement.getBoundingClientRect();
  }
  
  // Calculate initial mouse offset
  startX = event.clientX - position.value.x * gridSize;
  startY = event.clientY - position.value.y * gridSize;

  // Add global event listeners
  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('mouseup', handleMouseUp);
}

/**
 * Clean up event listeners on component unmount
 */
onUnmounted(() => {
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('mouseup', handleMouseUp);
});
</script>

<style scoped>
/**
 * Game Icon Component Styles
 * 
 * Provides desktop-style icon appearance with drag and drop functionality.
 */

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