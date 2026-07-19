<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $request->changePassword();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password changed successfully.');
    }
}
