<template>
  <div class="dashboard-layout">
    <header class="navbar">
      <div class="brand">Grad System (Faculty Portal)</div>
      <div class="user-info">
        <span v-if="user.username">Prof. {{ user.username }}</span>
        <button @click="logout" class="btn-logout">Logout</button>
      </div>
    </header>

    <div class="main-container">
      <aside class="sidebar">
        <nav>
          <ul>
            <li :class="{ active: activeSection === 'research' }" @click="activeSection = 'research'">Research Hold</li>
            <li :class="{ active: activeSection === 'overview' }" @click="activeSection = 'overview'">My Teaching</li>
            <li :class="{ active: activeSection === 'assignments' }" @click="activeSection = 'assignments'">Assignments</li>
            <li :class="{ active: activeSection === 'docs' }" @click="activeSection = 'docs'">Advisee Docs</li>
            <li :class="{ active: activeSection === 'thesis' }" @click="activeSection = 'thesis'">Thesis / Project</li>
            <li :class="{ active: activeSection === 'mp' }" @click="activeSection = 'mp'">MP Requests</li>
          </ul>
        </nav>
      </aside>

      <main class="content">
        <div v-if="activeSection === 'research'" class="card mb-30">
          <h2>Research Method Hold Check (Term 3)</h2>
          <p class="subtitle">
            You can lift the <strong>research_method</strong> hold only after the student has registered/taken the Research Method course.
          </p>

            <div v-if="holdsLoading" class="loading-text">Loading...</div>
            <div v-else-if="researchHolds.length === 0" class="empty-state">No active research_method holds for your advisees.</div>
            <div v-else class="review-list">
              <div v-for="h in researchHolds" :key="`${h.student_id}-${h.term_code || ''}`" class="review-item">
                <div class="info">
                  <span class="student-name">{{ h.student_username || `#${h.student_id}` }} (#{{ h.student_id }})</span>
                  <span class="file-link">
                    Hold: research_method · Term: {{ h.term_code || '-' }} · Research Method:
                    <strong>{{ Number(h.has_research_method) ? 'YES' : 'NO' }}</strong>
                  </span>
                  <span v-if="h.proof_doc_id" class="file-link">
                    Proof uploaded: <strong>{{ String(h.proof_status || 'pending') }}</strong>
                    <span v-if="h.proof_upload_date" class="muted"> · {{ h.proof_upload_date }}</span>
                  </span>
                </div>
                <div class="actions">
                  <a v-if="h.proof_doc_id" :href="docUrl({ doc_id: h.proof_doc_id })" target="_blank" class="btn-view">View Proof</a>
                  <button class="btn-approve" @click="liftResearchHold(h)" :disabled="!Number(h.has_research_method) || busyStudentId === String(h.student_id)">
                    {{ busyStudentId === String(h.student_id) ? 'Working...' : 'Lift Hold' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

        <div v-else-if="activeSection === 'overview'" class="stack">
          <div class="card mb-30">
            <h2>My Teaching Courses</h2>
            <div v-if="!facultyCoursesEnabled" class="msg bad">
              Teaching courses table not found. Run <code>backend/sql/10_faculty_courses.sql</code> in MySQL.
            </div>
            <div v-else-if="overviewMsg" class="msg" :class="{ ok: overviewOk, bad: !overviewOk }">{{ overviewMsg }}</div>

            <div class="form-row">
              <select v-model="selectedTeachCourse" class="select">
                <option disabled value="">-- Select a course to add --</option>
                <option v-for="c in allCourses" :key="c.course_code" :value="c.course_code">
                  {{ c.course_code }} · {{ c.course_name }} ({{ c.credits }})
                </option>
              </select>
              <button class="btn-approve" @click="addTeachCourse" :disabled="!selectedTeachCourse || !facultyCoursesEnabled || teachBusy">
                {{ teachBusy ? 'Working...' : 'Add' }}
              </button>
            </div>

            <div v-if="teachCourses.length === 0" class="empty-state">No courses added.</div>
            <div v-else class="table mt-20">
              <div class="row header">
                <div>Course</div>
                <div>Name</div>
                <div>Credits</div>
                <div></div>
              </div>
              <div v-for="c in teachCourses" :key="c.course_code" class="row">
                <div><strong>{{ c.course_code }}</strong></div>
                <div>{{ c.course_name || '-' }}</div>
                <div>{{ c.credits || '-' }}</div>
                <div class="actions">
                  <button class="btn-reject" @click="removeTeachCourse(c.course_code)" :disabled="teachBusy">Remove</button>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <h2>My Advisees</h2>
            <div class="form-row" style="margin: 10px 0 6px">
              <input v-model="adviseeSearch" class="select" type="text" placeholder="Search by name / username / email / id..." />
              <button class="btn-view" @click="fetchOverview" :disabled="teachBusy">Refresh</button>
            </div>
            <div v-if="filteredAdvisees.length === 0" class="empty-state">No advisees found.</div>
            <div v-else class="table mt-20">
              <div class="row header">
                <div>Student</div>
                <div>Email</div>
                <div>Cohort</div>
                <div>Status</div>
              </div>
              <div v-for="s in filteredAdvisees" :key="s.student_id" class="row">
                <div>
                  <strong>{{ displayAdviseeName(s) }}</strong>
                  <span class="muted"> (#{{ s.student_id }})</span>
                </div>
                <div>{{ s.student_email || '-' }}</div>
                <div>{{ s.entry_term_code || '-' }}</div>
                <div>{{ s.mp_status }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeSection === 'assignments'" class="stack">
          <div class="card mb-30">
            <h2>Create Assignment</h2>
            <div class="form-grid">
              <div class="form-group">
                <label>Title</label>
                <input v-model="assnForm.title" type="text" placeholder="e.g. HW1: Reading Summary" />
              </div>
              <div class="form-group">
                <label>Due (optional)</label>
                <input v-model="assnForm.due_at" type="datetime-local" />
              </div>
              <div class="form-group full">
                <label>Description (optional)</label>
                <textarea v-model="assnForm.description" rows="4" placeholder="Details..."></textarea>
              </div>

              <div class="form-group">
                <label>Target</label>
                <select v-model="assnForm.target_mode">
                  <option value="all">All Students</option>
                  <option value="course" :disabled="!assignmentCoursesEnabled || assignmentCourses.length === 0">Course</option>
                  <option value="students">Selected Students</option>
                </select>
              </div>

              <div class="form-group" v-if="assnForm.target_mode === 'course'">
                <label>Course</label>
                <div v-if="!assignmentCoursesEnabled" class="msg bad" style="margin: 0 0 10px">
                  Teaching courses table not found. Add courses in <strong>My Teaching</strong> first.
                </div>
                <select v-model="assnForm.course_code" :disabled="!assignmentCoursesEnabled || assignmentCourses.length === 0">
                  <option disabled value="">-- Select Course --</option>
                  <option v-for="c in assignmentCourses" :key="c.course_code" :value="c.course_code">
                    {{ c.course_code }}{{ c.course_name ? ` · ${c.course_name}` : '' }}
                  </option>
                </select>
              </div>

              <div class="form-group full" v-if="assnForm.target_mode === 'students'">
                <label>Students</label>
                <select v-model="selectedStudentIds" multiple class="multi">
                  <option v-for="s in students" :key="s.student_id" :value="String(s.student_id)">
                    {{ s.username }} (#{{ s.student_id }}) {{ s.entry_term_code ? `· ${s.entry_term_code}` : '' }}
                  </option>
                </select>
                <div class="hint">Hold Ctrl/Cmd to select multiple.</div>
              </div>

              <div class="form-group full">
                <label>Attachment (optional)</label>
                <input type="file" @change="onAssnFileChange" />
              </div>
            </div>

            <div class="form-actions">
              <button class="btn-approve" @click="createAssignment" :disabled="assnBusy">
                {{ assnBusy ? 'Creating...' : 'Publish Assignment' }}
              </button>
            </div>

            <div v-if="assnMsg" class="msg" :class="{ ok: assnOk, bad: !assnOk }">{{ assnMsg }}</div>
          </div>

          <div class="card mb-30">
            <h2>My Assignments</h2>
            <div v-if="assignmentsLoading" class="loading-text">Loading...</div>
            <div v-else-if="assignments.length === 0" class="empty-state">No assignments yet.</div>
            <div v-else class="review-list">
              <div v-for="a in assignments" :key="a.id" class="review-item">
                <div class="info">
                  <span class="student-name">{{ a.title }} (#{{ a.id }})</span>
                  <span class="file-link">
                    Created: {{ a.created_at }} <span v-if="a.due_at">· Due: {{ a.due_at }}</span> · Submissions:
                    <strong>{{ a.submissions_count }}</strong>
                  </span>
                  <span class="file-link">{{ summarizeTargets(a.targets) }}</span>
                </div>
                <div class="actions">
                  <button class="btn-view" @click="openAssignment(a)">View</button>
                  <button v-if="a.attachment_path" class="btn-view" @click="downloadAssignment(a)">Attachment</button>
                  <button class="btn-view" @click="openEditAssignment(a)">Edit</button>
                  <button class="btn-reject" @click="deleteAssignment(a)">Delete</button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedAssignment" class="card">
            <h2>Submissions · {{ selectedAssignment.title }}</h2>
            <div v-if="submissionsError" class="msg bad">{{ submissionsError }}</div>
            <div v-if="submissionsLoading" class="loading-text">Loading submissions...</div>
            <div v-else class="review-list">
              <div v-for="r in submissions" :key="r.student_id" class="review-item">
                <div class="info">
                  <span class="student-name">{{ displayStudentName(r) }} (#{{ r.student_id }})</span>
                  <span class="file-link">
                    {{ r.submission_id ? `Submitted: ${r.submitted_at}` : 'Not submitted yet.' }}
                    <span v-if="r.entry_term_code">· Cohort: {{ r.entry_term_code }}</span>
                  </span>
                  <span v-if="r.submission_id" class="file-link">
                    Grade:
                    <strong v-if="r.grade !== null && r.grade !== undefined">{{ r.grade }}</strong>
                    <span v-else class="muted">-</span>
                    <span v-if="r.graded_at" class="muted"> · graded_at: {{ r.graded_at }}</span>
                  </span>
                </div>
                <div class="actions">
                  <button v-if="r.submission_id" class="btn-view" @click="downloadSubmission(r)">Download</button>
                  <button v-if="r.submission_id" class="btn-view" @click="openSubmissionComments(r)">
                    Comments ({{ Number(r.comments_count || 0) }})
                  </button>
                  <div v-if="r.submission_id" class="grade-box">
                    <input
                      class="grade-input"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      :value="gradeDraft[r.submission_id] ?? ''"
                      placeholder="0-100"
                      @input="(e) => setGradeDraft(r.submission_id, e?.target?.value)"
                      :disabled="gradeBusyId === String(r.submission_id)"
                    />
                    <button class="btn-approve" @click="saveGrade(r)" :disabled="gradeBusyId === String(r.submission_id)">
                      {{ gradeBusyId === String(r.submission_id) ? 'Saving...' : 'Save' }}
                    </button>
                    <button class="btn-reject" @click="clearGrade(r)" :disabled="gradeBusyId === String(r.submission_id)">
                      Clear
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="gradeMsg" class="msg" :class="{ ok: gradeOk, bad: !gradeOk }">{{ gradeMsg }}</div>
          </div>
        </div>

        <div v-else-if="activeSection === 'docs'" class="card mb-30">
          <h2>My Advisee Documents</h2>
          <p class="subtitle">Documents submitted by students who selected you as their Major Professor.</p>

          <div class="form-row" style="margin: 10px 0 6px">
            <input v-model="adviseeDocsSearch" class="select" type="text" placeholder="Search by name / username / email / id..." />
            <select v-model="adviseeDocsTerm" class="select" style="min-width: 220px">
              <option value="">All terms</option>
              <option v-for="t in adviseeDocsTerms" :key="t" :value="t">{{ t }}</option>
            </select>
            <button class="btn-view" @click="fetchAdviseeDocs" :disabled="teachBusy">Refresh</button>
          </div>

          <div v-if="filteredAdviseeDocs.length === 0" class="empty-state">No advisee documents.</div>
          <div v-else class="review-list">
            <div v-for="doc in filteredAdviseeDocs" :key="doc.doc_id" class="review-item">
              <div class="info">
                <span class="student-name">
                  {{ displayStudentName(doc) }} (#{{ doc.student_id }}) <span v-if="doc.entry_term_code" class="muted">· {{ doc.entry_term_code }}</span>
                </span>
                <div class="doc-meta-row">
                  <span class="doc-source-pill">{{ docTypeLabel(doc.doc_type) }}</span>
                  <span class="doc-format-pill">{{ fileFormatLabel(doc.file_path) }}</span>
                  <span class="doc-status-pill" :class="statusPillClass(doc.status)">{{ statusLabel(doc.status) }}</span>
                  <span v-if="doc.upload_date" class="muted">· {{ doc.upload_date }}</span>
                </div>
              </div>
              <div class="actions">
                <a :href="docUrl(doc)" target="_blank" class="btn-view">View</a>
                <button class="btn-view" @click="openComments(doc)">Comments</button>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="activeSection === 'thesis'" class="stack">
          <div class="card mb-30">
            <h2>Defense Window (Admin-published)</h2>
            <div v-if="defenseWindowsLoading" class="loading-text">Loading...</div>
            <div v-else-if="defenseWindows.length === 0" class="empty-state">No defense windows published yet.</div>
            <div v-else class="table">
              <div class="row header defense-row">
                <div>Year</div>
                <div>Start</div>
                <div>End</div>
              </div>
              <div v-for="w in defenseWindows" :key="w.year" class="row defense-row">
                <div><strong>{{ w.year }}</strong></div>
                <div>{{ w.start_date }}</div>
                <div>{{ w.end_date }}</div>
              </div>
            </div>
          </div>

          <div class="card">
            <h2>Advisee Thesis / Project Timeline</h2>
            <div class="form-row" style="margin: 10px 0 6px">
              <input v-model="thesisSearch" class="select" type="text" placeholder="Search advisees..." />
              <button class="btn-view" @click="fetchThesisTimeline" :disabled="thesisLoading">Refresh</button>
            </div>
            <div v-if="thesisLoading" class="loading-text">Loading...</div>
            <div v-else-if="filteredThesisRows.length === 0" class="empty-state">No data.</div>
            <div v-else class="table mt-20">
              <div class="row header thesis-row">
                <div>Student</div>
                <div>Type</div>
                <div>Submission</div>
                <div>Defense</div>
                <div>File</div>
                <div>Title</div>
              </div>
              <div v-for="r in filteredThesisRows" :key="r.student_id" class="row thesis-row">
                <div>
                  <strong>{{ r.student_username || `#${r.student_id}` }}</strong>
                  <span class="muted"> (#{{ r.student_id }})</span>
                </div>
                <div>{{ r.type || '-' }}</div>
                <div>{{ r.submission_date || '-' }}</div>
                <div>{{ r.defense_date || '-' }}</div>
                <div>
                  <div v-if="r.thesis_doc_id" class="actions">
                    <a :href="docUrl({ doc_id: r.thesis_doc_id })" target="_blank" class="btn-view">View</a>
                    <button class="btn-view" @click="openThesisDocComments(r)">Comments</button>
                  </div>
                  <span v-else class="muted">-</span>
                </div>
                <div>{{ r.title || '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="card">
          <h2>Major Professor Requests</h2>
          <p class="subtitle">Students requesting you as their advisor.</p>

          <div v-if="mpRequests.length === 0" class="empty-state">No new advising requests.</div>
          <div v-else class="review-list">
            <div v-for="stu in mpRequests" :key="stu.student_id" class="review-item highlight-item">
              <div class="info">
                <span class="student-name">{{ displayStudentName(stu) }} (#{{ stu.student_id }})</span>
                <span class="email-text">{{ stu.email }}</span>
              </div>
              <div class="actions">
                <button @click="respondMP(stu, 'reject')" class="btn-reject">Decline</button>
                <button @click="respondMP(stu, 'accept')" class="btn-approve">Accept Student</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <div v-if="showCommentsModal" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Document Comments</h3>
        <div class="comment-meta" v-if="activeDoc">
          <div>
            <strong>{{ activeDoc.student_username || `#${activeDoc.student_id}` }}</strong>
            <span> · {{ activeDoc.doc_type }} · doc_id={{ activeDoc.doc_id }}</span>
          </div>
        </div>

        <div class="comment-list">
          <div v-if="commentsLoading" class="loading-text">Loading comments...</div>
          <div v-else-if="comments.length === 0" class="empty-state">No comments yet.</div>
          <div v-else>
            <div v-for="c in comments" :key="c.id" class="comment-item">
              <div class="comment-head">
                <span class="comment-author">{{ c.author_username || c.author_role }}</span>
                <span class="comment-time">{{ c.created_at }}</span>
              </div>
              <div class="comment-body">{{ c.comment }}</div>
            </div>
          </div>
        </div>

        <textarea v-model="newComment" placeholder="Write a comment..." rows="4"></textarea>
        <div class="modal-actions">
          <button @click="closeComments" class="btn-cancel">Close</button>
          <button @click="submitComment" class="btn-confirm-approve" :disabled="commentSubmitting || !newComment.trim()">
            {{ commentSubmitting ? 'Posting...' : 'Post Comment' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showSubCommentsModal" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Submission Comments</h3>
        <div class="comment-meta" v-if="activeSubmission">
          <div>
            <strong>{{ activeSubmission.student_username || `#${activeSubmission.student_id}` }}</strong>
            <span> · submission_id={{ activeSubmission.submission_id }}</span>
          </div>
        </div>

        <div class="comment-list">
          <div v-if="subCommentsLoading" class="loading-text">Loading comments...</div>
          <div v-else-if="subComments.length === 0" class="empty-state">No comments yet.</div>
          <div v-else>
            <div v-for="c in subComments" :key="c.id" class="comment-item">
              <div class="comment-head">
                <span class="comment-author">{{ c.author_username || c.author_role }}</span>
                <span class="comment-time">{{ c.created_at }}</span>
              </div>
              <div class="comment-body">{{ c.comment }}</div>
            </div>
          </div>
        </div>

        <textarea v-model="subNewComment" placeholder="Write a comment..." rows="4"></textarea>
        <div class="modal-actions">
          <button @click="closeSubComments" class="btn-cancel">Close</button>
          <button
            @click="submitSubComment"
            class="btn-confirm-approve"
            :disabled="subCommentSubmitting || !subNewComment.trim() || !activeSubmission"
          >
            {{ subCommentSubmitting ? 'Posting...' : 'Post Comment' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showEditAssnModal" class="modal-overlay">
      <div class="modal-box wide-modal">
        <h3>Edit Assignment</h3>
        <div v-if="editAssnMsg" class="msg" :class="{ ok: editAssnOk, bad: !editAssnOk }">{{ editAssnMsg }}</div>

        <div v-if="editAssn" class="form-grid">
          <div class="form-group full">
            <label>Title</label>
            <input v-model="editAssn.title" type="text" />
          </div>
          <div class="form-group full">
            <label>Description</label>
            <textarea v-model="editAssn.description" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label>Due At</label>
            <input v-model="editAssn.due_at" type="datetime-local" />
          </div>
          <div class="form-group">
            <label>Target</label>
            <select v-model="editAssn.target_mode">
              <option value="all">All Students</option>
              <option value="course" :disabled="!assignmentCoursesEnabled || assignmentCourses.length === 0">Course</option>
              <option value="cohort" disabled>Cohort (Legacy)</option>
              <option value="students">Specific Students</option>
            </select>
          </div>
          <div class="form-group" v-if="editAssn.target_mode === 'cohort'">
            <label>Cohort Term Code (legacy)</label>
            <input :value="editAssn.cohort_term_code || ''" type="text" disabled />
          </div>
          <div class="form-group" v-if="editAssn.target_mode === 'course'">
            <label>Course</label>
            <select v-model="editAssn.course_code" class="select" :disabled="!assignmentCoursesEnabled || assignmentCourses.length === 0">
              <option disabled value="">-- Select course --</option>
              <option v-for="c in assignmentCourses" :key="c.course_code" :value="c.course_code">
                {{ c.course_code }}{{ c.course_name ? ` · ${c.course_name}` : '' }}
              </option>
            </select>
          </div>
          <div class="form-group full" v-if="editAssn.target_mode === 'students'">
            <label>Students</label>
            <select v-model="editAssn.student_ids" multiple class="multi">
              <option v-for="s in students" :key="s.student_id" :value="String(s.student_id)">
                {{ s.username }} (#{{ s.student_id }}) {{ s.entry_term_code ? `· ${s.entry_term_code}` : '' }}
              </option>
            </select>
            <div class="hint">Hold Ctrl/Cmd to select multiple.</div>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="closeEditAssignment" :disabled="editAssnBusy">Cancel</button>
          <button class="btn-confirm-approve" @click="saveEditAssignment" :disabled="editAssnBusy || !editAssn">
            {{ editAssnBusy ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api, { apiBaseURL } from '../api/client'

const router = useRouter()
const user = ref({})
const activeSection = ref('research')
const mpRequests = ref([])
const adviseeDocs = ref([])

const holdsLoading = ref(true)
const researchHolds = ref([])
const busyStudentId = ref('')

// Assignments
const students = ref([])
const assignmentCoursesEnabled = ref(true)
const assignmentCourses = ref([])
const assnForm = ref({
  title: '',
  description: '',
  due_at: '',
  target_mode: 'all',
  course_code: '',
})
const selectedStudentIds = ref([])
const assnFile = ref(null)
const assnBusy = ref(false)
const assnMsg = ref('')
const assnOk = ref(true)

const assignmentsLoading = ref(false)
const assignments = ref([])
const selectedAssignment = ref(null)
const submissionsLoading = ref(false)
const submissions = ref([])
const gradeDraft = ref({})
const gradeBusyId = ref('')
const gradeMsg = ref('')
const gradeOk = ref(true)
const submissionsError = ref('')

// Assignment edit/delete
const showEditAssnModal = ref(false)
const editAssn = ref(null)
const editAssnBusy = ref(false)
const editAssnMsg = ref('')
const editAssnOk = ref(true)

const showSubCommentsModal = ref(false)
const activeSubmission = ref(null)
const subComments = ref([])
const subCommentsLoading = ref(false)
const subNewComment = ref('')
const subCommentSubmitting = ref(false)

// Teaching & advisees overview
const facultyCoursesEnabled = ref(true)
const teachCourses = ref([])
const advisees = ref([])
const adviseeSearch = ref('')
const allCourses = ref([])
const selectedTeachCourse = ref('')
const teachBusy = ref(false)
const overviewMsg = ref('')
const overviewOk = ref(true)

// Thesis/Project timeline
const defenseWindowsLoading = ref(false)
const defenseWindows = ref([])
const thesisLoading = ref(false)
const thesisRows = ref([])
const thesisSearch = ref('')

// Advisee docs search
const adviseeDocsSearch = ref('')
const adviseeDocsTerm = ref('')

const showCommentsModal = ref(false)
const activeDoc = ref(null)
const comments = ref([])
const commentsLoading = ref(false)
const newComment = ref('')
const commentSubmitting = ref(false)

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    if (user.value.role !== 'faculty') return router.push('/dashboard')
    fetchMPRequests()
    fetchAdviseeDocs()
    fetchResearchMethodHolds()
    fetchAssignmentOptions()
    fetchAssignments()
    fetchOverview()
    fetchAllCourses()
    fetchThesisTimeline()
  } else {
    router.push('/')
  }
})

const docUrl = (req) => `${apiBaseURL}/download_document.php?doc_id=${req.doc_id}`

const docTypeLabel = (docType) => {
  const raw = String(docType || '').trim()
  if (!raw) return 'DOCUMENT'
  const map = {
    thesis_project: 'THESIS / PROJECT',
    major_professor_form: 'MAJOR PROFESSOR FORM',
    admission_letter: 'ADMISSION LETTER',
    research_method_proof: 'RESEARCH METHOD',
  }
  return map[raw] || raw.replace(/_/g, ' ').toUpperCase()
}

const fileFormatLabel = (filePath) => {
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
  }
  return map[ext] || ext.toUpperCase()
}

const statusLabel = (status) => {
  const s = String(status || '').trim().toLowerCase()
  if (!s) return '-'
  return s
}

const statusPillClass = (status) => {
  const s = String(status || '').trim().toLowerCase()
  if (s === 'approved') return 'st-approved'
  if (s === 'pending') return 'st-pending'
  if (s === 'rejected') return 'st-rejected'
  return 'st-other'
}

const openBlob = (blob) => {
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank', 'noopener,noreferrer')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

const fetchMPRequests = async () => {
  try {
    const res = await api.get('faculty_get_mp_requests.php')
    if (res.data.status === 'success') mpRequests.value = res.data.data
  } catch (e) {
    console.error(e)
  }
}

const fetchAdviseeDocs = async () => {
  try {
    const res = await api.get('faculty_get_advisee_documents.php')
    if (res.data.status === 'success') adviseeDocs.value = res.data.data || []
  } catch (e) {
    console.error(e)
  }
}

const fetchResearchMethodHolds = async () => {
  holdsLoading.value = true
  try {
    const res = await api.get('faculty_get_research_method_holds.php')
    if (res.data?.status === 'success') researchHolds.value = res.data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    holdsLoading.value = false
  }
}

const fetchDefenseWindows = async () => {
  defenseWindowsLoading.value = true
  try {
    const res = await api.get('defense_windows_list.php')
    if (res.data?.status === 'success') defenseWindows.value = res.data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    defenseWindowsLoading.value = false
  }
}

const fetchThesisTimeline = async () => {
  thesisLoading.value = true
  try {
    await fetchDefenseWindows()
    const res = await api.get('faculty_get_thesis_projects.php')
    if (res.data?.status === 'success') thesisRows.value = res.data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    thesisLoading.value = false
  }
}

const filteredThesisRows = computed(() => {
  const q = (thesisSearch.value || '').trim().toLowerCase()
  const rows = thesisRows.value || []
  if (!q) return rows
  return rows.filter((r) => {
    const fields = [
      r.student_id,
      r.student_username,
      r.student_email,
      r.first_name,
      r.last_name,
      r.title,
      r.type,
      r.submission_date,
      r.defense_date,
    ]
      .map((x) => String(x || '').toLowerCase())
      .join(' ')
    return fields.includes(q)
  })
})

const adviseeDocsTerms = computed(() => {
  const terms = (adviseeDocs.value || [])
    .map((d) => String(d.entry_term_code || '').trim())
    .filter((t) => t !== '')
  return Array.from(new Set(terms)).sort((a, b) => b.localeCompare(a))
})

const filteredAdviseeDocs = computed(() => {
  const q = (adviseeDocsSearch.value || '').trim().toLowerCase()
  const termFilter = (adviseeDocsTerm.value || '').trim().toUpperCase()
  const docs = adviseeDocs.value || []

  return docs.filter((d) => {
    if (termFilter && String(d.entry_term_code || '').toUpperCase() !== termFilter) return false
    if (!q) return true
    const fields = [
      d.student_id,
      d.student_username,
      d.student_email,
      d.first_name,
      d.last_name,
      d.entry_term_code,
      d.doc_type,
      d.status,
      d.file_path,
    ]
      .map((x) => String(x || '').toLowerCase())
      .join(' ')
    return fields.includes(q)
  })
})

const openThesisDocComments = async (r) => {
  if (!r?.thesis_doc_id) return
  await openComments({
    doc_id: r.thesis_doc_id,
    student_id: r.student_id,
    student_username: r.student_username,
    doc_type: 'thesis_project',
    file_path: r.thesis_file_path,
    status: r.thesis_doc_status,
  })
}

const liftResearchHold = async (h) => {
  busyStudentId.value = String(h.student_id)
  try {
    const res = await api.post('faculty_lift_research_method_hold.php', { student_id: h.student_id })
    if (res.data?.status === 'success') {
      alert(`Hold lifted. Registrar code: ${res.data.registrar_code || '(none)'}`)
      await fetchResearchMethodHolds()
    } else {
      alert(res.data?.message || 'Lift failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Network Error')
  } finally {
    busyStudentId.value = ''
  }
}

const fetchAssignmentOptions = async () => {
  try {
    const res = await api.get('faculty_assignment_options.php')
    if (res.data?.status === 'success') {
      assignmentCoursesEnabled.value = Boolean(res.data.courses_enabled)
      assignmentCourses.value = res.data.courses || []
      students.value = res.data.students || []
    }
  } catch (e) {
    console.error(e)
  }
}

const fetchAllCourses = async () => {
  try {
    const res = await api.get('get_courses.php')
    if (res.data?.status === 'success') allCourses.value = res.data.data || []
  } catch {}
}

const fetchOverview = async () => {
  overviewMsg.value = ''
  overviewOk.value = true
  try {
    const res = await api.get('faculty_get_overview.php')
    if (res.data?.status === 'success') {
      facultyCoursesEnabled.value = Boolean(res.data.faculty_courses_enabled)
      teachCourses.value = res.data.taught_courses || []
      advisees.value = res.data.advisees || []
    } else {
      overviewOk.value = false
      overviewMsg.value = res.data?.message || 'Failed to load overview.'
    }
  } catch (e) {
    overviewOk.value = false
    overviewMsg.value = e?.response?.data?.message || 'Failed to load overview.'
  }
}

const displayAdviseeName = (s) => {
  const first = String(s?.first_name || '').trim()
  const last = String(s?.last_name || '').trim()
  const full = `${first} ${last}`.trim()
  return full || s?.student_username || 'Student'
}

const filteredAdvisees = computed(() => {
  const q = adviseeSearch.value.trim().toLowerCase()
  const list = Array.isArray(advisees.value) ? [...advisees.value] : []

  const matches = (s) => {
    if (!q) return true
    const hay = [
      displayAdviseeName(s),
      s?.student_username,
      s?.student_email,
      String(s?.student_id ?? ''),
      s?.entry_term_code,
      s?.mp_status,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return hay.includes(q)
  }

  const key = (s) => {
    const first = String(s?.first_name || '').trim()
    const fallback = String(s?.student_username || '').trim()
    const ch = (first || fallback).charAt(0).toUpperCase()
    return ch || 'Z'
  }

  return list
    .filter(matches)
    .sort((a, b) => {
      const ka = key(a)
      const kb = key(b)
      if (ka !== kb) return ka.localeCompare(kb)
      return displayAdviseeName(a).localeCompare(displayAdviseeName(b))
    })
})

const addTeachCourse = async () => {
  if (!selectedTeachCourse.value) return
  teachBusy.value = true
  try {
    const res = await api.post('faculty_add_teaching_course.php', { course_code: selectedTeachCourse.value })
    if (res.data?.status === 'success') {
      selectedTeachCourse.value = ''
      await fetchOverview()
    } else {
      alert(res.data?.message || 'Failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed')
  } finally {
    teachBusy.value = false
  }
}

const removeTeachCourse = async (courseCode) => {
  teachBusy.value = true
  try {
    const res = await api.post('faculty_remove_teaching_course.php', { course_code: courseCode })
    if (res.data?.status === 'success') {
      await fetchOverview()
    } else {
      alert(res.data?.message || 'Failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed')
  } finally {
    teachBusy.value = false
  }
}

const fetchAssignments = async () => {
  assignmentsLoading.value = true
  try {
    const res = await api.get('faculty_list_assignments.php')
    if (res.data?.status === 'success') assignments.value = res.data.data || []
  } catch (e) {
    console.error(e)
  } finally {
    assignmentsLoading.value = false
  }
}

const onAssnFileChange = (e) => {
  assnFile.value = e?.target?.files?.[0] ?? null
}

const createAssignment = async () => {
  assnMsg.value = ''
  assnOk.value = true
  if (!assnForm.value.title.trim()) {
    assnOk.value = false
    assnMsg.value = 'Title is required.'
    return
  }
  if (assnForm.value.target_mode === 'course' && !assnForm.value.course_code) {
    assnOk.value = false
    assnMsg.value = 'Please select a course.'
    return
  }
  if (assnForm.value.target_mode === 'students' && selectedStudentIds.value.length === 0) {
    assnOk.value = false
    assnMsg.value = 'Please select at least one student.'
    return
  }

  assnBusy.value = true
  try {
    const form = new FormData()
    form.append('title', assnForm.value.title)
    form.append('description', assnForm.value.description || '')
    form.append('due_at', assnForm.value.due_at || '')
    form.append('target_mode', assnForm.value.target_mode)
    form.append('course_code', assnForm.value.course_code || '')
    form.append('student_ids', JSON.stringify(selectedStudentIds.value))
    if (assnFile.value) form.append('file', assnFile.value)

    const res = await api.post('faculty_create_assignment.php', form)
    if (res.data?.status === 'success') {
      assnOk.value = true
      assnMsg.value = 'Assignment published.'
      assnForm.value.title = ''
      assnForm.value.description = ''
      assnForm.value.due_at = ''
      assnForm.value.target_mode = 'all'
      assnForm.value.course_code = ''
      selectedStudentIds.value = []
      assnFile.value = null
      await fetchAssignments()
    } else {
      assnOk.value = false
      assnMsg.value = res.data?.message || 'Create failed.'
    }
  } catch (e) {
    assnOk.value = false
    assnMsg.value = e?.response?.data?.message || 'Create failed.'
  } finally {
    assnBusy.value = false
  }
}

const summarizeTargets = (targets) => {
  const t = targets || []
  if (t.some((x) => x.target_type === 'all')) return 'Target: All students'
  const course = t.find((x) => x.target_type === 'course')?.target_value
  if (course) return `Target: Course ${course}`
  const cohort = t.find((x) => x.target_type === 'cohort')?.target_value
  if (cohort) return `Target: Cohort ${cohort}`
  const n = t.filter((x) => x.target_type === 'student').length
  return `Target: ${n} student(s)`
}

const openAssignment = async (a) => {
  selectedAssignment.value = a
  submissions.value = []
  gradeDraft.value = {}
  gradeMsg.value = ''
  submissionsError.value = ''
  submissionsLoading.value = true
  try {
    const res = await api.get(`faculty_get_assignment_submissions.php?assignment_id=${a.id}`)
    if (res.data?.status === 'success') {
      submissions.value = res.data.data || []
      const draft = {}
      for (const r of submissions.value) {
        if (r.submission_id) draft[r.submission_id] = r.grade ?? ''
      }
      gradeDraft.value = draft
    }
  } catch (e) {
    submissionsError.value = e?.response?.data?.message || 'Failed to load submissions'
  } finally {
    submissionsLoading.value = false
  }
}

const toDatetimeLocal = (dt) => {
  if (!dt) return ''
  const s = String(dt).trim()
  if (!s) return ''
  if (s.includes('T')) return s.slice(0, 16)
  if (s.includes(' ')) return s.replace(' ', 'T').slice(0, 16)
  return s
}

const openEditAssignment = (a) => {
  editAssnMsg.value = ''
  editAssnOk.value = true

  const targets = a.targets || []
  let target_mode = 'all'
  let course_code = ''
  let cohort_term_code = ''
  let student_ids = []
  if (targets.some((t) => t.target_type === 'all')) {
    target_mode = 'all'
  } else if (targets.some((t) => t.target_type === 'course')) {
    target_mode = 'course'
    course_code = targets.find((t) => t.target_type === 'course')?.target_value || ''
  } else if (targets.some((t) => t.target_type === 'cohort')) {
    target_mode = 'cohort'
    cohort_term_code = targets.find((t) => t.target_type === 'cohort')?.target_value || ''
  } else {
    target_mode = 'students'
    student_ids = targets.filter((t) => t.target_type === 'student').map((t) => String(t.target_value || '')).filter(Boolean)
  }

  editAssn.value = {
    assignment_id: a.id,
    title: a.title || '',
    description: a.description || '',
    due_at: toDatetimeLocal(a.due_at),
    target_mode,
    course_code,
    cohort_term_code,
    student_ids,
  }
  showEditAssnModal.value = true
}

const closeEditAssignment = () => {
  showEditAssnModal.value = false
  editAssn.value = null
  editAssnMsg.value = ''
  editAssnOk.value = true
}

const saveEditAssignment = async () => {
  if (!editAssn.value) return
  editAssnMsg.value = ''
  editAssnOk.value = true

  const title = (editAssn.value.title || '').trim()
  if (!title) {
    editAssnOk.value = false
    editAssnMsg.value = 'Title is required.'
    return
  }
  if (editAssn.value.target_mode === 'course' && !editAssn.value.course_code) {
    editAssnOk.value = false
    editAssnMsg.value = 'Please select a course.'
    return
  }
  if (editAssn.value.target_mode === 'cohort' && !editAssn.value.cohort_term_code) {
    editAssnOk.value = false
    editAssnMsg.value = 'Missing cohort term code (legacy). Please switch target to Course or Students.'
    return
  }
  if (editAssn.value.target_mode === 'students' && (!editAssn.value.student_ids || editAssn.value.student_ids.length === 0)) {
    editAssnOk.value = false
    editAssnMsg.value = 'Please select at least one student.'
    return
  }

  editAssnBusy.value = true
  try {
    const res = await api.post('faculty_update_assignment.php', {
      assignment_id: editAssn.value.assignment_id,
      title: editAssn.value.title,
      description: editAssn.value.description,
      due_at: editAssn.value.due_at || '',
      target_mode: editAssn.value.target_mode,
      course_code: editAssn.value.course_code || '',
      cohort_term_code: editAssn.value.cohort_term_code || '',
      student_ids: editAssn.value.student_ids || [],
    })
    if (res.data?.status === 'success') {
      editAssnOk.value = true
      editAssnMsg.value = res.data?.message || 'Saved.'
      await fetchAssignments()
      if (selectedAssignment.value && selectedAssignment.value.id === editAssn.value.assignment_id) {
        // refresh selected assignment display
        selectedAssignment.value = assignments.value.find((x) => x.id === editAssn.value.assignment_id) || selectedAssignment.value
      }
      closeEditAssignment()
    } else {
      editAssnOk.value = false
      editAssnMsg.value = res.data?.message || 'Save failed.'
    }
  } catch (e) {
    editAssnOk.value = false
    editAssnMsg.value = e?.response?.data?.message || 'Save failed.'
  } finally {
    editAssnBusy.value = false
  }
}

const deleteAssignment = async (a) => {
  if (!confirm(`Delete assignment "${a.title}" (#${a.id})? This will also delete submissions.`)) return
  try {
    const res = await api.post('faculty_delete_assignment.php', { assignment_id: a.id })
    if (res.data?.status === 'success') {
      await fetchAssignments()
      if (selectedAssignment.value?.id === a.id) {
        selectedAssignment.value = null
        submissions.value = []
        submissionsError.value = ''
      }
    } else {
      alert(res.data?.message || 'Delete failed')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Delete failed')
  }
}

const setGradeDraft = (submissionId, value) => {
  gradeDraft.value = { ...gradeDraft.value, [submissionId]: value }
}

const saveGrade = async (r) => {
  if (!r?.submission_id) return
  gradeBusyId.value = String(r.submission_id)
  gradeMsg.value = ''
  gradeOk.value = true
  try {
    const raw = gradeDraft.value?.[r.submission_id]
    const payload = { submission_id: r.submission_id, grade: raw === '' ? null : raw }
    const res = await api.post('faculty_grade_assignment_submission.php', payload)
    if (res.data?.status === 'success') {
      gradeMsg.value = res.data?.message || 'Saved.'
      gradeOk.value = true
      // Update row locally
      r.grade = res.data.grade
      r.graded_at = res.data.graded_at ?? null
    } else {
      gradeMsg.value = res.data?.message || 'Save failed.'
      gradeOk.value = false
    }
  } catch (e) {
    gradeMsg.value = e?.response?.data?.message || 'Save failed.'
    gradeOk.value = false
  } finally {
    gradeBusyId.value = ''
  }
}

const clearGrade = async (r) => {
  if (!r?.submission_id) return
  setGradeDraft(r.submission_id, '')
  await saveGrade(r)
}

const downloadAssignment = async (a) => {
  try {
    const res = await api.get(`download_assignment_file.php?assignment_id=${a.id}`, { responseType: 'blob' })
    openBlob(res.data)
  } catch (e) {
    alert('Download failed')
  }
}

const downloadSubmission = async (r) => {
  if (!r.submission_id) return
  try {
    const res = await api.get(`download_assignment_submission.php?submission_id=${r.submission_id}`, { responseType: 'blob' })
    openBlob(res.data)
  } catch (e) {
    alert('Download failed')
  }
}

const openSubmissionComments = async (r) => {
  activeSubmission.value = r
  showSubCommentsModal.value = true
  subNewComment.value = ''
  subComments.value = []
  subCommentsLoading.value = true
  try {
    const res = await api.get(`assignment_submission_comments_list.php?submission_id=${r.submission_id}`)
    if (res.data?.status === 'success') subComments.value = res.data.data || []
  } finally {
    subCommentsLoading.value = false
  }
}

const closeSubComments = () => {
  showSubCommentsModal.value = false
  activeSubmission.value = null
  subComments.value = []
  subNewComment.value = ''
}

const submitSubComment = async () => {
  if (!activeSubmission.value?.submission_id) return
  subCommentSubmitting.value = true
  try {
    const res = await api.post('faculty_add_submission_comment.php', {
      submission_id: activeSubmission.value.submission_id,
      comment: subNewComment.value,
    })
    if (res.data?.status === 'success') {
      subNewComment.value = ''
      await openSubmissionComments(activeSubmission.value)
      await openAssignment(selectedAssignment.value)
    } else {
      alert(res.data?.message || 'Failed to post comment')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to post comment')
  } finally {
    subCommentSubmitting.value = false
  }
}

const displayStudentName = (stu) => {
  const name = `${stu.first_name || ''} ${stu.last_name || ''}`.trim()
  return name || stu.student_username || `Student`
}

const respondMP = async (stu, action) => {
  if (!confirm(`Are you sure you want to ${action.toUpperCase()} ${displayStudentName(stu)}?`)) return
  try {
    const res = await api.post('faculty_respond_mp.php', { student_id: stu.student_id, action })
    if (res.data.status === 'success') {
      alert('Success: ' + res.data.message)
      fetchMPRequests()
    } else {
      alert(res.data.message || 'Failed')
    }
  } catch (e) {
    alert('Network Error')
  }
}

const openComments = async (doc) => {
  activeDoc.value = doc
  showCommentsModal.value = true
  newComment.value = ''
  comments.value = []
  commentsLoading.value = true
  try {
    const res = await api.get(`document_comments_list.php?doc_id=${doc.doc_id}`)
    if (res.data.status === 'success') comments.value = res.data.data || []
    else alert(res.data.message || 'Failed to load comments')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to load comments')
  } finally {
    commentsLoading.value = false
  }
}

const closeComments = () => {
  showCommentsModal.value = false
  activeDoc.value = null
  comments.value = []
  newComment.value = ''
}

const submitComment = async () => {
  if (!activeDoc.value) return
  commentSubmitting.value = true
  try {
    const res = await api.post('document_comments_add.php', {
      doc_id: activeDoc.value.doc_id,
      comment: newComment.value,
    })
    if (res.data.status === 'success') {
      newComment.value = ''
      await openComments(activeDoc.value)
    } else {
      alert(res.data.message || 'Failed to post comment')
    }
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to post comment')
  } finally {
    commentSubmitting.value = false
  }
}

const logout = async () => {
  try {
    await api.post('logout.php')
  } catch {}
  localStorage.removeItem('user')
  router.push('/')
}
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  flex-direction: column;
  width: 100vw;
  height: 100vh;
  background-color: #f4f7fa;
}
.navbar {
  background-color: #003366;
  color: white;
  padding: 0 2rem;
  height: 64px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.btn-logout {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 6px 16px;
  border-radius: 4px;
  cursor: pointer;
}
.main-container {
  flex: 1;
  display: flex;
  overflow: hidden;
}
.sidebar {
  width: 260px;
  background: white;
  border-right: 1px solid #dee2e6;
  padding-top: 1rem;
}
.sidebar li {
  padding: 15px 25px;
  cursor: pointer;
  color: #495057;
}
.sidebar li.active {
  background-color: #e3f2fd;
  color: #003366;
  border-left: 4px solid #003366;
}
.content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}
.card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 30px;
}
.stack {
  width: 100%;
  max-width: 1100px;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group.full {
  grid-column: 1 / -1;
}
.form-group input,
.form-group select,
.form-group textarea {
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 10px;
}
.multi {
  min-height: 180px;
}
.hint {
  color: #6b7280;
  font-size: 12px;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}
.form-row {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.select {
  min-width: 320px;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}
.muted {
  color: #6b7280;
  font-weight: 400;
}
.table {
  width: 100%;
  border: 1px solid #eee;
  border-radius: 10px;
  overflow: hidden;
}
.table .row {
  display: grid;
  grid-template-columns: 1.3fr 1.4fr 0.6fr 0.6fr;
  gap: 10px;
  padding: 12px 14px;
  border-bottom: 1px solid #eee;
  align-items: center;
}
.table .row.header {
  background: #f8fafc;
  font-weight: 700;
  color: #003366;
}
.table .row.defense-row {
  grid-template-columns: 0.6fr 1fr 1fr;
}
.table .row.thesis-row {
  grid-template-columns: 1.5fr 0.7fr 0.9fr 0.9fr 1.2fr 1.6fr;
}
.grade-box {
  display: flex;
  gap: 8px;
  align-items: center;
}
.grade-input {
  width: 90px;
  padding: 8px 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}
.table .row:last-child {
  border-bottom: none;
}
.msg {
  padding: 12px 14px;
  border-radius: 8px;
  margin-top: 12px;
  font-weight: 600;
}
.msg.ok {
  background: #e8f7ee;
  border: 1px solid #b6e2c3;
  color: #1b7a3a;
}
.msg.bad {
  background: #fdebec;
  border: 1px solid #f5c2c7;
  color: #b4232c;
}
.review-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border: 1px solid #eee;
  border-radius: 8px;
  margin-bottom: 15px;
  background: white;
}
.highlight-item {
  border-left: 5px solid #003366;
  background-color: #fdfdfe;
}
.info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.doc-meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}
.doc-source-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #e7f0ff;
  color: #003366;
  font-weight: 800;
  letter-spacing: 0.3px;
  font-size: 12px;
  text-transform: uppercase;
}
.doc-format-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #f3f4f6;
  color: #374151;
  font-weight: 700;
  font-size: 12px;
}
.doc-status-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 12px;
  text-transform: uppercase;
  border: 1px solid transparent;
}
.doc-status-pill.st-approved {
  background: #e8f7ee;
  border-color: #b6e2c3;
  color: #1b7a3a;
}
.doc-status-pill.st-pending {
  background: #fff4d6;
  border-color: #ffe2a6;
  color: #8a5200;
}
.doc-status-pill.st-rejected {
  background: #fdebec;
  border-color: #f5c2c7;
  color: #b4232c;
}
.doc-status-pill.st-other {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #3730a3;
}
.student-name {
  font-weight: bold;
  font-size: 1.1rem;
}
.actions {
  display: flex;
  gap: 10px;
}
.btn-view,
.btn-reject,
.btn-approve {
  border: none;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  color: white;
}
.btn-view {
  background: #6c757d;
}
.btn-reject {
  background-color: #dc3545;
}
.btn-approve {
  background-color: #28a745;
}
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
.modal-box {
  background: white;
  width: 90%;
  max-width: 500px;
  padding: 25px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.wide-modal {
  max-width: 600px;
}
.course-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.btn-cancel {
  background: #fff;
  border: 1px solid #ccc;
  padding: 8px 15px;
  cursor: pointer;
}
.btn-confirm-reject,
.btn-confirm-approve {
  color: white;
  border: none;
  padding: 8px 15px;
  cursor: pointer;
}
.btn-confirm-reject {
  background: #dc3545;
}
.btn-confirm-approve {
  background: #28a745;
}
textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
}

.comment-list {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 12px;
  max-height: 260px;
  overflow-y: auto;
  background: #fafafa;
}
.comment-item {
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}
.comment-item:last-child {
  border-bottom: none;
}
.comment-head {
  display: flex;
  justify-content: space-between;
  color: #6b7280;
  font-size: 12px;
  margin-bottom: 6px;
}
.comment-author {
  font-weight: 700;
  color: #374151;
}
.comment-body {
  color: #111827;
  white-space: pre-wrap;
}
.comment-meta {
  color: #374151;
  font-size: 14px;
}
</style>
