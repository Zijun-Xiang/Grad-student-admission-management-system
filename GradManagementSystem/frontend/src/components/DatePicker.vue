<template>
  <div ref="root" class="dp">
    <div class="dp-input" :class="{ disabled }" @click="toggle" role="button" tabindex="0" @keydown.enter.prevent="toggle">
      <input :value="displayValue" :placeholder="placeholder" readonly :disabled="disabled" />
      <span class="dp-icon" aria-hidden="true">📅</span>
    </div>

    <div v-if="open" class="dp-pop">
      <div class="dp-head">
        <button class="nav" type="button" @click="prevMonth">&lt;</button>
        <div class="title">{{ title }}</div>
        <button class="nav" type="button" @click="nextMonth">&gt;</button>
      </div>

      <div class="dow">
        <div v-for="d in daysOfWeek" :key="d" class="dow-cell">{{ d }}</div>
      </div>

      <div class="grid">
        <button
          v-for="(cell, idx) in cells"
          :key="idx"
          class="cell"
          type="button"
          :class="cellClass(cell)"
          :disabled="!cell.date || cellClass(cell).disabled"
          @click="select(cell)"
        >
          <span class="num">{{ cell.day }}</span>
        </button>
      </div>

      <div class="dp-actions">
        <button class="btn-link" type="button" @click="setToday">Today</button>
        <button class="btn-link" type="button" @click="clear">Clear</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' }, // YYYY-MM-DD
  locale: { type: String, default: 'en-US' },
  placeholder: { type: String, default: 'YYYY-MM-DD' },
  min: { type: String, default: '' }, // YYYY-MM-DD (optional)
  max: { type: String, default: '' }, // YYYY-MM-DD (optional)
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const root = ref(null)
const open = ref(false)

const parseDate = (s) => {
  if (!s) return null
  const d = new Date(s + 'T00:00:00')
  return isNaN(d.getTime()) ? null : d
}

const formatISO = (d) => {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const selected = computed(() => parseDate(props.modelValue))
const minDate = computed(() => parseDate(props.min))
const maxDate = computed(() => parseDate(props.max))
const today = new Date()

const viewYear = ref(today.getFullYear())
const viewMonth = ref(today.getMonth()) // 0-11

watch(
  () => props.modelValue,
  (v) => {
    const d = parseDate(v)
    if (d) {
      viewYear.value = d.getFullYear()
      viewMonth.value = d.getMonth()
    }
  },
  { immediate: true },
)

const displayValue = computed(() => props.modelValue || '')

const title = computed(() => {
  const dt = new Date(viewYear.value, viewMonth.value, 1)
  return dt.toLocaleString(props.locale, { month: 'long', year: 'numeric' })
})

const daysOfWeek = computed(() => {
  const fmt = new Intl.DateTimeFormat(props.locale, { weekday: 'short' })
  const baseSunday = new Date(Date.UTC(2024, 0, 7)) // a Sunday
  const out = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(baseSunday.getTime() + i * 24 * 60 * 60 * 1000)
    out.push(fmt.format(d))
  }
  return out
})

const cells = computed(() => {
  const first = new Date(viewYear.value, viewMonth.value, 1)
  const firstDow = first.getDay()
  const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate()

  const out = []
  for (let i = 0; i < firstDow; i++) out.push({ day: '', date: null })
  for (let d = 1; d <= daysInMonth; d++) {
    out.push({ day: d, date: new Date(viewYear.value, viewMonth.value, d) })
  }
  while (out.length % 7 !== 0) out.push({ day: '', date: null })
  return out
})

const isSameYMD = (a, b) =>
  a &&
  b &&
  a.getFullYear() === b.getFullYear() &&
  a.getMonth() === b.getMonth() &&
  a.getDate() === b.getDate()

const isOutsideRange = (d) => {
  if (!d) return false
  const min = minDate.value
  const max = maxDate.value
  if (min && d.getTime() < min.getTime()) return true
  if (max && d.getTime() > max.getTime()) return true
  return false
}

const cellClass = (cell) => {
  if (!cell.date) return 'empty'
  return {
    today: isSameYMD(cell.date, today),
    selected: isSameYMD(cell.date, selected.value),
    disabled: isOutsideRange(cell.date),
  }
}

const prevMonth = () => {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value -= 1
  } else {
    viewMonth.value -= 1
  }
}

const nextMonth = () => {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value += 1
  } else {
    viewMonth.value += 1
  }
}

const select = (cell) => {
  if (!cell?.date) return
  if (isOutsideRange(cell.date)) return
  emit('update:modelValue', formatISO(cell.date))
  open.value = false
}

const setToday = () => {
  const d = new Date()
  if (isOutsideRange(d)) return
  emit('update:modelValue', formatISO(d))
  open.value = false
}

const clear = () => {
  emit('update:modelValue', '')
  open.value = false
}

const toggle = () => {
  if (props.disabled) return
  open.value = !open.value
}

const onDocClick = (e) => {
  if (!open.value) return
  const el = root.value
  if (!el) return
  if (e?.target && el.contains(e.target)) return
  open.value = false
}

onMounted(() => document.addEventListener('mousedown', onDocClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocClick))
</script>

<style scoped>
.dp {
  position: relative;
}
.dp-input {
  position: relative;
}
.dp-input input {
  width: 100%;
  padding: 12px 40px 12px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
}
.dp-input.disabled {
  opacity: 0.7;
}
.dp-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  opacity: 0.8;
}
.dp-pop {
  position: absolute;
  z-index: 20;
  margin-top: 8px;
  width: 320px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  padding: 14px;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
}
.dp-head {
  display: grid;
  grid-template-columns: 32px 1fr 32px;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}
.title {
  text-align: center;
  font-weight: 700;
  color: #0f172a;
}
.nav {
  border: 1px solid #ddd;
  background: #fff;
  border-radius: 6px;
  cursor: pointer;
  height: 32px;
}
.dow {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  margin-bottom: 6px;
}
.dow-cell {
  text-align: center;
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
}
.grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}
.cell {
  height: 34px;
  border-radius: 8px;
  border: 1px solid #eef2f7;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #0f172a;
  background: #fff;
  cursor: pointer;
  padding: 0;
}
.cell:disabled {
  cursor: default;
  opacity: 1;
}
.cell.disabled {
  opacity: 0.45;
}
.cell.empty {
  border: 1px dashed #f1f5f9;
  background: #fafafa;
}
.cell.today {
  border-color: #93c5fd;
  background: #eff6ff;
}
.cell.selected {
  border-color: #86efac;
  background: #f0fdf4;
}
.num {
  font-size: 13px;
  font-weight: 600;
}
.dp-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
}
.btn-link {
  border: 0;
  background: transparent;
  color: #0b3a6e;
  cursor: pointer;
  font-weight: 700;
}
</style>
