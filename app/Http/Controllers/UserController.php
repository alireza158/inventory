<?php

namespace App\Http\Controllers;

use App\Services\ExternalUserSyncService;

class UserController extends Controller
{
    public function index(ExternalUserSyncService $externalUserSyncService)
    {
        $result = $externalUserSyncService->fetchUsers();

        $users = $result['users'];
        $error = $result['error'];

        return view('users.index', compact('users', 'error'));
    }

    public function sync(ExternalUserSyncService $externalUserSyncService)
    {
        $result = $externalUserSyncService->fetchUsers();

        if (!empty($result['error'])) {
            return redirect()->route('users.index')->with('sync_error', $result['error']);
        }

        return redirect()->route('users.index')->with('sync_success', 'سینک کاربران با موفقیت انجام شد. تعداد کاربران: ' . count($result['users']));
    }
}
