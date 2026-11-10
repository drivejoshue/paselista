<div style="
    position: relative;
    height: 72px;
    margin-bottom: 20px;
    border-bottom: 3px solid {{ $branding['primary'] }};
">

    @if(!empty($branding['logo']))
        <img
            src="{{ $branding['logo'] }}"
            alt="Logo institucional"
            width="52"
            height="52"
            style="
                position: absolute;
                top: 0;
                left: 0;
                width: 52px;
                height: 52px;
                max-width: 52px;
                max-height: 52px;
            "
        >
    @endif

    <div style="
        position: absolute;
        top: 5px;
        left: {{ !empty($branding['logo']) ? '68px' : '0' }};
        right: 135px;
    ">
        <div style="
            font-size: 15px;
            font-weight: bold;
            line-height: 1.3;
            color: {{ $branding['secondary'] }};
        ">
            {{ $branding['school_name'] }}
        </div>

        @if(!empty($branding['legal_name'])
            && $branding['legal_name'] !== $branding['school_name'])
            <div style="
                margin-top: 4px;
                font-size: 8px;
                color: #64748b;
            ">
                {{ $branding['legal_name'] }}
            </div>
        @endif

        @if(!empty($branding['address']))
            <div style="
                margin-top: 4px;
                font-size: 8px;
                color: #64748b;
            ">
                {{ $branding['address'] }}
            </div>
        @endif
    </div>

    <div style="
        position: absolute;
        top: 7px;
        right: 0;
        width: 125px;
        text-align: right;
        font-size: 8px;
        font-weight: bold;
        line-height: 1.6;
        color: {{ $branding['primary'] }};
    ">
        REPORTE INFORMATIVO
        <div>NO OFICIAL</div>
    </div>

</div>
