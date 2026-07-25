<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiRun;
use App\Services\Ai\Charts\AiChartSvgRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AiExportController extends Controller
{
    public function __construct(
        private readonly AiChartSvgRenderer
            $chartSvgRenderer
    ) {
    }

    public function print(
        Request $request,
        int $run
    ): View {
        return view(
            'admin.ai.pdf',
            [
                ...$this->viewData(
                    $request,
                    $run
                ),

                'printMode' => true,
            ]
        );
    }

    public function pdf(
        Request $request,
        int $run
    ): Response {
        $data = $this->viewData(
            $request,
            $run
        );

        $pdf = Pdf::loadView(
            'admin.ai.pdf',
            [
                ...$data,
                'printMode' => false,
            ]
        )
            ->setPaper(
                'letter',
                'portrait'
            )
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $title = $data['run']
            ->conversation?->title
            ?: 'analisis-schoolpass';

        return $pdf->download(
            sprintf(
                'schoolpass-ia-%s-%s.pdf',
                Str::slug($title),
                $data['run']->id
            )
        );
    }

    private function viewData(
        Request $request,
        int $runId
    ): array {
        $user = $request->user();

        $schoolId = (int) (
            $user?->school_id
            ?? 0
        );

        abort_unless(
            $schoolId > 0,
            403
        );

        $run = AiRun::query()
            ->with([
                'conversation',
                'user',
            ])
            ->where(
                'id',
                $runId
            )
            ->where(
                'school_id',
                $schoolId
            )
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'status',
                'success'
            )
            ->firstOrFail();

        $school = DB::table(
            'schools'
        )
            ->where(
                'id',
                $schoolId
            )
            ->firstOrFail();

        $timezone = $school->timezone
            ?: config('app.timezone');

        $result = is_array(
            $run->response_json
        )
            ? $run->response_json
            : [];

        $primaryColor = $this->safeColor(
            $school->primary_color
                ?? null,
            '#206bc4'
        );

        $secondaryColor = $this->safeColor(
            $school->secondary_color
                ?? null,
            '#0f172a'
        );

        $chartImages = $this
            ->chartSvgRenderer
            ->renderMany(
                charts: is_array(
                    $result['charts']
                    ?? null
                )
                    ? $result['charts']
                    : [],
                primaryColor:
                    $primaryColor,
                secondaryColor:
                    $secondaryColor
            );

        return [
            'run' => $run,
            'school' => $school,
            'result' => $result,

            'chartImages' =>
                $chartImages,

            'logoDataUri' =>
                $this->schoolLogoDataUri(
                    $school->logo_path
                        ?? null
                ),

            'primaryColor' =>
                $primaryColor,

            'secondaryColor' =>
                $secondaryColor,

            'generatedAt' =>
                Carbon::now($timezone),
        ];
    }

    private function schoolLogoDataUri(
        ?string $logoPath
    ): ?string {
        if (! $logoPath) {
            return null;
        }

        $relative = ltrim(
            str_replace(
                [
                    '\\',
                    '/storage/',
                    'storage/',
                ],
                [
                    '/',
                    '',
                    '',
                ],
                $logoPath
            ),
            '/'
        );

        $candidates = [
            public_path(
                ltrim(
                    $logoPath,
                    '/'
                )
            ),

            storage_path(
                'app/public/'
                .$relative
            ),

            public_path(
                'storage/'
                .$relative
            ),
        ];

        foreach (
            $candidates
            as $candidate
        ) {
            if (
                ! is_file($candidate)
                || ! is_readable($candidate)
            ) {
                continue;
            }

            $extension = strtolower(
                pathinfo(
                    $candidate,
                    PATHINFO_EXTENSION
                )
            );

            $mime = match ($extension) {
                'jpg',
                'jpeg' =>
                    'image/jpeg',

                'gif' =>
                    'image/gif',

                'webp' =>
                    'image/webp',

                default =>
                    'image/png',
            };

            $contents = file_get_contents(
                $candidate
            );

            if ($contents !== false) {
                return sprintf(
                    'data:%s;base64,%s',
                    $mime,
                    base64_encode(
                        $contents
                    )
                );
            }
        }

        return null;
    }

    private function safeColor(
        ?string $value,
        string $fallback
    ): string {
        $value = trim(
            (string) $value
        );

        return preg_match(
            '/^#[0-9a-f]{6}$/i',
            $value
        )
            ? $value
            : $fallback;
    }
}
