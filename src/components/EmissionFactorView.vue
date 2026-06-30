<template>
  <div class="ef-container">
    <div class="page-title">
      <h2>Emission Factor Management</h2>
      <p>Configure per-activity CO₂ emission factors used in carbon calculations</p>
    </div>

    <!-- Access denied for non-admins -->
    <div v-if="!isAdmin" class="access-denied">
      <Lock class="denied-icon" :size="48" />
      <p>This page is restricted to administrators only.</p>
    </div>

    <template v-else>
      <!-- Add / Edit Activity Type -->
      <div class="ef-card">
        <h3><component :is="editingId ? Pencil : PlusCircle" :size="18" /> {{ editingId ? 'Edit Activity Type' : 'Add New Activity Type' }}</h3>
        <form @submit.prevent="editingId ? submitUpdate() : submitCreate()" class="ef-form">
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select v-model="form.category" required>
                <option disabled value="">Select category</option>
                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                <option value="__other__">+ Other (add new)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Activity Name</label>
              <input v-model="form.name" type="text" placeholder="e.g. Car (Petrol)" required />
            </div>
          </div>
          <div v-if="form.category === '__other__'" class="form-row">
            <div class="form-group">
              <label>New Category Name</label>
              <input v-model="customCategory" type="text" placeholder="e.g. Water, Aviation" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Unit</label>
              <input v-model="form.unit" type="text" placeholder="e.g. km, kWh, meal" required />
            </div>
            <div class="form-group">
              <label>kg CO₂ per unit</label>
              <input v-model.number="form.kg_co2_per_unit" type="number" step="0.0001" placeholder="e.g. 0.1920" required />
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="submit-btn" :disabled="loading">
              {{ loading ? 'Saving...' : (editingId ? 'Update' : 'Add Activity Type') }}
            </button>
            <button v-if="editingId" type="button" class="cancel-edit-btn" @click="cancelEdit">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Activity Types Table grouped by category -->
      <div v-if="loadingList" class="state-box">
        <div class="spinner"></div>
        <p>Loading activity types...</p>
      </div>

      <template v-else>
        <div v-for="(items, category) in grouped" :key="category" class="ef-card">
          <div class="category-header">
            <h3>{{ category }}</h3>
            <span class="count-badge">{{ items.length }}</span>
          </div>

          <div class="ef-table-wrapper">
            <table class="ef-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Unit</th>
                  <th>kg CO₂ / unit</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in items" :key="item.id" :class="{ 'editing-row': editingId === item.id }">
                  <td class="name-cell">{{ item.name }}</td>
                  <td><span class="unit-tag">{{ item.unit }}</span></td>
                  <td>
                    <span :class="item.kg_co2_per_unit < 0 ? 'factor-negative' : 'factor-positive'">
                      {{ Number(item.kg_co2_per_unit).toFixed(4) }}
                    </span>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button class="icon-action-btn edit-icon-btn" @click="startEdit(item)" title="Edit"><Pencil :size="14" /></button>
                      <button class="icon-action-btn del-icon-btn" @click="confirmDelete(item)" title="Delete"><Trash2 :size="14" /></button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- Delete Confirmation Modal -->
      <div v-if="deletingItem" class="modal-overlay" @click.self="deletingItem = null">
        <div class="confirm-modal">
          <h4>Delete Activity Type?</h4>
          <p>
            Are you sure you want to delete <strong>{{ deletingItem.name }}</strong>?
            This will fail if existing activity logs reference it.
          </p>
          <div class="confirm-actions">
            <button class="cancel-edit-btn" @click="deletingItem = null">Cancel</button>
            <button class="del-btn" @click="submitDelete" :disabled="loading">
              {{ loading ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { emissionFactorAPI } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { Lock, Pencil, PlusCircle, Trash2 } from 'lucide-vue-next'

const authStore = useAuthStore()
const { toast } = useToast()

const isAdmin = computed(() => authStore.user?.role === 'admin')

const activityTypes = ref([])
const categories    = ref([])
const loadingList   = ref(false)
const loading       = ref(false)
const editingId     = ref(null)
const deletingItem  = ref(null)
const customCategory = ref('')

const blankForm = () => ({ category: '', name: '', unit: '', kg_co2_per_unit: '' })
const form = ref(blankForm())

const grouped = computed(() => {
  const g = {}
  for (const item of activityTypes.value) {
    if (!g[item.category]) g[item.category] = []
    g[item.category].push(item)
  }
  return g
})

async function loadCategories() {
  try {
    const res = await emissionFactorAPI.getCategories()
    categories.value = res.data.categories ?? []
  } catch {
    toast.error('Failed to load categories')
  }
}

async function loadAll() {
  loadingList.value = true
  try {
    const res = await emissionFactorAPI.getAll()
    activityTypes.value = res.data.activity_types ?? []
  } catch {
    toast.error('Failed to load activity types')
  } finally {
    loadingList.value = false
  }
}

async function submitCreate() {
  loading.value = true
  try {
    const payload = { ...form.value }
    
    if (!payload.category) {
      toast.error('Please select a category')
      loading.value = false
      return
    }
    
    if (payload.category === '__other__') {
      const newCat = customCategory.value.trim()
      if (!newCat) {
        toast.error('Please enter a new category name')
        loading.value = false
        return
      }
      const res = await emissionFactorAPI.createCategory({ name: newCat })
      payload.category = res.data.category
    }
    
    if (!payload.name?.trim()) {
      toast.error('Please enter an activity name')
      loading.value = false
      return
    }
    
    if (!payload.unit?.trim()) {
      toast.error('Please enter a unit (e.g. km, kWh, meal)')
      loading.value = false
      return
    }
    
    if (payload.kg_co2_per_unit === null || payload.kg_co2_per_unit === undefined || payload.kg_co2_per_unit === '') {
      toast.error('Please enter the kg CO₂ per unit value')
      loading.value = false
      return
    }
    
    await emissionFactorAPI.create(payload)
    toast.success('Activity type added')
    form.value = blankForm()
    customCategory.value = ''
    await Promise.all([loadAll(), loadCategories()])
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Failed to add activity type')
  } finally {
    loading.value = false
  }
}

function startEdit(item) {
  editingId.value = item.id
  form.value = {
    category: item.category,
    name: item.name,
    unit: item.unit,
    kg_co2_per_unit: item.kg_co2_per_unit
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelEdit() {
  editingId.value = null
  form.value = blankForm()
  customCategory.value = ''
}

async function submitUpdate() {
  loading.value = true
  try {
    await emissionFactorAPI.update(editingId.value, { ...form.value })
    toast.success('Activity type updated')
    cancelEdit()
    await loadAll()
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Failed to update')
  } finally {
    loading.value = false
  }
}

function confirmDelete(item) {
  deletingItem.value = item
}

async function submitDelete() {
  loading.value = true
  try {
    await emissionFactorAPI.delete(deletingItem.value.id)
    toast.success('Activity type deleted')
    deletingItem.value = null
    await loadAll()
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Failed to delete')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (isAdmin.value) {
    loadAll()
    loadCategories()
  }
})
</script>

<style scoped>
.ef-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.access-denied {
  text-align: center;
  padding: 4rem 1rem;
  color: #54656F;
}

.denied-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
}

.ef-card {
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  padding: 1.75rem;
}

.ef-card h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #25D366;
  margin-bottom: 1.25rem;
}

.ef-form .form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #54656F;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.form-group input,
.form-group select {
  padding: 0.7rem 0.9rem;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  background: #F0F2F5;
  color: #111B21;
  font-size: 0.9rem;
  outline: none;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #00A884;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.submit-btn {
  padding: 0.7rem 1.5rem;
  background: #00A884;
  color: #FFFFFF;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background: #008f6f;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-edit-btn {
  padding: 0.7rem 1.25rem;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  background: transparent;
  color: #54656F;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background 0.2s;
}

.cancel-edit-btn:hover {
  background: #E9EDEF;
  color: #111B21;
}

/* Category header */
.category-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 1rem;
}

