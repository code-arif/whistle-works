<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ProfileHeaderCard from '@/Admin/Components/Profile/ProfileHeaderCard.vue';
import ProfileDetailsForm from '@/Admin/Components/Profile/ProfileDetailsForm.vue';
import ChangePasswordForm from '@/Admin/Components/Profile/ChangePasswordForm.vue';
import { 
  ShieldCheck, 
  KeyRound, 
  UserCheck, 
  Clock, 
  ExternalLink,
  Lock,
  ChevronRight
} from 'lucide-vue-next';

defineProps({
  profile: {
    type: Object,
    required: true,
  }
});
</script>

<template>
  <AdminLayout>
    <Head title="Account Profile Settings" />

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Executive Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <!-- Breadcrumb path -->
          <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1 font-mono">
            <Link href="/admin/v2/dashboard" class="hover:text-[#F29F67] transition-colors">Dashboard</Link>
            <ChevronRight class="w-3 h-3 text-slate-400" />
            <Link href="/admin/v2/settings" class="hover:text-[#F29F67] transition-colors">Settings</Link>
            <ChevronRight class="w-3 h-3 text-slate-400" />
            <span class="text-[#F29F67] font-semibold">Profile</span>
          </div>

          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            Account Profile Settings
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Manage your personal identity, contact coordinates, security credentials, and profile image.
          </p>
        </div>

        <!-- Quick link to System Settings -->
        <div class="flex items-center gap-2">
          <Link 
            href="/admin/v2/settings" 
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-md text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-[#1E1E2C] hover:bg-slate-50 dark:hover:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-2xs transition-colors"
          >
            <span>Global Settings</span>
            <ExternalLink class="w-3.5 h-3.5 text-slate-400" />
          </Link>
        </div>
      </div>

      <!-- Hero Profile Overview Card with Instant Avatar Uploader -->
      <ProfileHeaderCard :profile="profile" />

      <!-- Form Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Personal & Contact Information (7 cols on lg) -->
        <div class="lg:col-span-7">
          <ProfileDetailsForm :profile="profile" />
        </div>

        <!-- Right: Security & Credentials + Session Card (5 cols on lg) -->
        <div class="lg:col-span-5 space-y-6">
          <ChangePasswordForm />

          <!-- Security Diagnostics Overview Card -->
          <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
            <h4 class="text-xs uppercase font-mono font-bold tracking-wider text-slate-400 mb-4">
              Security Health Check
            </h4>

            <div class="space-y-3.5">
              <div class="flex items-center justify-between text-xs">
                <span class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <UserCheck class="w-4 h-4 text-[#34B1AA]" />
                  <span>Role Authorization</span>
                </span>
                <span class="font-mono font-semibold text-[#34B1AA]">
                  {{ profile.role }}
                </span>
              </div>

              <div class="flex items-center justify-between text-xs">
                <span class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <Lock class="w-4 h-4 text-emerald-500" />
                  <span>Password Hash</span>
                </span>
                <span class="font-mono text-emerald-500 font-semibold">
                  Bcrypt Active
                </span>
              </div>

              <div class="flex items-center justify-between text-xs">
                <span class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <Clock class="w-4 h-4 text-[#F29F67]" />
                  <span>Activity Status</span>
                </span>
                <span class="font-mono text-slate-500 dark:text-slate-400">
                  {{ profile.last_active }}
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>
