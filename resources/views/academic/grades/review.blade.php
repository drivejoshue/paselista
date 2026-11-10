@extends('academic.grades.layout')
@section('title', 'Academic · Revisar calificaciones')
@section('content')
<p><a href="{{route('academic.grades.director')}}">← Validación de calificaciones</a></p>
<h1>{{$a->subject_name}} · {{$a->group_name}}</h1><p class="muted">Estado: <span class="tag">{{$b->status}}</span></p>
<section class="panel"><table><thead><tr><th>Matrícula</th><th>Alumno</th><th>Calificación</th><th>Corrección interna</th></tr></thead><tbody>
@foreach($roster as $student)
@php $grade=$grades->get($student->enrollment_id); @endphp
<tr><td>{{$student->student_code}}</td><td>{{$student->last_name}} {{$student->first_name}}</td><td>@if($grade && $grade->numeric_value !== null)<span class="{{ (float)$grade->numeric_value < 6 ? 'grade-low-pill' : 'grade-ok-pill' }}">{{ number_format((float)$grade->numeric_value, 1, '.', '') }}</span>@else<span class="muted">Pendiente</span>@endif</td>
<td>@if($b->status==='published' && $grade)
<form method="POST" action="{{route('academic.grades.correct',['book'=>$b->id,'grade'=>$grade->id])}}" class="toolbar">@csrf
<input type="number" step="0.1" min="0" max="10" required name="numeric_value" value="{{number_format((float)$grade->numeric_value, 1, '.', '')}}" aria-label="Nueva calificación">
<input type="text" required minlength="10" maxlength="1000" name="reason" placeholder="Motivo obligatorio (mín. 10 caracteres)" aria-label="Motivo">
<button class="secondary" onclick="return confirm('¿Registrar corrección auditada?')">Corregir</button></form>
@endif</td></tr>
@endforeach
</tbody></table></section>
@if($b->status==='submitted')<form method="POST" action="{{route('academic.grades.publish',['book'=>$b->id])}}" onsubmit="return confirm('¿Validar y publicar estas calificaciones?');">@csrf<button type="submit">Validar y publicar</button></form>@endif
@endsection
