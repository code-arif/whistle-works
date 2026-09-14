<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * User Business Service Instance.
     */
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a paginated, searchable, and filterable listing of Users.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search', 'role', 'status', 'sort_by', 'sort_order', 'per_page', 'date_from', 'date_to'
        ]);

        return Inertia::render('Users/Index', [
            'users'   => $this->userService->getPaginatedUsers($filters),
            'filters' => [
                'search'     => $filters['search'] ?? '',
                'role'       => $filters['role'] ?? 'all',
                'status'     => $filters['status'] ?? '',
                'sort_by'    => $filters['sort_by'] ?? 'id',
                'sort_order' => $filters['sort_order'] ?? 'desc',
                'per_page'   => (int) ($filters['per_page'] ?? 10),
                'date_from'  => $filters['date_from'] ?? '',
                'date_to'    => $filters['date_to'] ?? '',
            ],
            'metrics' => $this->userService->getKpiMetrics(),
        ]);
    }

    /**
     * Show detailed user profile with role-aware metrics and history.
     */
    public function show(int $id): Response
    {
        $data = $this->userService->getUserProfileData($id);

        return Inertia::render('Users/Show', $data);
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        try {
            $user = $this->userService->toggleStatus($id, (int) auth()->id());

            return back()->with('success', "Status updated to {$user->status} successfully!");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Soft delete user to trash.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->userService->deleteUser($id, (int) auth()->id());

            return back()->with('success', 'User moved to trash successfully!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore soft-deleted user.
     */
    public function restore(int $id): RedirectResponse
    {
        try {
            $this->userService->restoreUser($id);

            return back()->with('success', 'User restored successfully!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Permanently delete user.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        try {
            $this->userService->forceDeleteUser($id, (int) auth()->id());

            return back()->with('success', 'User permanently deleted!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export Users to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['role', 'status', 'search']);

        return $this->userService->exportCsv($filters);
    }
}
