<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { ref } from 'vue'

// 这是从 student.html 翻译并修改而来的
const navItems = ref([
    { id: 'dashboard', name: 'Dashboard', path: '/student/dashboard' },
    { id: 'milestones', name: 'Milestone Tracker', path: '/student/milestones' },
    { id: 'program', name: 'Program of Study', path: '/student/program' },
    { id: 'committee', name: 'Committee', path: '/student/committee' },
    { id: 'documents', name: 'My Documents', path: '/student/documents' },
    { id: 'notifications', name: 'Notifications', path: '/student/notifications' },
    { id: 'profile', name: 'Profile', path: '/student/profile' }
]);
</script>

<template>
  <div class="portal-container">
    <nav class="sidebar"> <h2>Student Portal</h2> <ul class="sidebar-nav">
        <RouterLink v-for="item in navItems" :key="item.id" :to="item.path" v-slot="{ isActive }">
          <li :class="{ active: isActive }">
            {{ item.name }}
          </li>
        </RouterLink>
      </ul>
      <div class="sidebar-footer"> <RouterLink to="/login" class="logout-link">&#8592; Log Out</RouterLink>
      </div>
    </nav>

    <main class="main-content"> <RouterView />
    </main>
  </div>
</template>