<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ modalTitle }}</h3>
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

        <div class="form-row form-row-three">
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
          <div class="form-group">
            <label>Member Limit</label>
            <input
              v-model="form.member_limit"
              type="number"
              min="1"
              placeholder="Max members"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label>Target Category</label>
          <select v-model="form.target_category" required>
            <option value="" disabled selected>Select a category</option>
            <option value="All">All (General)</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
          <p class="field-hint">
            All fields are required to create a challenge.
          </p>
        </div>

        <div class="form-group">
          <label>Target Activity Type</label>
          <div v-if="!isCategorySpecific" class="field-hint">
            Select a specific category above to choose activity types.
          </div>
          <div v-else class="activity-type-checklist">
            <label
              v-for="type in activityTypes"
              :key="type.id"
              class="check-item"
              :class="{ checked: form.target_activity_type_ids.includes(type.id) }"
            >
              <input
                type="checkbox"
                :value="type.id"
                v-model="form.target_activity_type_ids"
                class="check-input"
              />
              <span class="check-box">
                <svg v-if="form.target_activity_type_ids.includes(type.id)" viewBox="0 0 12 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1 5l3.5 3.5L11 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="check-label">{{ type.name }}</span>
            </label>
          </div>
          <p class="field-hint">
            Leave all unchecked to count all activities in the selected category.
          </p>
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
            {{ submitting ? (isEdit ? 'Saving...' : 'Creating...') : (isEdit ? 'Save Changes' : 'Create Challenge') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { activityAPI } from '@/services/api'

const props = defineProps({
  show: { type: Boolean, default: false },
  challenge: { type: Object, default: null }
})

const emit = defineEmits(['close', 'submit'])

const submitting = ref(false)
const activityTypes = ref([])
const categories = ref([])

const form = reactive({
  name: '',
  description: '',
  target_co2_reduction: '',
  target_category: '',
  target_activity_type_id: '',
  duration_days: '',
  member_limit: '',
  start_date: '',
  end_date: ''
})

const isEdit = computed(() => !!props.challenge)
const modalTitle = computed(() => isEdit.value ? 'Edit Challenge' : 'Create New Challenge')
const isCategorySpecific = computed(() => form.target_category !== 'All' && form.target_category !== '')

function addDaysToDate(dateStr, days) {
  if (!dateStr || !days) return ''
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return ''
  date.setDate(date.getDate() + parseInt(days, 10))
  return date.toISOString().split('T')[0]
}

watch(
  [() => form.duration_days, () => form.start_date],
  ([durationDays, startDate]) => {
    if (!durationDays || !startDate) return
    const newEndDate = addDaysToDate(startDate, durationDays)
    if (newEndDate !== form.end_date) {
      form.end_date = newEndDate
    }
  },
  { immediate: true }
)

watch(
  () => form.end_date,
  (newEndDate) => {
    if (!newEndDate || !form.start_date) return
    const start = new Date(form.start_date)
    const end = new Date(newEndDate)
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return
    const diffTime = end - start
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    if (diffDays >= 0 && diffDays !== parseInt(form.duration_days, 10)) {
      form.duration_days = diffDays.toString()
    }
  }
)

async function loadCategories() {
  try {
    const { data } = await activityAPI.getCategories()
    if (data.success) categories.value = data.categories || []
  } catch (e) {
    categories.value = []
  }
}

async function loadActivityTypes() {
  if (!isCategorySpecific.value) {
    activityTypes.value = []
    return
  }
  try {
    const { data } = await activityAPI.getTypesByCategory(form.target_category)
    activityTypes.value = data.activity_types || []
  } catch (e) {
    activityTypes.value = []
  }
}

let populatingForm = false

watch(() => form.target_category, async () => {
  if (!populatingForm) {
    form.target_activity_type_ids = []
  }
  await loadActivityTypes()
})

watch(() => props.show, (visible) => {
  if (visible) {
    loadCategories()
    loadActivityTypes()
  }
})

function resetForm() {
  form.name = ''
  form.description = ''
  form.target_co2_reduction = ''
  form.target_category = ''
  form.target_activity_type_id = ''
  form.duration_days = ''
  form.member_limit = ''
  form.start_date = ''
  form.end_date = ''
  activityTypes.value = []
}

async function populateForm() {
  if (props.challenge) {
    populatingForm = true
    form.name = props.challenge.name || ''
    form.description = props.challenge.description || ''
    form.target_co2_reduction = props.challenge.target_co2_reduction || ''
    form.target_category = props.challenge.target_category || 'All'
    form.target_activity_type_ids = []
    form.duration_days = props.challenge.duration_days || ''
    form.member_limit = props.challenge.member_limit || ''
    form.start_date = props.challenge.start_date || ''
    form.end_date = props.challenge.end_date || ''
    await loadActivityTypes()
    const saved = props.challenge.target_activity_type_ids
    form.target_activity_type_ids = Array.isArray(saved)
      ? saved.map(Number)
      : []
    populatingForm = false
  }
}

watch(() => props.challenge, populateForm, { immediate: true })

async function handleSubmit() {
  submitting.value = true
  const payload = {
    ...form,
    target_activity_type_id: form.target_activity_type_id || null,
    member_limit: form.member_limit ? parseInt(form.member_limit, 10) : null
  }
  emit('submit', payload)
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
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
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
  color: #25D366;
  font-weight: 600;
  margin: 0;
}

.modal-close-btn {
  background: none;
  border: none;
  color: #54656F;
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
  color: #54656F;
  margin-bottom: 0.4rem;
  font-weight: 500;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.7rem 0.9rem;
  border: 1px solid #E9EDEF;
  border-radius: 8px;
  background: #F0F2F5;
  color: #111B21;
  font-size: 0.9rem;
  outline: none;
  font-family: inherit;
  resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: #00A884;
  box-shadow: 0 0 0 3px rgba(0, 168, 132, 0.15);
}

.form-group select:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.field-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0.3rem 0 0;
}

.optional-label {
  font-size: 0.75rem;
  color: #8696A0;
  font-weight: 400;
}

.activity-type-disabled {
  font-size: 0.82rem;
  color: #8696A0;
  padding: 0.6rem 0.9rem;
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 8px;
}

.activity-type-checklist {
  display: flex;
  flex-direction: column;
  max-height: 196px;
  overflow-y: auto;
  border: 1px solid #D1D7DB;
  border-radius: 8px;
  background: #fff;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 0.7rem;
  cursor: pointer;
  user-select: none;
  border-bottom: 1px solid #F0F2F5;
  transition: background 0.1s;
}

.check-item:last-child {
  border-bottom: none;
}

.check-item:hover {
  background: #F7F9FA;
}

.check-item.checked {
  background: rgba(0, 168, 132, 0.06);
}

.check-input {
  display: none;
}

.check-box {
  width: 16px;
  height: 16px;
  min-width: 16px;
  border-radius: 4px;
  border: 1.5px solid #CBD5E1;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.12s, border-color 0.12s;
}

.check-item.checked .check-box {
  background: #00A884;
  border-color: #00A884;
}

.check-box svg {
  width: 10px;
  height: 8px;
}

.check-label {
  font-size: 0.875rem;
  color: #111B21;
  line-height: 1.2;
}

.check-item.checked .check-label {
  color: #00A884;
  font-weight: 600;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.form-row-three .form-group {
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
  border: 1px solid #E9EDEF;
  border-radius: 8px;
  background: transparent;
  color: #54656F;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn:hover {
  background: #E9EDEF;
  color: #111B21;
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
