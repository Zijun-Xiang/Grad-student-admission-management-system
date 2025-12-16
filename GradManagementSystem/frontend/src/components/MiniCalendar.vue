<template>
  <div class="calendar">
    <div class="cal-header">
      <button class="nav" @click="prevMonth">&lt;</button>
      <div class="title">{{ title }}</div>
      <button class="nav" @click="nextMonth">&gt;</button>
    </div>

    <div class="dow">
      <div v-for="d in daysOfWeek" :key="d" class="dow-cell">{{ d }}</div>
    </div>

    <div class="grid">
      <div v-for="(cell, idx) in cells" :key="idx" class="cell" :class="cellClass(cell)">
        <span class="num">{{ cell.day }}</span>
      </div>
    </div>

    <div v-if="entryDateLabel" class="legend">
      <span class="dot entry"></span> Entry: {{ entryDateLabel }}
      <span class="dot today"></span> Today
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  entryDate: { type: String, default: '' }, // YYYY-MM-DD
})

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const parseDate = (s) => {
  if (!s) return null
  const d = new Date(s + 'T00:00:00')
  return isNaN(d.getTime()) ? null : d
}

const today = new Date()
const entry = computed(() => parseDate(props.entryDate))

const viewYear = ref(today.getFullYear())
const viewMonth = ref(today.getMonth()) // 0-11

watch(
  () => props.entryDate,
  (v) => {
    const d = parseDate(v)
    if (d) {
      viewYear.value = d.getFullYear()
      viewMonth.value = d.getMonth()
    }
  },
  { immediate: true },
)

const title = computed(() => {
  const dt = new Date(viewYear.value, viewMonth.value, 1)
  return dt.toLocaleString('en-US', { month: 'long', year: 'numeric' })
})

const entryDateLabel = computed(() => {
  const d = entry.value
  if (!d) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
})

const isSameYMD = (a, b) =>
  a &&
  b &&
  a.getFullYear() === b.getFullYear() &&
  a.getMonth() === b.getMonth() &&
  a.getDate() === b.getDate()

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

const cellClass = (cell) => {
  if (!cell.date) return 'empty'
  return {
    today: isSameYMD(cell.date, today),
    entry: isSameYMD(cell.date, entry.value),
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
</script>

<style scoped>
.calendar {
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 14px;
  background: #fff;
}
.cal-header {
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
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0f172a;
  background: #fff;
  position: relative;
}
.cell.empty {
  border: 1px dashed #f1f5f9;
  background: #fafafa;
}
.cell.today {
  border-color: #93c5fd;
  background: #eff6ff;
}
.cell.entry {
  border-color: #86efac;
  background: #f0fdf4;
}
.num {
  font-size: 13px;
  font-weight: 600;
}
.legend {
  margin-top: 10px;
  display: flex;
  gap: 14px;
  align-items: center;
  color: #475569;
  font-size: 12px;
  flex-wrap: wrap;
}
.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 6px;
  border: 1px solid transparent;
}
.dot.entry {
  background: #bbf7d0;
  border-color: #86efac;
}
.dot.today {
  background: #bfdbfe;
  border-color: #93c5fd;
}
</style>
