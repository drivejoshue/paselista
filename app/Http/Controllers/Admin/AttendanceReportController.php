<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendancePeriodService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AttendanceReportController extends Controller
{
    public function __construct(
        private readonly AttendancePeriodService $attendancePeriod
    ) {
    }

    public function index(Request $request): View
    {
        $report = $this->buildReport($request);

        $rows = $this->paginate(
            request: $request,
            rows: $report['display_rows'],
            perPage: 25
        );

        return view('admin.reports.attendance', [
            'rows' => $rows,

            'summary' => $report['summary'],
            'filters' => $report['filters'],

            'school' => $report['school'],
            'activeCycle' => $report['active_cycle'],
            'calendarDay' => $report['calendar_day'],

            'isNoClassDay' => $report['is_no_class_day'],
            'hasActiveCycle' =>
                $report['active_cycle'] !== null,

            'dateInsideCycle' =>
                $report['date_inside_cycle'],

            'dateIsFuture' =>
                $report['date_is_future'],

            'campuses' => $this->campuses(
                schoolId: $report['school_id'],
                cycleId: $report['active_cycle']?->id
            ),

            'levels' => $this->levels(
                schoolId: $report['school_id'],
                cycleId: $report['active_cycle']?->id
            ),

            'groups' => $this->groups(
                schoolId: $report['school_id'],
                cycleId: $report['active_cycle']?->id,
                campusId: $report['filters']['campus_id'],
                levelId: $report['filters']['level_id']
            ),

            'displayedTotal' =>
                $report['display_rows']->count(),
        ]);
    }

    public function excel(
        Request $request
    ): BinaryFileResponse {
        $report = $this->buildReport($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Asistencia diaria');

        $lastColumn = 'Z';

        $sheet->mergeCells(
            'A1:'.$lastColumn.'1'
        );

        $sheet->mergeCells(
            'A2:'.$lastColumn.'2'
        );

        $sheet->mergeCells(
            'A3:'.$lastColumn.'3'
        );

        $sheet->setCellValue(
            'A1',
            $report['school']->name
                ?? 'PaseLista'
        );

        $sheet->setCellValue(
            'A2',
            'Reporte diario de asistencia'
        );

        $sheet->setCellValue(
            'A3',
            $this->filterDescription(
                report: $report
            )
        );

        $sheet->getStyle(
            'A1:'.$lastColumn.'1'
        )->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],

            'fill' => [
                'fillType' =>
                    Fill::FILL_SOLID,

                'startColor' => [
                    'rgb' => '0F172A',
                ],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle(
            'A2:'.$lastColumn.'2'
        )->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 13,
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle(
            'A3:'.$lastColumn.'3'
        )->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => [
                    'rgb' => '475569',
                ],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'wrapText' => true,
            ],
        ]);

        $headers = [
            'A' => 'Matrícula',
            'B' => 'Alumno',
            'C' => 'Plantel',
            'D' => 'Nivel',
            'E' => 'Grupo',
            'F' => 'Estado',
            'G' => 'Min. tarde',

            'H' => 'Entrada',
            'I' => 'Horario entrada',
            'J' => 'Tutor que entregó',
            'K' => 'Realizado para',
            'L' => 'Origen entrada',
            'M' => 'Lector entrada',
            'N' => 'Operador entrada',
            'O' => 'Dispositivo entrada',

            'P' => 'Salida',
            'Q' => 'Horario salida',
            'R' => 'Tutor que recogió',
            'S' => 'Realizado para',
            'T' => 'Origen salida',
            'U' => 'Lector salida',
            'V' => 'Operador salida',
            'W' => 'Dispositivo salida',

            'X' => 'Salida anticipada',
            'Y' => 'Observación entrada',
            'Z' => 'Observación salida',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue(
                $column.'4',
                $label
            );
        }

        $sheet->getStyle(
            'A4:'.$lastColumn.'4'
        )->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],

            'fill' => [
                'fillType' =>
                    Fill::FILL_SOLID,

                'startColor' => [
                    'rgb' => '2563EB',
                ],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER,

                'wrapText' => true,
            ],

            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                        Border::BORDER_THIN,

                    'color' => [
                        'rgb' => 'CBD5E1',
                    ],
                ],
            ],
        ]);

        $rowNumber = 5;

        foreach (
            $report['display_rows']
            as $row
        ) {
            $entryObservation =
                $row->entry_notes
                ?: $row->entry_reason;

            $exitObservation =
                $row->exit_notes
                ?: $row->exit_reason;

            $sheet->setCellValueExplicit(
                'A'.$rowNumber,
                (string) $row->student_code,
                DataType::TYPE_STRING
            );

            $sheet->setCellValue(
                'B'.$rowNumber,
                $this->studentName($row)
            );

            $sheet->setCellValue(
                'C'.$rowNumber,
                $row->campus_name
                    ?? 'Sin plantel'
            );

            $sheet->setCellValue(
                'D'.$rowNumber,
                $row->level_name
                    ?? 'Sin nivel'
            );

            $sheet->setCellValue(
                'E'.$rowNumber,
                $row->group_name
                    ?? 'Sin grupo'
            );

            $sheet->setCellValue(
                'F'.$rowNumber,
                $this->statusLabel(
                    $row->final_status
                )
            );

            $sheet->setCellValue(
                'G'.$rowNumber,
                (int) ($row->minutes_late ?? 0)
            );

            $sheet->setCellValue(
                'H'.$rowNumber,
                $this->formatTime(
                    $row->entry_at
                )
            );

            $sheet->setCellValue(
                'I'.$rowNumber,
                $this->formatTime(
                    $row->scheduled_entry_time
                )
            );

            $sheet->setCellValue(
                'J'.$rowNumber,
                $row->entry_guardian_name
                    ?? ''
            );

            $sheet->setCellValue(
                'K'.$rowNumber,
                $this->humanize(
                    $row->entry_performed_for
                )
            );

            $sheet->setCellValue(
                'L'.$rowNumber,
                $this->sourceLabel(
                    $row->entry_source
                )
            );

            $sheet->setCellValue(
                'M'.$rowNumber,
                $this->readerLabel(
                    $row->entry_reader_type
                )
            );

            $sheet->setCellValue(
                'N'.$rowNumber,
                $row->entry_user_name
                    ?? ''
            );

            $sheet->setCellValue(
                'O'.$rowNumber,
                $row->entry_device_name
                    ?? ''
            );

            $sheet->setCellValue(
                'P'.$rowNumber,
                $this->formatTime(
                    $row->exit_at
                )
            );

            $sheet->setCellValue(
                'Q'.$rowNumber,
                $this->formatTime(
                    $row->scheduled_exit_time
                )
            );

            $sheet->setCellValue(
                'R'.$rowNumber,
                $row->exit_guardian_name
                    ?? ''
            );

            $sheet->setCellValue(
                'S'.$rowNumber,
                $this->humanize(
                    $row->exit_performed_for
                )
            );

            $sheet->setCellValue(
                'T'.$rowNumber,
                $this->sourceLabel(
                    $row->exit_source
                )
            );

            $sheet->setCellValue(
                'U'.$rowNumber,
                $this->readerLabel(
                    $row->exit_reader_type
                )
            );

            $sheet->setCellValue(
                'V'.$rowNumber,
                $row->exit_user_name
                    ?? ''
            );

            $sheet->setCellValue(
                'W'.$rowNumber,
                $row->exit_device_name
                    ?? ''
            );

            $sheet->setCellValue(
                'X'.$rowNumber,
                $row->is_early_exit
                    ? 'Sí'
                    : 'No'
            );

            $sheet->setCellValue(
                'Y'.$rowNumber,
                $entryObservation
                    ?? ''
            );

            $sheet->setCellValue(
                'Z'.$rowNumber,
                $exitObservation
                    ?? ''
            );

            $rowNumber++;
        }

        $lastDataRow = max(
            4,
            $rowNumber - 1
        );

        if ($lastDataRow >= 5) {
            $sheet->getStyle(
                'A5:'.$lastColumn.$lastDataRow
            )->applyFromArray([
                'alignment' => [
                    'vertical' =>
                        Alignment::VERTICAL_TOP,

                    'wrapText' => true,
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' =>
                            Border::BORDER_THIN,

                        'color' => [
                            'rgb' => 'E2E8F0',
                        ],
                    ],
                ],
            ]);
        }

        $sheet->freezePane('A5');

        $sheet->setAutoFilter(
            'A4:'.$lastColumn.$lastDataRow
        );

        $sheet->getRowDimension(1)
            ->setRowHeight(26);

        $sheet->getRowDimension(3)
            ->setRowHeight(30);

        $widths = [
            'A' => 15,
            'B' => 30,
            'C' => 22,
            'D' => 19,
            'E' => 17,
            'F' => 18,
            'G' => 11,

            'H' => 12,
            'I' => 15,
            'J' => 28,
            'K' => 17,
            'L' => 19,
            'M' => 18,
            'N' => 24,
            'O' => 24,

            'P' => 12,
            'Q' => 15,
            'R' => 28,
            'S' => 17,
            'T' => 19,
            'U' => 18,
            'V' => 24,
            'W' => 24,

            'X' => 18,
            'Y' => 34,
            'Z' => 34,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension(
                $column
            )->setWidth($width);
        }

        $sheet->getPageSetup()
            ->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
            );

        $sheet->getPageSetup()
            ->setPaperSize(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER
            );

        $directory = storage_path(
            'app/private/report-exports'
        );

        File::ensureDirectoryExists(
            $directory
        );

        $temporaryFile = tempnam(
            $directory,
            'attendance_'
        );

        if ($temporaryFile === false) {
            throw new RuntimeException(
                'No se pudo crear el archivo temporal.'
            );
        }

        $writer = new Xlsx(
            $spreadsheet
        );

        $writer->save(
            $temporaryFile
        );

        $spreadsheet->disconnectWorksheets();

        unset(
            $spreadsheet,
            $writer
        );

        $filename = sprintf(
            'asistencia_diaria_%s.xlsx',
            $report['filters']['date']
        );

        return response()
            ->download(
                $temporaryFile,
                $filename,
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    public function pdf(
        Request $request
    ): Response {
        $report = $this->buildReport(
            $request
        );

        $pdf = Pdf::loadView(
            'admin.reports.attendance-pdf',
            [
                'school' => $report['school'],

                'activeCycle' =>
                    $report['active_cycle'],

                'calendarDay' =>
                    $report['calendar_day'],

                'filters' =>
                    $report['filters'],

                'rows' =>
                    $report['display_rows'],

                'summary' =>
                    $report['summary'],

                'displayedTotal' =>
                    $report['display_rows']->count(),

                'filterDescription' =>
                    $this->filterDescription(
                        report: $report
                    ),

                'statusLabels' =>
                    $this->statusLabels(),

                'sourceLabels' =>
                    $this->sourceLabels(),

                'readerLabels' =>
                    $this->readerLabels(),

                'generatedAt' =>
                    Carbon::now(
                        $report['timezone']
                    ),
            ]
        )
            ->setPaper(
                'letter',
                'landscape'
            )
            ->setOption(
                'isRemoteEnabled',
                false
            )
            ->setOption(
                'isHtml5ParserEnabled',
                true
            );

        $filename = sprintf(
            'asistencia_diaria_%s.pdf',
            $report['filters']['date']
        );

        return $pdf->download(
            $filename
        );
    }

    private function buildReport(
        Request $request
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

        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->firstOrFail();

        $timezone = $school->timezone
            ?: config('app.timezone');

        $filters = $this->validatedFilters(
            request: $request,
            schoolId: $schoolId,
            timezone: $timezone
        );

        $today = Carbon::now(
            $timezone
        )->startOfDay();

        $selectedDate = Carbon::parse(
            $filters['date'],
            $timezone
        )->startOfDay();

        $date = $selectedDate
            ->toDateString();

        $dateIsFuture =
            $selectedDate->isAfter(
                $today
            );

        $dateIsToday =
            $selectedDate->isSameDay(
                $today
            );

        $weekday =
            $selectedDate->dayOfWeekIso;

        $activeWindow = $this
            ->attendancePeriod
            ->attendanceWindow(
                $schoolId
            );

        $activeCycle =
            $activeWindow['cycle']
            ?? null;

        $dateInsideCycle =
            $activeWindow !== null
            && $selectedDate->betweenIncluded(
                Carbon::parse(
                    $activeWindow['start'],
                    $timezone
                ),
                Carbon::parse(
                    $activeWindow['end'],
                    $timezone
                )
            );

        $calendarDay = null;

        if ($activeCycle) {
            $calendarDay = DB::table(
                'school_calendar_days'
            )
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'academic_cycle_id',
                    $activeCycle->id
                )
                ->where(
                    'date',
                    $date
                )
                ->where(
                    'status',
                    'active'
                )
                ->first();
        }

        $isNoClassDay = (bool) (
            $calendarDay
            && in_array(
                $calendarDay->type,
                [
                    'holiday',
                    'vacation',
                    'suspension',
                    'technical_council',
                    'no_class',
                ],
                true
            )
        );

        $allRows = collect();

        if ($activeCycle) {
            $scheduleIds = DB::table(
                'group_access_schedules'
            )
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'weekday',
                    $weekday
                )
                ->where(
                    'status',
                    'active'
                )
                ->selectRaw(
                    'MAX(id) as schedule_id, group_id'
                )
                ->groupBy(
                    'group_id'
                );

            $allRows = DB::table(
                'student_enrollments as se'
            )
                ->join(
                    'students as s',
                    function ($join) use (
                        $schoolId
                    ): void {
                        $join
                            ->on(
                                's.id',
                                '=',
                                'se.student_id'
                            )
                            ->where(
                                's.school_id',
                                '=',
                                $schoolId
                            );
                    }
                )
                ->join(
                    'school_groups as sg',
                    function ($join) use (
                        $schoolId,
                        $activeCycle
                    ): void {
                        $join
                            ->on(
                                'sg.id',
                                '=',
                                'se.school_group_id'
                            )
                            ->where(
                                'sg.school_id',
                                '=',
                                $schoolId
                            )
                            ->where(
                                'sg.academic_cycle_id',
                                '=',
                                $activeCycle->id
                            );
                    }
                )
                ->join(
                    'campuses as c',
                    function ($join) use (
                        $schoolId
                    ): void {
                        $join
                            ->on(
                                'c.id',
                                '=',
                                'se.campus_id'
                            )
                            ->where(
                                'c.school_id',
                                '=',
                                $schoolId
                            );
                    }
                )
                ->leftJoin(
                    'academic_levels as al',
                    'al.id',
                    '=',
                    'sg.academic_level_id'
                )
                ->leftJoinSub(
                    $scheduleIds,
                    'gas_latest',
                    function ($join): void {
                        $join->on(
                            'gas_latest.group_id',
                            '=',
                            'sg.id'
                        );
                    }
                )
                ->leftJoin(
                    'group_access_schedules as gas',
                    'gas.id',
                    '=',
                    'gas_latest.schedule_id'
                )
                ->leftJoin(
                    'daily_attendance as da',
                    function ($join) use (
                        $schoolId,
                        $date
                    ): void {
                        $join
                            ->on(
                                'da.student_id',
                                '=',
                                'se.student_id'
                            )
                            ->where(
                                'da.school_id',
                                '=',
                                $schoolId
                            )
                            ->where(
                                'da.date',
                                '=',
                                $date
                            );
                    }
                )

                /*
                 * Log exacto de entrada.
                 */
                ->leftJoin(
                    'access_logs as entry_log',
                    'entry_log.id',
                    '=',
                    'da.entry_log_id'
                )
                ->leftJoin(
                    'guardians as entry_guardian',
                    'entry_guardian.id',
                    '=',
                    'entry_log.guardian_id'
                )
                ->leftJoin(
                    'access_devices as entry_device',
                    'entry_device.id',
                    '=',
                    'entry_log.access_device_id'
                )
                ->leftJoin(
                    'users as entry_user',
                    'entry_user.id',
                    '=',
                    'entry_log.user_id'
                )

                /*
                 * Log exacto de salida.
                 */
                ->leftJoin(
                    'access_logs as exit_log',
                    'exit_log.id',
                    '=',
                    'da.exit_log_id'
                )
                ->leftJoin(
                    'guardians as exit_guardian',
                    'exit_guardian.id',
                    '=',
                    'exit_log.guardian_id'
                )
                ->leftJoin(
                    'access_devices as exit_device',
                    'exit_device.id',
                    '=',
                    'exit_log.access_device_id'
                )
                ->leftJoin(
                    'users as exit_user',
                    'exit_user.id',
                    '=',
                    'exit_log.user_id'
                )
                ->where(
                    'se.school_id',
                    $schoolId
                )
                ->where(
                    'se.academic_cycle_id',
                    $activeCycle->id
                )
                ->where(
                    'se.status',
                    'active'
                )
                ->where(
                    's.status',
                    'active'
                )
                ->whereDate(
                    'se.enrolled_on',
                    '<=',
                    $date
                )
                ->where(
                    function ($query) use (
                        $date
                    ): void {
                        $query
                            ->whereNull(
                                'se.withdrawn_on'
                            )
                            ->orWhereDate(
                                'se.withdrawn_on',
                                '>=',
                                $date
                            );
                    }
                )
                ->when(
                    $filters['campus_id'],
                    fn ($query, $campusId) =>
                        $query->where(
                            'se.campus_id',
                            $campusId
                        )
                )
                ->when(
                    $filters['level_id'],
                    fn ($query, $levelId) =>
                        $query->where(
                            'sg.academic_level_id',
                            $levelId
                        )
                )
                ->when(
                    $filters['group_id'],
                    fn ($query, $groupId) =>
                        $query->where(
                            'se.school_group_id',
                            $groupId
                        )
                )
                ->when(
                    $filters['student'] !== '',
                    function ($query) use (
                        $filters
                    ): void {
                        $search =
                            $filters['student'];

                        $query->where(
                            function ($inner) use (
                                $search
                            ): void {
                                $inner
                                    ->where(
                                        's.student_code',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        's.first_name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        's.last_name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereRaw(
                                        "CONCAT(
                                            s.first_name,
                                            ' ',
                                            s.last_name
                                        ) LIKE ?",
                                        [
                                            "%{$search}%",
                                        ]
                                    );
                            }
                        );
                    }
                )
                ->select([
                    's.id as student_id',
                    's.student_code',
                    's.first_name',
                    's.last_name',
                    's.photo_url',

                    'c.id as campus_id',
                    'c.name as campus_name',

                    'al.id as level_id',
                    'al.name as level_name',
                    'al.sort_order as level_sort_order',

                    'sg.id as group_id',
                    'sg.name as group_name',
                    'sg.grade_label',

                    'gas.id as schedule_id',

                    'gas.entry_time as scheduled_entry_time',
                    'gas.grace_until',
                    'gas.late_until',

                    'gas.exit_time as scheduled_exit_time',

                    'da.id as attendance_id',

                    'da.attendance_status as raw_attendance_status',

                    'da.entry_at',
                    'da.exit_at',
                    'da.minutes_late',

                    'da.entry_log_id',
                    'da.exit_log_id',

                    'entry_log.event_status as entry_event_status',
                    'entry_log.decision as entry_decision',
                    'entry_log.source as entry_source',
                    'entry_log.reader_type as entry_reader_type',
                    'entry_log.performed_for as entry_performed_for',
                    'entry_log.reason as entry_reason',
                    'entry_log.notes as entry_notes',

                    'entry_guardian.id as entry_guardian_id',

                    'entry_guardian.first_name as entry_guardian_first_name',

                    'entry_guardian.last_name as entry_guardian_last_name',

                    'entry_device.name as entry_device_name',

                    'entry_user.name as entry_user_name',

                    'entry_user.role as entry_user_role',

                    'exit_log.event_status as exit_event_status',
                    'exit_log.decision as exit_decision',
                    'exit_log.source as exit_source',
                    'exit_log.reader_type as exit_reader_type',
                    'exit_log.performed_for as exit_performed_for',
                    'exit_log.reason as exit_reason',
                    'exit_log.notes as exit_notes',

                    'exit_guardian.id as exit_guardian_id',

                    'exit_guardian.first_name as exit_guardian_first_name',

                    'exit_guardian.last_name as exit_guardian_last_name',

                    'exit_device.name as exit_device_name',

                    'exit_user.name as exit_user_name',

                    'exit_user.role as exit_user_role',
                ])
                ->orderBy(
                    'al.sort_order'
                )
                ->orderBy(
                    'sg.name'
                )
                ->orderBy(
                    's.last_name'
                )
                ->orderBy(
                    's.first_name'
                )
                ->get()
                ->map(
                    function ($row) use (
                        $dateInsideCycle,
                        $dateIsFuture,
                        $dateIsToday,
                        $isNoClassDay,
                        $timezone
                    ): object {
                        $row->entry_guardian_name =
                            $this->fullName(
                                $row->entry_guardian_first_name,
                                $row->entry_guardian_last_name
                            );

                        $row->exit_guardian_name =
                            $this->fullName(
                                $row->exit_guardian_first_name,
                                $row->exit_guardian_last_name
                            );

                        $row->has_exit =
                            ! empty(
                                $row->exit_at
                            );

                        $row->is_early_exit =
                            $row->exit_event_status
                                === 'early_exit'
                            || $row->raw_attendance_status
                                === 'early_exit';

                        if (
                            ! $dateInsideCycle
                            || $dateIsFuture
                        ) {
                            $row->final_status =
                                'outside_cycle';

                            return $row;
                        }

                        if (
                            $isNoClassDay
                            || ! $row->schedule_id
                        ) {
                            $row->final_status =
                                $row->attendance_id
                                    ? $this
                                        ->normalizeAttendanceStatus(
                                            $row
                                        )
                                    : 'no_class';

                            return $row;
                        }

                        if (! $row->attendance_id) {
                            if (
                                $dateIsToday
                                && $row->late_until
                                && Carbon::now(
                                    $timezone
                                )->format('H:i:s')
                                    <= $row->late_until
                            ) {
                                $row->final_status =
                                    'pending';
                            } else {
                                $row->final_status =
                                    'absent';
                            }

                            return $row;
                        }

                        $row->final_status =
                            $this
                                ->normalizeAttendanceStatus(
                                    $row
                                );

                        return $row;
                    }
                );
        }

        /*
         * El resumen respeta fecha, plantel, nivel,
         * grupo y alumno, pero no el filtro de estado.
         */
        $summary = $this->summary(
            $allRows
        );

        $displayRows =
            $this->applyStatusFilter(
                rows: $allRows,
                status: $filters['status']
            );

        return [
            'school_id' => $schoolId,
            'school' => $school,
            'timezone' => $timezone,

            'filters' => $filters,

            'selected_date' =>
                $selectedDate,

            'active_window' =>
                $activeWindow,

            'active_cycle' =>
                $activeCycle,

            'calendar_day' =>
                $calendarDay,

            'date_inside_cycle' =>
                $dateInsideCycle,

            'date_is_future' =>
                $dateIsFuture,

            'date_is_today' =>
                $dateIsToday,

            'is_no_class_day' =>
                $isNoClassDay,

            'all_rows' =>
                $allRows,

            'display_rows' =>
                $displayRows,

            'summary' =>
                $summary,

            'filter_labels' =>
                $this->selectedFilterLabels(
                    schoolId: $schoolId,
                    filters: $filters
                ),
        ];
    }

    private function validatedFilters(
        Request $request,
        int $schoolId,
        string $timezone
    ): array {
        $validated = $request->validate([
            'date' => [
                'nullable',
                'date',
            ],

            'campus_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'campuses',
                    'id'
                )->where(
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

            'status' => [
                'nullable',

                Rule::in([
                    'present',
                    'on_time',
                    'late',
                    'very_late',
                    'absent',
                    'pending',
                    'no_class',
                    'outside_cycle',
                    'exited',
                    'early_exit',
                ]),
            ],

            'student' => [
                'nullable',
                'string',
                'max:120',
            ],
        ]);

        return [
            'date' =>
                $validated['date']
                ?? Carbon::now(
                    $timezone
                )->toDateString(),

            'campus_id' =>
                ! empty(
                    $validated['campus_id']
                )
                    ? (int)
                        $validated['campus_id']
                    : null,

            'level_id' =>
                ! empty(
                    $validated['level_id']
                )
                    ? (int)
                        $validated['level_id']
                    : null,

            'group_id' =>
                ! empty(
                    $validated['group_id']
                )
                    ? (int)
                        $validated['group_id']
                    : null,

            'status' =>
                $validated['status']
                ?? null,

            'student' =>
                trim(
                    (string) (
                        $validated['student']
                        ?? ''
                    )
                ),
        ];
    }

    private function normalizeAttendanceStatus(
        object $row
    ): string {
        if (
            in_array(
                $row->entry_event_status,
                [
                    'on_time',
                    'late',
                    'very_late',
                ],
                true
            )
        ) {
            return $row->entry_event_status;
        }

        if (
            in_array(
                $row->raw_attendance_status,
                [
                    'on_time',
                    'late',
                    'very_late',
                ],
                true
            )
        ) {
            if (
                $row->raw_attendance_status
                    === 'late'
                && (int) $row->minutes_late
                    > 20
            ) {
                return 'very_late';
            }

            return $row
                ->raw_attendance_status;
        }

        if (
            in_array(
                $row->raw_attendance_status,
                [
                    'present',
                    'early_exit',
                ],
                true
            )
        ) {
            return $this->statusFromMinutes(
                (int) $row->minutes_late
            );
        }

        return $this->statusFromMinutes(
            (int) $row->minutes_late
        );
    }

    private function applyStatusFilter(
        Collection $rows,
        ?string $status
    ): Collection {
        if (! $status) {
            return $rows->values();
        }

        return match ($status) {
            'present' =>
                $rows
                    ->whereIn(
                        'final_status',
                        [
                            'on_time',
                            'late',
                            'very_late',
                        ]
                    )
                    ->values(),

            'exited' =>
                $rows
                    ->filter(
                        fn ($row): bool =>
                            (bool) $row->has_exit
                    )
                    ->values(),

            'early_exit' =>
                $rows
                    ->filter(
                        fn ($row): bool =>
                            (bool)
                                $row->is_early_exit
                    )
                    ->values(),

            default =>
                $rows
                    ->where(
                        'final_status',
                        $status
                    )
                    ->values(),
        };
    }

    private function summary(
        Collection $rows
    ): array {
        $present = $rows
            ->whereIn(
                'final_status',
                [
                    'on_time',
                    'late',
                    'very_late',
                ]
            )
            ->count();

        $eligible = $rows
            ->filter(
                fn ($row): bool =>
                    ! in_array(
                        $row->final_status,
                        [
                            'no_class',
                            'outside_cycle',
                            'pending',
                        ],
                        true
                    )
            )
            ->count();

        return [
            'total' =>
                $rows->count(),

            'present' =>
                $present,

            'on_time' =>
                $rows
                    ->where(
                        'final_status',
                        'on_time'
                    )
                    ->count(),

            'late' =>
                $rows
                    ->where(
                        'final_status',
                        'late'
                    )
                    ->count(),

            'very_late' =>
                $rows
                    ->where(
                        'final_status',
                        'very_late'
                    )
                    ->count(),

            'absent' =>
                $rows
                    ->where(
                        'final_status',
                        'absent'
                    )
                    ->count(),

            'pending' =>
                $rows
                    ->where(
                        'final_status',
                        'pending'
                    )
                    ->count(),

            'no_class' =>
                $rows
                    ->where(
                        'final_status',
                        'no_class'
                    )
                    ->count(),

            'outside_cycle' =>
                $rows
                    ->where(
                        'final_status',
                        'outside_cycle'
                    )
                    ->count(),

            'exited' =>
                $rows
                    ->filter(
                        fn ($row): bool =>
                            (bool) $row->has_exit
                    )
                    ->count(),

            'early_exit' =>
                $rows
                    ->filter(
                        fn ($row): bool =>
                            (bool)
                                $row->is_early_exit
                    )
                    ->count(),

            'eligible' =>
                $eligible,

            'attendance_rate' =>
                $eligible > 0
                    ? round(
                        (
                            $present
                            / $eligible
                        ) * 100,
                        1
                    )
                    : 0.0,
        ];
    }

    private function paginate(
        Request $request,
        Collection $rows,
        int $perPage
    ): LengthAwarePaginator {
        $page =
            LengthAwarePaginator
                ::resolveCurrentPage();

        return new LengthAwarePaginator(
            items: $rows
                ->forPage(
                    $page,
                    $perPage
                )
                ->values(),

            total: $rows->count(),

            perPage: $perPage,

            currentPage: $page,

            options: [
                'path' =>
                    $request->url(),

                'query' =>
                    $request->query(),
            ]
        );
    }

    private function filterDescription(
        array $report
    ): string {
        $filters = $report['filters'];
        $labels = $report['filter_labels'];

        $parts = [
            'Fecha: '.
                Carbon::parse(
                    $filters['date']
                )->format('d/m/Y'),
        ];

        if ($labels['campus']) {
            $parts[] =
                'Plantel: '.
                $labels['campus'];
        }

        if ($labels['level']) {
            $parts[] =
                'Nivel: '.
                $labels['level'];
        }

        if ($labels['group']) {
            $parts[] =
                'Grupo: '.
                $labels['group'];
        }

        if ($filters['status']) {
            $parts[] =
                'Estado: '.
                $this->statusLabel(
                    $filters['status']
                );
        }

        if ($filters['student'] !== '') {
            $parts[] =
                'Alumno: '.
                $filters['student'];
        }

        $parts[] =
            'Registros: '.
            $report['display_rows']->count();

        $parts[] =
            'Generado: '.
            Carbon::now(
                $report['timezone']
            )->format(
                'd/m/Y H:i'
            );

        return implode(
            ' · ',
            $parts
        );
    }

    private function selectedFilterLabels(
        int $schoolId,
        array $filters
    ): array {
        $campus = null;
        $level = null;
        $group = null;

        if ($filters['campus_id']) {
            $campus = DB::table(
                'campuses'
            )
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'id',
                    $filters['campus_id']
                )
                ->value('name');
        }

        if ($filters['level_id']) {
            $level = DB::table(
                'academic_levels'
            )
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'id',
                    $filters['level_id']
                )
                ->value('name');
        }

        if ($filters['group_id']) {
            $group = DB::table(
                'school_groups'
            )
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'id',
                    $filters['group_id']
                )
                ->value('name');
        }

        return [
            'campus' => $campus,
            'level' => $level,
            'group' => $group,
        ];
    }

    private function statusLabels(): array
    {
        return [
            'present' => 'Presente',
            'on_time' => 'Puntual',
            'late' => 'Retardo',
            'very_late' => 'Muy tarde',
            'absent' => 'Ausente',
            'pending' => 'Pendiente',
            'no_class' => 'Sin clase',
            'outside_cycle' =>
                'Fuera del ciclo',
            'exited' => 'Con salida',
            'early_exit' =>
                'Salida anticipada',
        ];
    }

    private function sourceLabels(): array
    {
        return [
            'qr' => 'QR de alumno',
            'guardian_qr' => 'QR de tutor',
            'manual' => 'Registro manual',
            'kiosk' => 'Kiosco',
            'nfc' => 'NFC',
            'app' => 'Aplicación',
        ];
    }

    private function readerLabels(): array
    {
        return [
            'camera_qr' => 'Cámara QR',
            'manual' => 'Manual',
            'nfc' => 'Lector NFC',
        ];
    }

    private function statusLabel(
        ?string $status
    ): string {
        if (! $status) {
            return '';
        }

        return $this->statusLabels()[
            $status
        ] ?? $this->humanize(
            $status
        );
    }

    private function sourceLabel(
        ?string $source
    ): string {
        if (! $source) {
            return '';
        }

        return $this->sourceLabels()[
            $source
        ] ?? $this->humanize(
            $source
        );
    }

    private function readerLabel(
        ?string $reader
    ): string {
        if (! $reader) {
            return '';
        }

        return $this->readerLabels()[
            $reader
        ] ?? $this->humanize(
            $reader
        );
    }

    private function humanize(
        ?string $value
    ): string {
        if (! $value) {
            return '';
        }

        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function formatTime(
        mixed $value
    ): string {
        if (! $value) {
            return '';
        }

        return Carbon::parse(
            $value
        )->format('H:i');
    }

    private function studentName(
        object $row
    ): string {
        return trim(
            trim(
                (string) $row->first_name
            )
            .' '
            .trim(
                (string) $row->last_name
            )
        );
    }

    private function statusFromMinutes(
        int $minutesLate
    ): string {
        if ($minutesLate <= 0) {
            return 'on_time';
        }

        if ($minutesLate <= 20) {
            return 'late';
        }

        return 'very_late';
    }

    private function fullName(
        ?string $firstName,
        ?string $lastName
    ): ?string {
        $name = trim(
            trim(
                (string) $firstName
            )
            .' '
            .trim(
                (string) $lastName
            )
        );

        return $name !== ''
            ? $name
            : null;
    }

    private function campuses(
        int $schoolId,
        ?int $cycleId
    ): Collection {
        if (! $cycleId) {
            return collect();
        }

        return DB::table(
            'campuses as c'
        )
            ->join(
                'school_groups as sg',
                'sg.campus_id',
                '=',
                'c.id'
            )
            ->where(
                'c.school_id',
                $schoolId
            )
            ->where(
                'c.status',
                'active'
            )
            ->where(
                'sg.school_id',
                $schoolId
            )
            ->where(
                'sg.academic_cycle_id',
                $cycleId
            )
            ->where(
                'sg.status',
                'active'
            )
            ->select([
                'c.id',
                'c.name',
            ])
            ->distinct()
            ->orderBy(
                'c.name'
            )
            ->get();
    }

    private function levels(
        int $schoolId,
        ?int $cycleId
    ): Collection {
        if (! $cycleId) {
            return collect();
        }

        return DB::table(
            'academic_levels as al'
        )
            ->join(
                'school_groups as sg',
                'sg.academic_level_id',
                '=',
                'al.id'
            )
            ->where(
                'al.school_id',
                $schoolId
            )
            ->where(
                'al.status',
                'active'
            )
            ->where(
                'sg.school_id',
                $schoolId
            )
            ->where(
                'sg.academic_cycle_id',
                $cycleId
            )
            ->where(
                'sg.status',
                'active'
            )
            ->select([
                'al.id',
                'al.name',
                'al.sort_order',
            ])
            ->distinct()
            ->orderBy(
                'al.sort_order'
            )
            ->orderBy(
                'al.name'
            )
            ->get();
    }

    private function groups(
        int $schoolId,
        ?int $cycleId,
        ?int $campusId,
        ?int $levelId
    ): Collection {
        if (! $cycleId) {
            return collect();
        }

        return DB::table(
            'school_groups as sg'
        )
            ->join(
                'campuses as c',
                'c.id',
                '=',
                'sg.campus_id'
            )
            ->leftJoin(
                'academic_levels as al',
                'al.id',
                '=',
                'sg.academic_level_id'
            )
            ->where(
                'sg.school_id',
                $schoolId
            )
            ->where(
                'sg.academic_cycle_id',
                $cycleId
            )
            ->where(
                'sg.status',
                'active'
            )
            ->when(
                $campusId,
                fn ($query, $value) =>
                    $query->where(
                        'sg.campus_id',
                        $value
                    )
            )
            ->when(
                $levelId,
                fn ($query, $value) =>
                    $query->where(
                        'sg.academic_level_id',
                        $value
                    )
            )
            ->select([
                'sg.id',
                'sg.name',
                'sg.campus_id',
                'sg.academic_level_id',

                'c.name as campus_name',

                'al.name as level_name',
                'al.sort_order as level_sort_order',
            ])
            ->orderBy(
                'c.name'
            )
            ->orderBy(
                'al.sort_order'
            )
            ->orderBy(
                'sg.name'
            )
            ->get();
    }
}