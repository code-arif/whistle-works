<?php

namespace App\Http\Controllers\Web\Backend;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Basic user stats with growth metrics
            $userStats = [
                'total' => DB::table('users')->count(),
                'directors' => $this->getUserCountByRole('director'),
                'evaluators' => $this->getUserCountByRole('evaluator'),
                'referees' => $this->getUserCountByRole('referee'),
                'active' => DB::table('users')->where('status', 'active')->count(),
                'new_this_month' => DB::table('users')
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'last_month' => DB::table('users')
                    ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', Carbon::now()->subMonth()->year)
                    ->count(),
            ];

            // Calculate growth percentage
            $userStats['growth_percentage'] = $userStats['last_month'] > 0
                ? round((($userStats['new_this_month'] - $userStats['last_month']) / $userStats['last_month']) * 100, 1)
                : 0;

            // Sports types stats
            $sportsStats = [
                'total' => DB::table('sports_types')->count(),
                'active' => DB::table('sports_types')->where('status', 'active')->count(),
                'inactive' => DB::table('sports_types')->where('status', 'inactive')->count(),
                'camps_by_sport' => DB::table('camps')
                    ->select('sports_type_name', DB::raw('count(*) as total'))
                    ->whereNotNull('sports_type_name')
                    ->groupBy('sports_type_name')
                    ->orderByDesc('total')
                    ->limit(10)
                    ->get()
            ];

            // Camp stats with enhanced metrics
            $campStats = [
                'total' => DB::table('camps')->count(),
                'active' => DB::table('camps')->where('status', 'active')->count(),
                'upcoming' => DB::table('camps')->where('start_date', '>', Carbon::now())->count(),
                'ongoing' => DB::table('camps')
                    ->where('start_date', '<=', Carbon::now())
                    ->where('end_date', '>=', Carbon::now())
                    ->count(),
                'completed' => DB::table('camps')->where('end_date', '<', Carbon::now())->count(),
                'this_month' => DB::table('camps')
                    ->whereMonth('start_date', Carbon::now()->month)
                    ->whereYear('start_date', Carbon::now()->year)
                    ->count(),
                'last_month' => DB::table('camps')
                    ->whereMonth('start_date', Carbon::now()->subMonth()->month)
                    ->whereYear('start_date', Carbon::now()->subMonth()->year)
                    ->count(),
                'monthly' => DB::table('camps')
                    ->select(
                        DB::raw('MONTH(start_date) as month'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereYear('start_date', Carbon::now()->year)
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
            ];

            // Calculate camp growth
            $campStats['growth_percentage'] = $campStats['last_month'] > 0
                ? round((($campStats['this_month'] - $campStats['last_month']) / $campStats['last_month']) * 100, 1)
                : 0;

            // Schedule stats
            $scheduleStats = [
                'total' => DB::table('schedules')->count(),
                'published' => DB::table('schedules')->where('status', 'published')->count(),
                'draft' => DB::table('schedules')->where('status', 'draft')->count(),
            ];

            // Game slot stats with weekly comparison
            $gameSlotStats = [
                'total' => DB::table('game_slots')->count(),
                'available' => DB::table('game_slots')->where('status', 'available')->count(),
                'assigned' => DB::table('game_slots')->where('status', 'assigned')->count(),
                'completed' => DB::table('game_slots')->where('status', 'completed')->count(),
                'blocked' => DB::table('game_slots')->where('is_block', true)->count(),
                'today' => DB::table('game_slots')->whereDate('game_date', Carbon::today())->count(),
                'this_week' => DB::table('game_slots')
                    ->whereBetween('game_date', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                    ->count(),
                'last_week' => DB::table('game_slots')
                    ->whereBetween('game_date', [
                        Carbon::now()->subWeek()->startOfWeek(),
                        Carbon::now()->subWeek()->endOfWeek()
                    ])
                    ->count(),
                'weekly_slots' => DB::table('game_slots')
                    ->select(
                        DB::raw('DATE(game_date) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereBetween('game_date', [
                        Carbon::now()->subDays(30),
                        Carbon::now()
                    ])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
            ];

            // Calculate game slot growth
            $gameSlotStats['growth_percentage'] = $gameSlotStats['last_week'] > 0
                ? round((($gameSlotStats['this_week'] - $gameSlotStats['last_week']) / $gameSlotStats['last_week']) * 100, 1)
                : 0;

            // Crew stats
            $totalCrews = DB::table('crews')->count();
            $totalMembers = DB::table('crew_members')->count();
            $avgMembers = $totalCrews > 0 ? round($totalMembers / $totalCrews, 1) : 0;

            $crewStats = [
                'total' => $totalCrews,
                'active' => DB::table('crews')->where('status', 'active')->count(),
                'total_members' => $totalMembers,
                'avg_members' => $avgMembers,
                'crew_distribution' => DB::table('crews')
                    ->select('crews.*', DB::raw('COUNT(crew_members.id) as members_count'))
                    ->leftJoin('crew_members', 'crews.id', '=', 'crew_members.crew_id')
                    ->groupBy('crews.id', 'crews.camp_id', 'crews.name', 'crews.description', 'crews.status', 'crews.created_at', 'crews.updated_at')
                    ->orderByDesc('members_count')
                    ->limit(5)
                    ->get()
            ];

            // Recent activities (limit to 5 for better UI)
            $recentCamps = DB::table('camps')
                ->latest('created_at')
                ->limit(5)
                ->get();

            $recentSchedules = DB::table('schedules')
                ->latest('created_at')
                ->limit(5)
                ->get();

            // Revenue with comparison
            $revenue = [
                'total' => DB::table('camps')->sum('price') ?? 0,
                'this_month' => DB::table('camps')
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('price') ?? 0,
                'last_month' => DB::table('camps')
                    ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', Carbon::now()->subMonth()->year)
                    ->sum('price') ?? 0,
            ];

            // Calculate revenue growth
            $revenue['growth_percentage'] = $revenue['last_month'] > 0
                ? round((($revenue['this_month'] - $revenue['last_month']) / $revenue['last_month']) * 100, 1)
                : 0;

            // Daily activities (for timeline)
            $dailyActivities = $this->getDailyActivities();

            return view('backend.layouts.dashboard', compact(
                'userStats',
                'sportsStats',
                'campStats',
                'scheduleStats',
                'gameSlotStats',
                'crewStats',
                'recentCamps',
                'recentSchedules',
                'revenue',
                'dailyActivities'
            ));
        } catch (\Exception $e) {
            // Log the error
            Log::error('Dashboard Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Return error view or redirect
            return back()->with('error', 'Dashboard loading failed: ' . $e->getMessage());
        }
    }

    /**
     * Get user count by role name safely
     */
    private function getUserCountByRole($roleName)
    {
        try {
            $roleExists = DB::table('roles')->where('name', $roleName)->exists();

            if (!$roleExists) {
                return 0;
            }

            return DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', $roleName)
                ->where('model_has_roles.model_type', 'App\Models\User')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get daily activities for timeline
     */
    private function getDailyActivities()
    {
        $activities = [];

        try {
            // Get recent camps
            $recentCamps = DB::table('camps')
                ->latest('created_at')
                ->limit(3)
                ->get();

            foreach ($recentCamps as $camp) {
                $activities[] = [
                    'type' => 'camp',
                    'icon' => 'fe-campground',
                    'color' => 'primary',
                    'title' => 'New Camp Created',
                    'description' => $camp->camp_name,
                    'time' => Carbon::parse($camp->created_at)->diffForHumans(),
                    'created_at' => $camp->created_at
                ];
            }

            // Get recent schedules
            $recentSchedules = DB::table('schedules')
                ->latest('created_at')
                ->limit(2)
                ->get();

            foreach ($recentSchedules as $schedule) {
                $activities[] = [
                    'type' => 'schedule',
                    'icon' => 'fe-calendar',
                    'color' => 'secondary',
                    'title' => 'Schedule Published',
                    'description' => 'New schedule added',
                    'time' => Carbon::parse($schedule->created_at)->diffForHumans(),
                    'created_at' => $schedule->created_at
                ];
            }

            // Sort by creation time
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return array_slice($activities, 0, 5);
        } catch (\Exception $e) {
            return [];
        }
    }
}
