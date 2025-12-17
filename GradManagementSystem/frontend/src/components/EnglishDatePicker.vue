<template>
  <input ref="inputEl" class="date-input" type="text" :placeholder="placeholder" :disabled="disabled" />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'

const props = defineProps({
  modelValue: { type: String, default: '' }, // YYYY-MM-DD
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: 'YYYY-MM-DD' },
})

const emit = defineEmits(['update:modelValue'])

const inputEl = ref(null)
let fp = null

const setFpValue = (val) => {
  if (!fp) return
  const v = String(val || '').trim()
  fp.setDate(v || null, false, 'Y-m-d')
}

onMounted(() => {
  fp = flatpickr(inputEl.value, {
    dateFormat: 'Y-m-d',
    allowInput: true,
    disableMobile: true,
    defaultDate: props.modelValue || null,
    onChange: (_selectedDates, dateStr) => emit('update:modelValue', dateStr || ''),
  })
})

onBeforeUnmount(() => {
  fp?.destroy?.()
  fp = null
})

watch(
  () => props.modelValue,
  (v) => setFpValue(v),
)

watch(
  () => props.disabled,
  (v) => {
    if (!fp) return
    fp._input.disabled = Boolean(v)
  },
)
</script>

<style scoped>
.date-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}
</style>

