<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { ref } from 'vue' // 确保导入 ref

// 这是从 faculty_portal.html 翻译并修改而来的
const navItems = ref([
    { id: 'dashboard', name: 'Dashboard', path: '/faculty/dashboard' },
    { id: 'progress', name: 'Advisee Progress', path: '/faculty/progress' },
    { id: 'approvals', name: 'Approval Requests', path: '/faculty/approvals' },
    { id: 'evaluations', name: 'Evaluation Reports', path: '/faculty/evaluations' },
    { id: 'notifications', name: 'Notifications', path: '/faculty/notifications' },
    { id: 'settings', name: 'Profile Settings', path: '/faculty/settings' }
]);
</script>

<template>
  <div class="portal-container">
    <nav class="sidebar"> <h2>Faculty Portal</h2> <ul class="sidebar-nav">
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