<?php
declare(strict_types=1);

namespace App\Http\Controllers\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\AcademicDashboardService;
use App\Services\SchoolCore\DashboardService;
use App\Services\SchoolCore\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AcademicDashboardController extends Controller
{
    public function __construct(
        private readonly AcademicDashboardService $academic,
        private readonly DashboardService $dashboard,
        private readonly NavigationService $navigation,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user
            && (int) $user->school_id > 0,
            403
        );

        $schoolId = (int) $user->school_id;

        return view('schoolcore.index', [
            'dashboard' => $this->dashboard->get($schoolId),

            'navigation' => $this->navigation->forSchool(
                $schoolId,
                (string) $user->role
            ),

            'canManageFreeModules' =>
                $user->role === 'school_admin',

            'initialPage' => 'academic',

            'academic' => $this->academic->get($schoolId),
        ]);
    }
}
