@extends('layouts.app')

@section(
    'title',
    $notice->title.' | PaseLista'
)

@section('section-label', 'Dirección')
@section('page-title', 'Seguimiento de comunicado')

@section('topbar-actions')

    <a
        href="{{ route(
            'admin.notices.index'
        ) }}"
        class="
            btn
            btn-outline-secondary
            btn-sm
        "
    >
        <i class="ti ti-arrow-left me-1"></i>
        Avisos
    </a>

    <a
        href="{{ route(
            'admin.notices.edit',
            $notice->id
        ) }}"
        class="
            btn
            btn-outline-primary
            btn-sm
        "
    >
        <i class="ti ti-edit me-1"></i>
        Editar
    </a>

@endsection


@section('content')

    {{-- ================================================================
         COMUNICADO
         ================================================================ --}}

    <div class="card mb-3">

        <div class="card-body">

            <div
                class="
                    d-flex
                    flex-wrap
                    justify-content-between
                    gap-3
                "
            >

                <div>

                    <div
                        class="
                            d-flex
                            flex-wrap
                            align-items-center
                            gap-2
                            mb-2
                        "
                    >

                        <h2 class="mb-0">
                            {{ $notice->title }}
                        </h2>


                        @if(
                            $notice->status
                            === 'published'
                        )

                            <span
                                class="
                                    badge
                                    bg-green-lt
                                    text-green
                                "
                            >
                                Publicado
                            </span>

                        @elseif(
                            $notice->status
                            === 'draft'
                        )

                            <span
                                class="
                                    badge
                                    bg-yellow-lt
                                    text-yellow
                                "
                            >
                                Borrador
                            </span>

                        @else

                            <span
                                class="
                                    badge
                                    bg-secondary-lt
                                    text-secondary
                                "
                            >
                                Histórico
                            </span>

                        @endif


                        @if(
                            $notice->requires_ack
                        )

                            <span
                                class="
                                    badge
                                    bg-orange-lt
                                    text-orange
                                "
                            >
                                Requiere enterado
                            </span>

                        @endif

                    </div>


                    @if($notice->subtitle)

                        <div
                            class="
                                text-secondary
                                mb-2
                            "
                        >
                            {{ $notice->subtitle }}
                        </div>

                    @endif


                    @if($notice->publish_at)

                        <div
                            class="
                                small
                                text-secondary
                            "
                        >
                            Publicado:

                            {{
                                \Illuminate\Support\Carbon::parse(
                                    $notice->publish_at
                                )->format(
                                    'd/m/Y H:i'
                                )
                            }}
                        </div>

                    @endif

                </div>

            </div>


            @if($notice->banner_path)

                <div class="mt-3">

                    <img
                        src="{{ $notice->banner_path }}"
                        alt="{{
                            $notice->banner_alt
                            ?: $notice->title
                        }}"
                        class="img-fluid rounded"
                        style="
                            max-height: 300px;
                            width: 100%;
                            object-fit: cover;
                        "
                    >

                </div>

            @endif


            @if($notice->header)

                <div class="fw-semibold mt-4">
                    {{ $notice->header }}
                </div>

            @endif


            <div
                class="mt-3"
                style="white-space: pre-line;"
            >{{ $notice->body }}</div>


            @if($notice->footer)

                <div
                    class="
                        mt-3
                        text-secondary
                        small
                    "
                >
                    {{ $notice->footer }}
                </div>

            @endif

        </div>

    </div>


    {{-- ================================================================
         MÉTRICAS
         ================================================================ --}}

    <div class="row row-cards mb-3">

        <div class="col-6 col-lg-3">

            <div class="card card-sm h-100">

                <div class="card-body">

                    <div class="text-secondary">
                        Destinatarios
                    </div>

                    <div class="h1 mb-0">
                        {{
                            number_format(
                                $stats['recipients']
                            )
                        }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-lg-3">

            <div class="card card-sm h-100">

                <div class="card-body">

                    <div class="text-secondary">
                        Leído
                    </div>

                    <div
                        class="
                            h1
                            mb-0
                            text-green
                        "
                    >
                        {{
                            number_format(
                                $stats['read']
                            )
                        }}
                    </div>

                    <div
                        class="
                            small
                            text-secondary
                        "
                    >
                        {{
                            number_format(
                                $stats[
                                    'read_percentage'
                                ],
                                1
                            )
                        }}%
                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-lg-3">

            <div class="card card-sm h-100">

                <div class="card-body">

                    <div class="text-secondary">
                        Sin leer
                    </div>

                    <div
                        class="
                            h1
                            mb-0
                            text-warning
                        "
                    >
                        {{
                            number_format(
                                $stats['unread']
                            )
                        }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-6 col-lg-3">

            <div class="card card-sm h-100">

                <div class="card-body">

                    <div class="text-secondary">
                        Enterados
                    </div>

                    <div
                        class="
                            h1
                            mb-0
                            text-blue
                        "
                    >
                        {{
                            number_format(
                                $stats[
                                    'acknowledged'
                                ]
                            )
                        }}
                    </div>

                    <div
                        class="
                            small
                            text-secondary
                        "
                    >
                        {{
                            number_format(
                                $stats[
                                    'ack_percentage'
                                ],
                                1
                            )
                        }}%
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ================================================================
         ENTREGA
         ================================================================ --}}

    <div class="card mb-3">

        <div class="card-header">

            <div>

                <h3 class="card-title">
                    Distribución
                </h3>

                <div
                    class="
                        card-subtitle
                        text-secondary
                    "
                >
                    Estado de envío y recepción
                    del comunicado.
                </div>

            </div>

        </div>


        <div class="card-body">

            <div
                class="
                    d-flex
                    flex-wrap
                    gap-3
                "
            >

                <div>

                    Push enviado:

                    <strong>
                        {{
                            number_format(
                                $stats['push_sent']
                            )
                        }}
                    </strong>

                </div>


                <div>

                    Sin dispositivo activo:

                    <strong>
                        {{
                            number_format(
                                $stats['no_devices']
                            )
                        }}
                    </strong>

                </div>


                @if($notice->requires_ack)

                    <div>

                        Leídos sin confirmar:

                        <strong>
                            {{
                                number_format(
                                    max(
                                        0,
                                        $stats['read']
                                        - $stats[
                                            'acknowledged'
                                        ]
                                    )
                                )
                            }}
                        </strong>

                    </div>

                @endif

            </div>

        </div>

    </div>


    {{-- ================================================================
         FILTROS
         ================================================================ --}}

    <div class="card mb-3">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route(
                    'admin.notices.show',
                    $notice->id
                ) }}"
                class="
                    row
                    g-2
                    align-items-end
                "
            >

                <div class="col-12 col-md-5">

                    <label class="form-label">
                        Buscar tutor
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="
                            Nombre, correo o teléfono
                        "
                    >

                </div>


                <div class="col-12 col-md-4">

                    <label class="form-label">
                        Estado
                    </label>

                    <select
                        name="state"
                        class="form-select"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="read"
                            @selected(
                                $state === 'read'
                            )
                        >
                            Leídos
                        </option>

                        <option
                            value="unread"
                            @selected(
                                $state === 'unread'
                            )
                        >
                            Sin leer
                        </option>

                        <option
                            value="acknowledged"
                            @selected(
                                $state
                                === 'acknowledged'
                            )
                        >
                            Enterados
                        </option>

                        @if(
                            $notice->requires_ack
                        )

                            <option
                                value="pending_ack"
                                @selected(
                                    $state
                                    === 'pending_ack'
                                )
                            >
                                Leído sin confirmar
                            </option>

                        @endif

                    </select>

                </div>


                <div
                    class="
                        col-12
                        col-md-auto
                    "
                >

                    <button
                        type="submit"
                        class="
                            btn
                            btn-primary
                            w-100
                        "
                    >
                        <i
                            class="
                                ti
                                ti-filter
                                me-1
                            "
                        ></i>

                        Filtrar
                    </button>

                </div>


                <div
                    class="
                        col-12
                        col-md-auto
                    "
                >

                    <a
                        href="{{ route(
                            'admin.notices.show',
                            $notice->id
                        ) }}"
                        class="
                            btn
                            btn-outline-secondary
                            w-100
                        "
                    >
                        Limpiar
                    </a>

                </div>

            </form>

        </div>

    </div>


    {{-- ================================================================
         DESTINATARIOS
         ================================================================ --}}

    <div class="card">

        <div class="card-header">

            <div>

                <h3 class="card-title">
                    Seguimiento por tutor
                </h3>

                <div
                    class="
                        card-subtitle
                        text-secondary
                    "
                >
                    Lectura y confirmación
                    individual del comunicado.
                </div>

            </div>

        </div>


        <div class="table-responsive">

            <table
                class="
                    table
                    table-vcenter
                    card-table
                "
            >

                <thead>

                    <tr>
                        <th>Tutor</th>
                        <th>Lectura</th>

                        @if(
                            $notice->requires_ack
                        )
                            <th>Enterado</th>
                        @endif

                        <th>Push</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse(
                        $recipients
                        as $recipient
                    )

                        <tr>

                            <td>

                                <div
                                    class="fw-semibold"
                                >
                                    {{
                                        trim(
                                            (
                                                $recipient
                                                    ->first_name
                                                ?? ''
                                            )
                                            .' '.
                                            (
                                                $recipient
                                                    ->last_name
                                                ?? ''
                                            )
                                        )
                                        ?: 'Tutor'
                                    }}
                                </div>


                                @if(
                                    $recipient->email
                                )

                                    <div
                                        class="
                                            small
                                            text-secondary
                                        "
                                    >
                                        {{
                                            $recipient
                                                ->email
                                        }}
                                    </div>

                                @endif


                                @if(
                                    $recipient->phone
                                )

                                    <div
                                        class="
                                            small
                                            text-secondary
                                        "
                                    >
                                        {{
                                            $recipient
                                                ->phone
                                        }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                @if(
                                    $recipient->read_at
                                )

                                    <span
                                        class="
                                            badge
                                            bg-green-lt
                                            text-green
                                        "
                                    >
                                        Leído
                                    </span>

                                    <div
                                        class="
                                            small
                                            text-secondary
                                            mt-1
                                        "
                                    >
                                        {{
                                            \Illuminate\Support\Carbon::parse(
                                                $recipient
                                                    ->read_at
                                            )->format(
                                                'd/m/Y H:i'
                                            )
                                        }}
                                    </div>

                                @else

                                    <span
                                        class="
                                            badge
                                            bg-yellow-lt
                                            text-yellow
                                        "
                                    >
                                        Sin leer
                                    </span>

                                @endif

                            </td>


                            @if(
                                $notice->requires_ack
                            )

                                <td>

                                    @if(
                                        $recipient
                                            ->acknowledged_at
                                    )

                                        <span
                                            class="
                                                badge
                                                bg-blue-lt
                                                text-blue
                                            "
                                        >
                                            Enterado
                                        </span>

                                        <div
                                            class="
                                                small
                                                text-secondary
                                                mt-1
                                            "
                                        >
                                            {{
                                                \Illuminate\Support\Carbon::parse(
                                                    $recipient
                                                        ->acknowledged_at
                                                )->format(
                                                    'd/m/Y H:i'
                                                )
                                            }}
                                        </div>

                                    @else

                                        <span
                                            class="
                                                badge
                                                bg-secondary-lt
                                                text-secondary
                                            "
                                        >
                                            Pendiente
                                        </span>

                                    @endif

                                </td>

                            @endif


                            <td>

                                @if(
                                    $recipient
                                        ->push_status
                                    === 'sent'
                                )

                                    <span
                                        class="
                                            badge
                                            bg-green-lt
                                            text-green
                                        "
                                    >
                                        Enviado
                                    </span>

                                @elseif(
                                    $recipient
                                        ->push_status
                                    === 'no_devices'
                                )

                                    <span
                                        class="
                                            badge
                                            bg-yellow-lt
                                            text-yellow
                                        "
                                    >
                                        Sin dispositivo
                                    </span>

                                @elseif(
                                    $recipient
                                        ->push_status
                                    === 'pending'
                                )

                                    <span
                                        class="
                                            badge
                                            bg-blue-lt
                                            text-blue
                                        "
                                    >
                                        Pendiente
                                    </span>

                                @else

                                    <span
                                        class="
                                            badge
                                            bg-red-lt
                                            text-red
                                        "
                                    >
                                        {{
                                            $recipient
                                                ->push_status
                                        }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="{{
                                    $notice->requires_ack
                                        ? 4
                                        : 3
                                }}"
                                class="
                                    text-center
                                    text-secondary
                                    py-5
                                "
                            >

                                No hay destinatarios
                                con estos filtros.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if(
            $recipients->hasPages()
        )

            <div class="card-footer">

                {{
                    $recipients->links()
                }}

            </div>

        @endif

    </div>

@endsection