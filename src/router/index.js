import { createRouter, createWebHashHistory } from 'vue-router'
import DashboardView from '../components/DashboardView.vue'
import ActivityLogView from '../components/ActivityLogView.vue'
import ChallengesView from '../components/ChallengesView.vue'
import TipsView from '../components/TipsView.vue'
import ProfileView from '../components/ProfileView.vue'
import FriendsView from '../components/FriendsView.vue'
import ChallengeDetailsView from '../components/ChallengeDetailsView.vue'
import LeaderboardView from '../components/LeaderboardView.vue'
import EmissionFactorView from '../components/EmissionFactorView.vue'
import AdminDashboard from '../components/AdminDashboard.vue'
import AddBadgesView from '../components/AddBadgesView.vue' 
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    redirect: '/profile'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    beforeEnter: () => {
      const auth = useAuthStore()

      if (auth.user?.role === 'admin') {
        return {
          name: 'AdminDashboard'
        }
      }

      return true
    },
    component: DashboardView
  },
  {
    path: '/admin-dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard
  },
  {
    path: '/admin/add-badges',
    name: 'AddBadges',
    component: AddBadgesView,
    beforeEnter: () => {
      const auth = useAuthStore()
      if (!auth.isAuthenticated || auth.user?.role !== 'admin') {
        return { name: 'Dashboard' }
      }
      return true
    }
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
    path: '/challenges/:id',
    name: 'ChallengeDetails',
    component: ChallengeDetailsView
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
  },
  {
    path: '/friends',
    name: 'Friends',
    component: FriendsView
  },
  {
    path: '/leaderboard',
    name: 'Leaderboard',
    component: LeaderboardView
  },
  {
    path: '/emission-factors',
    name: 'EmissionFactor',
    component: EmissionFactorView
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

// Modernized global routing guard matching standard Vue Router specifications
router.beforeEach((to) => {
  const auth = useAuthStore()
  
  // Guard admin layout routes securely
  if (to.meta.requiresAdmin && (!auth.isAuthenticated || auth.user?.role !== 'admin')) {
    return { name: 'Dashboard' }
  }

  // Guard standard user routes securely
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'Profile' }
  }
})

export default router