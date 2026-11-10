@extends('academic.grades.layout')
@section('title', 'Academic · Captura docente')
@section('content')
<h1>Mis calificaciones</h1><p class="muted">Selecciona una materia, grupo y periodo. Tus borradores no son visibles para los tutores.</p>
@forelse($assignments as $assignment)
<section class="panel"><h2>{{ $assignment->subject_name }}</h2><p class="muted">{{ $assignment->group_name }}</p><div class="toolbar">
@php $matches=$periods->filter(fn($p)=> (int)$p->academic_cycle_id===(int)$assignment->academic_cycle_id && ($p->academic_level_id===null || (int)$p->academic_level_id===(int)$assignment->academic_level_id)); @endphp
@forelse($matches as $period)<a class="btn secondary" href="{{route('academic.grades.teacher',['assignment'=>$assignment->id,'period'=>$period->id])}}">{{ $period->name }}</a>@empty<span class="muted">Dirección todavía no ha habilitado periodos.</span>@endforelse
</div></section>
@empty<div class="panel">No tienes asignaciones activas en el ciclo vigente.</div>@endforelse
@endsection