.category-header h3 {
  margin-bottom: 0;
}

.category-icon {
  font-size: 1.25rem;
}

.count-badge {
  background: #E9EDEF;
  color: #54656F;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

/* Table */
.ef-table-wrapper {
  overflow-x: auto;
}

.ef-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.ef-table th {
  text-align: left;
  padding: 0.65rem 0.9rem;
  color: #54656F;
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-bottom: 1px solid #E9EDEF;
}

.ef-table td {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid #E9EDEF;
  color: #111B21;
}

.ef-table tr:last-child td {
  border-bottom: none;
}

.ef-table tr.editing-row {
  background: rgba(0, 168, 132, 0.05);
}

.name-cell {
  font-weight: 500;
}

.unit-tag {
  background: #E9EDEF;
  color: #54656F;
  font-size: 0.78rem;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

.factor-positive {
  color: #ef4444;
  font-weight: 600;
  font-family: monospace;
}

.factor-negative {
  color: #00A884;
  font-weight: 600;
  font-family: monospace;
}

.action-btns {
  display: flex;
  gap: 0.35rem;
}

.icon-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: transparent;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.1s ease;
  -webkit-tap-highlight-color: transparent;
}

.icon-action-btn:active {
  transform: scale(0.9);
}

.edit-icon-btn {
  border-color: #D1D7DB;
  background: #F0F2F5;
}

.edit-icon-btn:hover {
  background: #E9EDEF;
}

.del-icon-btn {
  border-color: rgba(239, 68, 68, 0.25);
  background: rgba(239, 68, 68, 0.07);
}

.del-icon-btn:hover {
  background: rgba(239, 68, 68, 0.18);
}

.del-btn {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #ef4444;
  padding: 0.35rem 0.8rem;
  font-size: 0.8rem;
  font-weight: 500;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.del-btn:hover {
  background: #ef4444;
  color: #FFFFFF;
}

/* State */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #54656F;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #E9EDEF;
  border-top-color: #00A884;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 12px;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Confirm delete modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.confirm-modal {
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  padding: 2rem;
  max-width: 420px;
  width: 100%;
}

.confirm-modal h4 {
  font-size: 1.1rem;
  color: #111B21;
  margin-bottom: 0.75rem;
}

.confirm-modal p {
  font-size: 0.9rem;
  color: #54656F;
  line-height: 1.55;
  margin-bottom: 1.5rem;
}

.confirm-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

@media (max-width: 640px) {
  .ef-form .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
