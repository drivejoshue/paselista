@extends('layouts.sysadmin')
@section('title', 'Plan y módulos SchoolCore')
@section('content')
@php
    $plan = $subscription['plan'] ?? null;
    $cycle = $subscription['billingCycle'] ?? null;
    $cycleLabel = $cycle === 'annual' ? 'anual' : 'mensual';
    $states = collect($subscription['modules'] ?? [])->keyBy('code');
    $activeContract = $contracts->firstWhere('status', 'active');
    $isLive = (bool) ($subscription['licenseActive'] ?? false);
    $money = fn ($value) => '$'.number_format((float) $value, 2).' MXN';
@endphp
<div class="container-xl">
  <div class="page-header mb-4"><div class="row align-items-center g-3"><div class="col">
    <div class="page-pretitle">SYSADMIN / ESCUELAS / SCHOOLCORE</div>
    <h2 class="page-title">Plan, complementos y activación</h2>
    <div class="text-secondary mt-1">{{ $institution->name }} · Escuela #{{ $school }}</div>
  </div><div class="col-auto"><a href="{{ route('sysadmin.schools.show', $school) }}" class="btn btn-outline-primary">Administrar escuela</a></div></div></div>

  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger"><strong>No se completó la operación.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

  <div class="row row-cards mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-secondary small">Plan base</div><h2 class="mt-2">{{ $plan['name'] ?? 'Sin plan' }}</h2><span class="badge {{ $isLive ? 'bg-green-lt' : 'bg-red-lt' }}">{{ $isLive ? 'Licencia activa' : 'Licencia inactiva' }}</span><div class="text-secondary small mt-2">Licencia #{{ $subscription['licenseId'] ?? '—' }}</div></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-secondary small">Precio base contratado</div><h2 class="mt-2">{{ $money($subscription['baseContractPrice'] ?? 0) }}</h2><span class="text-secondary">{{ ucfirst($cycleLabel) }}</span></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-secondary small">Complementos recurrentes</div><h2 class="mt-2">{{ $money($subscription['recurringAddons'] ?? 0) }}</h2><span class="text-secondary">{{ ucfirst($cycleLabel) }}</span></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-secondary small">Total recurrente estimado</div><h2 class="text-primary mt-2">{{ $money($subscription['estimatedRecurringTotal'] ?? 0) }}</h2><span class="text-secondary">No es factura emitida</span></div></div></div>
  </div>

  <div class="card mb-4"><div class="card-body"><div class="row align-items-center g-3"><div class="col">
    <h3 class="mb-2">Suscripción principal</h3>
    <div class="text-secondary">Vigencia: {{ $subscription['startsOn'] ?? '—' }} al {{ $subscription['expiresOn'] ?? '—' }} · Facturación {{ $cycleLabel }}</div>
    <div class="mt-2">@foreach($states->where('included', true) as $state)<span class="badge bg-green-lt me-1">{{ $state['name'] }} · Incluido</span>@endforeach</div>
  </div><div class="col-auto"><a href="{{ route('sysadmin.schools.show', $school) }}" class="btn btn-outline-primary">Cambiar plan principal</a></div></div></div></div>

  <div class="alert alert-info mb-4"><strong>Activación directa:</strong> selecciona el complemento, revisa la tarifa y confirma. Si no hay contrato comercial, se creará automáticamente utilizando la vigencia y periodicidad de la licencia. Se aplicará el prorrateo por los días restantes. El cargo queda pendiente, no pagado.</div>

  <h3 class="mb-3">Módulos de la escuela</h3>
  <div class="row row-cards">
  @foreach($modules as $module)
    @php
      $state = $states->get($module->code, []);
      $included = (bool) ($state['included'] ?? false);
      $core = (bool) $module->is_core;
      $enabled = $module->accessSource !== null;
      $line = $module->contractLine;
      $entitlement = $module->entitlement;
      $contractedActive = $line && $line->status === 'active' && $entitlement && $entitlement->status === 'active';
      $catalogPrice = $cycle === 'annual' ? $module->annual_price : $module->monthly_price;
      $suggestedPrice = $line?->unit_price ?? ($module->pricing_model === 'free' ? '0.00' : $catalogPrice);
      $canActivate = $isLive && !$included && !$core && $module->status === 'active' && in_array($module->pricing_model, ['paid','free','quote'], true);
      $canSuspend = $contractedActive && !$included && !$core && $isLive;
      $isComingSoon = $module->status !== 'active';
    @endphp
    <div class="col-lg-6"><div class="card h-100">
      <div class="card-header"><div><h3 class="card-title mb-1">{{ $module->name }}</h3><code>{{ $module->code }}</code></div><div class="card-actions d-flex gap-2">@if($included)<span class="badge bg-blue-lt">Incluido</span>@endif<span class="badge {{ $enabled ? 'bg-green-lt' : 'bg-secondary-lt' }}">{{ $enabled ? 'Activo' : 'Inactivo' }}</span></div></div>
      <div class="card-body">
        <p class="text-secondary">{{ $module->description }}</p>
        @if($core)
          <div class="alert alert-info mb-0">Núcleo obligatorio del ERP. No se factura como complemento.</div>
        @elseif($included)
          <div class="alert alert-success mb-0">Incluido en el plan principal. No se cobra nuevamente.</div>
        @elseif($isComingSoon && !$canSuspend)
          <div class="alert alert-secondary mb-0">No disponible para nuevas contrataciones.</div>
        @elseif(!$canActivate && !$canSuspend)
          <div class="alert alert-warning mb-0">La escuela requiere una licencia vigente para contratar este módulo.</div>
        @elseif($canSuspend)
          <div class="row g-3 align-items-end"><div class="col-12"><div class="text-secondary small">Precio contratado · {{ $cycleLabel }}</div><h3>{{ $money($line->unit_price) }}</h3></div></div>
          <form method="POST" action="{{ route('sysadmin.schools.modules.update', ['school' => $school, 'module' => $module->id]) }}" onsubmit="return confirm('¿Suspender {{ $module->name }}? Se revocará el acceso; los datos y cargos se conservarán.');">@csrf @method('PUT')<input type="hidden" name="enabled" value="0"><button class="btn btn-outline-danger w-100 mt-3" type="submit"><i class="ti ti-player-pause me-2"></i>Suspender módulo</button></form>
        @else
          <form method="POST" action="{{ route('sysadmin.schools.modules.update', ['school' => $school, 'module' => $module->id]) }}" onsubmit="return confirm('¿Contratar y activar {{ $module->name }}? Se generará el cargo proporcional pendiente si corresponde.');">
            @csrf @method('PUT')<input type="hidden" name="enabled" value="1">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Tarifa de catálogo / {{ $cycleLabel }}</label><input class="form-control" readonly value="{{ $catalogPrice !== null ? $money($catalogPrice) : 'Bajo cotización' }}"></div>
              <div class="col-md-6"><label class="form-label">Precio pactado (MXN)</label><div class="input-group"><span class="input-group-text">$</span><input type="number" name="unit_price" class="form-control" min="0" step="0.01" max="9999999999.99" value="{{ $suggestedPrice }}" placeholder="Introduce precio" required></div></div>
            </div>
            <div class="text-secondary small mt-3">{{ ucfirst($cycleLabel) }} · Fecha efectiva: {{ now()->toDateString() }} · Fin: {{ $subscription['expiresOn'] ?? '—' }}</div>
            <button class="btn btn-primary w-100 mt-3" type="submit"><i class="ti ti-circle-plus me-2"></i>Contratar y activar {{ $module->name }}</button>
          </form>
        @endif
      </div>
    </div></div>
  @endforeach
  </div>

  <div class="card mt-4 mb-4"><div class="card-header"><h3 class="card-title">Contratos y cargos recientes</h3></div><div class="card-body">
    <div class="row g-4"><div class="col-md-5"><h4>Contratos</h4>@forelse($contracts as $contract)<div class="d-flex justify-content-between border-bottom py-2"><div><strong>{{ $contract->contract_number }}</strong><div class="text-secondary small">{{ $contract->starts_on }} — {{ $contract->ends_on ?? 'Sin vencimiento' }}</div></div><span class="badge {{ $contract->status === 'active' ? 'bg-green-lt' : 'bg-secondary-lt' }}">{{ $contract->status }}</span></div>@empty<p class="text-secondary">Aún no hay contratos de complementos.</p>@endforelse</div>
    <div class="col-md-7"><h4>Cargos</h4><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Concepto</th><th>Importe</th><th>Estado</th></tr></thead><tbody>@forelse($charges as $charge)<tr><td>{{ $charge->description }}</td><td>{{ $money($charge->amount) }}</td><td><span class="badge bg-yellow-lt">{{ $charge->status }}</span></td></tr>@empty<tr><td colspan="3" class="text-secondary">Sin cargos registrados.</td></tr>@endforelse</tbody></table></div></div></div>
  </div></div>
</div>
@endsection
