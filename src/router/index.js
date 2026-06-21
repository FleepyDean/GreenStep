import { createRouter, createWebHashHistory } from 'vue-router'
import DashboardView from '../components/DashboardView.vue'
import ActivityLogView from '../components/ActivityLogView.vue'
import ChallengesView from '../components/ChallengesView.vue'
import TipsView from '../components/TipsView.vue'
import ProfileView from '../components/ProfileView.vue'
import FriendsView from '../components/FriendsView.vue'
import LeaderboardView from '../components/LeaderboardView.vue'
import ChallengeDetailsView from '../components/ChallengeDetailsView.vue'

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
  }
]

const router = createRouter({
  history: createWebHashHistory(), // Safe for Capacitor native environments
  routes
})

export default router