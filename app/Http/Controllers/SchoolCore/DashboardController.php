<?php
declare(strict_types=1);

namespace App\Http\Controllers\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\DashboardService;
use App\Services\SchoolCore\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly NavigationService $navigation,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && (int) $user->school_id > 0
            && in_array(
                $user->role,
                ['school_admin', 'director'],
                true
            ),
            403
        );

        $schoolId = (int) $user->school_id;
        $role = (string) $user->role;

        return view('schoolcore.index', [
            'dashboard' => $this->dashboard->get($schoolId),

            'navigation' => $this->navigation->forSchool(
                $schoolId,
                $role
            ),

            'canManageFreeModules' => $role === 'school_admin',
        ]);
    }
}
