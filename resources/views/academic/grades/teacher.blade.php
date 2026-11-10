@extends('academic.grades.layout')
@section('title', 'Academic · Capturar calificaciones')
@section('content')
<p><a href="{{route('academic.grades.teacher.index')}}">← Mis calificaciones</a></p>
<h1>{{ $a->subject_name }}</h1><p class="muted">{{ $a->group_name }} · {{ $p->name }}</p>
<div class="panel"><div class="toolbar"><strong>Estado:</strong><span class="tag">{{ $book?->status ?? 'draft' }}</span><span class="muted">Escala: 0 a 10, incrementos de 0.1. Pendiente = sin calificación.</span></div></div>
<form id="grades-form" method="POST" action="{{route('academic.grades.teacher.save',['assignment'=>$a->id,'period'=>$p->id])}}">@csrf
<div class="panel"><table><thead><tr><th>Matrícula</th><th>Alumno</th><th>Calificación</th></tr></thead><tbody>
@foreach($roster as $student)
<tr><td>{{ $student->student_code }}</td><td>{{ $student->last_name }} {{ $student->first_name }}</td>
<td><input aria-label="Calificación de {{ $student->first_name }} {{ $student->last_name }}" type="number" class="grade-input" min="0" max="10" step="0.1" inputmode="decimal" name="grades[{{$student->enrollment_id}}]" value="{{ old('grades.'.$student->enrollment_id, $grades->get($student->enrollment_id) !== null ? number_format((float)$grades->get($student->enrollment_id), 1, '.', '') : '') }}" {{($book && $book->status !== 'draft')?'disabled':''}}></td></tr>
@endforeach
</tbody></table></div>
@if(!$book || $book->status === 'draft')<div class="toolbar"><button type="submit">Guardar borrador</button><span class="muted small">Puedes continuar después. El envío se realiza por separado.</span></div>@endif
</form>
@if($book && $book->status === 'draft')
<form method="POST" action="{{route('academic.grades.teacher.submit',['assignment'=>$a->id,'period'=>$p->id])}}" style="margin-top:14px" onsubmit="return confirm('¿Enviar las calificaciones guardadas a Dirección? Ya no podrás editarlas.');">@csrf<button type="submit" class="secondary">Enviar a Dirección para validación</button></form>
@endif
@if($book && $book->status === 'submitted')<p class="notice">Captura enviada. Esperando validación de Dirección.</p>@endif
@if($book && $book->status === 'published')<p class="notice">Calificaciones validadas y publicadas.</p>@endif
<script>
(() => {
  const update = (input) => {
    const value = input.value.trim();
    input.classList.toggle('grade-low', value !== '' && Number(value) < 6 && Number(value) >= 0);
    input.classList.toggle('grade-invalid', value !== '' && (!/^\d+(?:\.\d)?$/.test(value) || Number(value) > 10));
  };
  document.querySelectorAll('.grade-input').forEach((input) => {
    update(input);
    input.addEventListener('input', () => update(input));
  });
})();
</script>
@endsection
