<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          :class="['toast-item', `toast-${t.type}`]"
          @click="removeToast(t.id)"
        >
          <span class="toast-icon">{{ iconFor(t.type) }}</span>
          <span class="toast-message">{{ t.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()

function iconFor(type) {
  const icons = { success: '✅', error: '❌', info: 'ℹ️' }
  return icons[type] || '✅'
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.toast-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #fff;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
  cursor: pointer;
  pointer-events: auto;
  min-width: 250px;
  max-width: 400px;
}

.toast-success {
  background: linear-gradient(135deg, #008069, #00A884);
}

.toast-error {
  background: linear-gradient(135deg, #dc2626, #ef4444);
}

.toast-info {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.toast-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.toast-message {
  flex: 1;
}

/* Animations */
.toast-enter-active {
  transition: all 0.3s ease;
}

.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-30px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

.toast-move {
  transition: transform 0.3s ease;
}

/* Mobile */
@media (max-width: 768px) {
  .toast-container {
    top: 16px;
    right: 16px;
    left: 16px;
  }

  .toast-item {
    min-width: auto;
    max-width: none;
  }
}
</style>
