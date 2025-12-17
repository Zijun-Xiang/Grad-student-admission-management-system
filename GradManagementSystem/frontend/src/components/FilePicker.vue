<template>
  <div class="file-picker" :class="{ disabled }">
    <input
      ref="inputEl"
      class="file-input"
      type="file"
      :accept="accept"
      :disabled="disabled"
      @change="handleChange"
    />
    <button class="file-btn" type="button" :disabled="disabled" @click="triggerPick">
      {{ buttonText }}
    </button>
    <span class="file-name" :class="{ placeholder: !fileName }">
      {{ fileName || placeholder }}
    </span>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  accept: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  buttonText: { type: String, default: 'Choose file' },
  placeholder: { type: String, default: 'No file chosen' },
})

const emit = defineEmits(['change'])

const inputEl = ref(null)
const fileName = ref('')

const triggerPick = () => {
  if (props.disabled) return
  inputEl.value?.click?.()
}

const handleChange = (e) => {
  const file = e?.target?.files?.[0] ?? null
  fileName.value = file?.name || ''
  emit('change', file)
}
</script>

<style scoped>
.file-picker {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
}
.file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
.file-btn {
  background: #f8fafc;
  border: 1px solid #d1d5db;
  color: #0f172a;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 700;
}
.file-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.file-name {
  color: #0f172a;
  font-size: 14px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}
.file-name.placeholder {
  color: #6b7280;
}
</style>

