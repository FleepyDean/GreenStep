import { createRouter, createWebHashHistory } from 'vue-router'
import DashboardView from '../components/DashboardView.vue'
import ActivityLogView from '../components/ActivityLogView.vue'
import ChallengesView from '../components/ChallengesView.vue'
import TipsView from '../components/TipsView.vue'
import ProfileView from '../components/ProfileView.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardView
  },
  {
    path: '/activity',
    name: 'ActivityLog',
    component: ActivityLogView
  },
  {
    path: '/challenges',
    name: 'Challenges',
    component: ChallengesView
  },
  {
    path: '/tips',
    name: 'Tips',
    component: TipsView
  },
  {
    path: '/profile',
    name: 'Profile',
    component: ProfileView
  }
]

const router = createRouter({
  history: createWebHashHistory(), // Safe for Capacitor native environments
  routes
})

export default router