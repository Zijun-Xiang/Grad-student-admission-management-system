export function docTypeLabel(docType) {
  const raw = String(docType || '').trim()
  if (!raw) return 'DOCUMENT'

  const map = {
    thesis_project: 'THESIS / PROJECT',
    major_professor_form: 'MAJOR PROFESSOR FORM',
    admission_letter: 'ADMISSION LETTER',
    research_method: 'RESEARCH METHOD',
    research_method_proof: 'RESEARCH METHOD',
  }
  return map[raw] || raw.replace(/_/g, ' ').toUpperCase()
}

export function fileFormatLabel(filePath) {
  const fp = String(filePath || '').trim()
  const ext = fp.includes('.') ? fp.split('.').pop()?.toLowerCase() : ''
  if (!ext) return '-'
  const map = {
    pdf: 'PDF',
    doc: 'WORD',
    docx: 'WORD',
    jpg: 'JPG',
    jpeg: 'JPG',
    png: 'PNG',
    zip: 'ZIP',
    txt: 'TXT',
  }
  return map[ext] || ext.toUpperCase()
}

export function statusLabel(status) {
  const s = String(status || '').trim()
  return s || '-'
}

export function statusPillClass(status) {
  const s = String(status || '').trim().toLowerCase()
  if (s === 'approved') return 'st-approved'
  if (s === 'pending') return 'st-pending'
  if (s === 'rejected') return 'st-rejected'
  return 'st-other'
}
