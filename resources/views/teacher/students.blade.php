<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alumnos de la materia · Academic</title>
<style>body{background:#090f20;color:#e2e8f0;font:15px system-ui;margin:0}main{max-width:1050px;margin:35px auto;padding:0 20px}.muted{color:#94a3b8}.card{border:1px solid #34425c;background:#111b30;border-radius:12px;padding:18px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px;border-bottom:1px solid #34425c}th{color:#94a3b8;font-size:13px}a{color:#93c5fd}</style></head>
<body><main><a href="{{ route('teacher.dashboard') }}">← Mis grupos y materias</a>
<h1>{{ $assigned->subject_name }}</h1><p class="muted">{{ $assigned->level_name }} · Grado {{ $assigned->grade_label }} · {{ $assigned->group_name }} · {{ $assigned->cycle_name }}</p>
<div class="card"><h2>Alumnos activos ({{ $students->count() }})</h2><table><thead><tr><th>#</th><th>Matrícula</th><th>Alumno</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>{{ $loop->iteration }}</td><td>{{ $student->student_code }}</td><td>{{ $student->last_name }} {{ $student->first_name }}</td></tr>
@empty<tr><td colspan="3">No hay alumnos activos inscritos en este grupo y ciclo.</td></tr>@endforelse
</tbody></table></div><p class="muted">Consulta de solo lectura. Las notas y evaluaciones se implementarán después.</p>
</main></body></html>