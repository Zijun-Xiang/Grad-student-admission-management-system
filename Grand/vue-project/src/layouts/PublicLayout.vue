<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { ref } from 'vue'

// 这是从 public.html 翻译并修改而来的
const navItems = ref([
    { id: 'home', name: 'Home', path: '/home' },
    { id: 'login', name: 'Login', path: '/login' },
    { id: 'help', name: 'Help', path: '/help' },
    { id: 'contact', name: 'Contact Us', path: '/contact' }
]);
</script>

<template>
  <div class="portal-container">
    <nav class="sidebar"> <h2>Graduate School</h2> <ul class="sidebar-nav">
        <RouterLink v-for="item in navItems" :key="item.id" :to="item.path" v-slot="{ isActive }">
          <li :class="{ active: isActive }">
            {{ item.name }}
          </li>
        </RouterLink>
      </ul>
      </nav>

    <main class="main-content"> <RouterView />
    </main>
  </div>
</template>