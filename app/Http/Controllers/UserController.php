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
}
