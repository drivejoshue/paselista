@extends('academic.grades.layout')
@section('title', 'Academic · Validación de calificaciones')
@section('content')
<h1>Validación de calificaciones</h1><p class="muted">Dirección y School Admin revisan las capturas antes de publicarlas.</p>
<section class="panel"><h2>Periodos de evaluación</h2>
@if($cycle)
<form method="POST" action="{{route('academic.grades.periods.store')}}" class="toolbar">@csrf
<input type="hidden" name="academic_cycle_id" value="{{$cycle->id}}">
<label>Nivel <select name="academic_level_id" required>@foreach($levels as $level)<option value="{{$level->id}}">{{$level->name}}</option>@endforeach</select></label>
<label>Nombre <input name="name" required maxlength="100" placeholder="Trimestre 1"></label>
<label>Orden <input type="number" name="sequence" min="1" max="20" value="1" required></label>
<button type="submit">Crear periodo</button></form>
@else<p>No hay ciclo activo.</p>@endif
<p class="muted small">Periodos existentes: @forelse($periods as $p)<span class="tag">{{$p->name}} · nivel {{$p->academic_level_id ?? 'general'}}</span> @empty ninguno @endforelse</p>
</section>
<section class="panel"><div class="toolbar" style="justify-content:space-between">
<h2>Capturas por revisar</h2>
@php $pendingToPublish=$books->where('status','submitted')->count(); @endphp
@if($pendingToPublish > 0)
<form method="POST" action="{{ route('academic.grades.publish-all') }}" onsubmit="return confirm('¿Validar y PUBLICAR las '+@json($pendingToPublish)+' capturas enviadas? Las calificaciones serán oficiales para consultas autorizadas.');">
@csrf<button type="submit">Validar todas las enviadas ({{ $pendingToPublish }})</button></form>
@endif
</div>
<p class="muted small">Solo se publican capturas enviadas, completas y válidas. Los borradores permanecen sin publicar. Si alguna enviada tiene errores, no se publica ninguna.</p><table><thead><tr><th>Grupo</th><th>Materia</th><th>Periodo</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($books as $b)<tr><td>{{$b->group_name}}</td><td>{{$b->subject_name}}</td><td>{{$b->period_name}}</td><td><span class="tag">{{$b->status}}</span></td><td><a href="{{route('academic.grades.review',['book'=>$b->id])}}">Revisar</a></td></tr>
@empty<tr><td colspan="5">Aún no hay capturas iniciadas.</td></tr>@endforelse
</tbody></table></section>
@endsection
