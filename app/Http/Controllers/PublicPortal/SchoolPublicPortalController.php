<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class SchoolPublicPortalController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $modules
    ) {}

    public function show(string $school): View
    {
        $row = DB::table('schools')
            ->where('slug', $school)
            ->where('status', 'active')
            ->first([
                'id',
                'name',
                'slug',
                'logo_path',
                'primary_color',
                'secondary_color',
            ]);

        abort_unless($row, 404);

        $schoolId = (int) $row->id;

        $gradePortalEnabled =
            $this->modules->canAccess($schoolId, 'academic')
            && (bool) DB::table('academic_grade_portal_settings')
                ->where('school_id', $schoolId)
                ->value('enabled');

        $primary = (string) ($row->primary_color ?? '');
        $secondary = (string) ($row->secondary_color ?? '');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primary)) {
            $primary = '#2563eb';
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $secondary)) {
            $secondary = '#0f172a';
        }

        $logo = ltrim((string) ($row->logo_path ?? ''), '/');

        $logoUrl = $logo !== ''
            ? asset(
                str_starts_with($logo, 'storage/')
                    ? $logo
                    : 'storage/' . $logo
            )
            : null;

        return view('academic.public-grades', [
            'schoolName' => $row->name,
            'schoolSlug' => $row->slug,
            'schoolLogoUrl' => $logoUrl,
            'schoolPrimary' => $primary,
            'schoolSecondary' => $secondary,
            'gradesEnabled' => $gradePortalEnabled,
        ]);
    }
}
