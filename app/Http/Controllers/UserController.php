<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ExternalUserSyncService;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with(['roles', 'manager'])
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function sync(ExternalUserSyncService $externalUserSyncService)
    {
        $result = $externalUserSyncService->syncUsers();

        if (!empty($result['error'])) {
            return redirect()->route('users.index')->with('sync_error', $result['error']);
        }

        return redirect()->route('users.index')->with('sync_success', 'سینک کاربران با موفقیت انجام شد. تعداد کاربران: ' . $result['synced_count']);
    }
}
