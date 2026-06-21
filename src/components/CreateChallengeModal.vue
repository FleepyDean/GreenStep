<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Create New Challenge</h3>
        <button class="modal-close-btn" @click="$emit('close')">×</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label>Challenge Title</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="e.g. Zero Waste Week"
            required
          />
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea
            v-model="form.description"
            placeholder="Describe the challenge goals..."
            rows="3"
            required
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Target CO₂ Reduction (kg)</label>
            <input
              v-model="form.target_co2_reduction"
              type="number"
              step="0.01"
              min="0"
              placeholder="e.g. 20"
              required
            />
          </div>
          <div class="form-group">
            <label>Duration (days)</label>
            <input
              v-model="form.duration_days"
              type="number"
              min="1"
              placeholder="e.g. 7"
              required
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Start Date</label>
            <input v-model="form.start_date" type="date" required />
          </div>
          <div class="form-group">
            <label>End Date</label>
            <input v-model="form.end_date" type="date" required />
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="cancel-btn" @click="$emit('close')">Cancel</button>
          <button type="submit" class="submit-btn" :disabled="submitting">
            {{ submitting ? 'Creating...' : 'Create Challenge' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'submit'])

const submitting = ref(false)

const form = reactive({
  name: '',
  description: '',
  target_co2_reduction: '',
  duration_days: '',
  start_date: '',
  end_date: ''
})

function resetForm() {
  form.name = ''
  form.description = ''
  form.target_co2_reduction = ''
  form.duration_days = ''
  form.start_date = ''
  form.end_date = ''
}

async function handleSubmit() {
  submitting.value = true
  emit('submit', { ...form })
  submitting.value = false
  resetForm()
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: #112620;
  border: 1px solid #1c3830;
  border-radius: 14px;
  padding: 1.5rem;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.modal-header h3 {
  font-size: 1.2rem;
  color: #34d399;
  font-weight: 600;
  margin: 0;
}

.modal-close-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.modal-close-btn:hover {
  color: #f87171;
}

.form-group {
  margin-bottom: 1rem;
  text-align: left;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  color: #94a3b8;
  margin-bottom: 0.4rem;
  font-weight: 500;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.7rem 0.9rem;
  border: 1px solid #1c3830;
  border-radius: 8px;
  background: #0c1d18;
  color: #f1f5f9;
  font-size: 0.9rem;
  outline: none;
  font-family: inherit;
  resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.cancel-btn {
  padding: 0.7rem 1.25rem;
  border: 1px solid #1c3830;
  border-radius: 8px;
  background: transparent;
  color: #94a3b8;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn:hover {
  background: #1c3830;
  color: #f1f5f9;
}

.modal-actions .submit-btn {
  width: auto;
  padding: 0.7rem 1.5rem;
}

@media (max-width: 480px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions button {
    width: 100%;
  }
}
</style>
