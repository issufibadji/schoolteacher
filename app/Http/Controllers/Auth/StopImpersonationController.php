<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class StopImpersonationController extends Controller
{
    public function __invoke(ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->encerrar();

        return redirect()->route('admin.users.index')
            ->with('success', 'Você voltou para a sua conta.');
    }
}
