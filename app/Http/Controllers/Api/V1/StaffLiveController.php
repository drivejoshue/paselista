<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Attendance\DirectionLiveSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class StaffLiveController extends Controller
{
    public function __construct(
        private readonly DirectionLiveSnapshotService $liveSnapshot
    ) {
    }

    public function today(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user && $user->school_id,
            403
        );

        $schoolId = (int) $user->school_id;

        $validated = $request->validate([
            'campus_id' => [
                'nullable',
                'integer',
                Rule::exists('campuses', 'id')
                    ->where(
                        'school_id',
                        $schoolId
                    ),
            ],

            'level_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'academic_levels',
                    'id'
                )->where(
                    'school_id',
                    $schoolId
                ),
            ],

            'group_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'school_groups',
                    'id'
                )->where(
                    'school_id',
                    $schoolId
                ),
            ],
        ]);

        $snapshot = $this->liveSnapshot
            ->snapshot(
                schoolId: $schoolId,
                filters: $validated
            );

        $summary = $snapshot['summary'];

        return response()
            ->json([
                'school' => $snapshot['school'],
                'clock' => $snapshot['clock'],
                'cycle' => $snapshot['cycle'],

                'summary' => [
                    'expected' =>
                        (int) $summary['total'],

                    'registered' =>
                        (int) $summary['present'],

                    'on_time' =>
                        (int) $summary['on_time'],

                    'late' =>
                        (int) $summary['late'],

                    'very_late' =>
                        (int) $summary['very_late'],

                    'late_total' =>
                        (int) $summary['late']
                        + (int) $summary['very_late'],

                    'pending' =>
                        (int) $summary['pending'],

                    'absent' =>
                        (int) $summary['absent'],

                    'exited' =>
                        (int) $summary['exited'],

                    'early_exit' =>
                        (int) $summary['early_exit'],

                    'attendance_rate' =>
                        (float) $summary[
                            'attendance_rate'
                        ],

                    'active_devices' =>
                        (int) $summary[
                            'active_devices'
                        ],

                    'online_devices' =>
                        (int) $summary[
                            'online_devices'
                        ],
                ],

                /*
                 * Para la mini pantalla solo enviamos
                 * los últimos ocho movimientos.
                 */
                'activity' => array_slice(
                    $snapshot['activity'],
                    0,
                    8
                ),

                'meta' => $snapshot['meta'],
            ])
            ->header(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            );
    }
}