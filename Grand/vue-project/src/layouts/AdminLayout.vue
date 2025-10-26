<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { ref } from 'vue'

// 翻译为英文并包含所有 7 个导航项
const navItems = ref([
    { id: 'dashboard', name: 'Dashboard', path: '/admin/dashboard' },
    { id: 'users', name: 'User Management', path: '/admin/users' },
    { id: 'workflows', name: 'Workflows', path: '/admin/workflows' },
    { id: 'migration', name: 'Migration', path: '/admin/migration' },
    { id: 'reports', name: 'Reports', path: '/admin/reports' },
    { id: 'compliance', name: 'Compliance', path: '/admin/compliance' },
    { id: 'settings', name: 'Settings', path: '/admin/settings' }
]);
</script>

<template>
  <div class="portal-container">
    <nav class="sidebar">
      <h2>Admin Portal</h2> <ul class="sidebar-nav">
        <RouterLink v-for="item in navItems" :key="item.id" :to="item.path" v-slot="{ isActive }">
          <li :class="{ active: isActive }">
            {{ item.name }}
          </li>
        </RouterLink>
      </ul>
      <div class="sidebar-footer">
        <RouterLink to="/login" class="logout-link">&#8592; Log Out</RouterLink>
      </div>
    </nav>

    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>