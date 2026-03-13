@php $artikuliMap = $artikuli->keyBy('id'); @endphp


    {{ $pasutijumi->links() }}

    <table class="table custom-requests-table">
        <thead>
            <tr>
                <th style="width:7%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Statuss</th>
                @php
                    $currentSort = $sort ?? request('sort', 'datums');
                    $currentDir  = $direction ?? request('direction', 'desc');
                    $isDateSort  = ($currentSort === 'datums');
                    $nextDir     = $isDateSort && $currentDir === 'asc' ? 'desc' : 'asc';

                    $query = [
                        'search'        => request('search'),
                        'status_filter' => request('status_filter', request('status_filter', 'neizpildits')),
                        'date_from'     => request('date_from'),
                        'date_to'       => request('date_to'),
                        'sort'          => 'datums',
                        'direction'     => $nextDir,
                    ];
                @endphp
                <th style="width:8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">
                    <a href="{{ route('pasutijumi.index', $query) }}" style="color: inherit; text-decoration: none;">
                        Pieprasījuma Datums
                        @if($isDateSort)
                            @if($currentDir === 'asc')
                                ▲
                            @else
                                ▼
                            @endif
                        @endif
                    </a>
                </th>
                <th style="width:29%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Zāļu nosaukums</th>
                <th style="width:5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Sk.</th>
                <th style="width:10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Pasūt. Nr.</th>
                <th style="width:13%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Receptes Nr.</th>
                <th style="width:14%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Vārds Uzvārds</th>
                <th style="width:10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Tālr. e-pasts</th>
                <th style="width:10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Pasūtījuma datums</th>
                @if(auth()->check() && strtolower(auth()->user()->role) !== 'farmaceiti')
                <th style="width:5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">-</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($pasutijumi as $p)
                @php
                    // $p->skaits is decimal in DB
                    $sk = (float) $p->skaits;
                    // if integer, show without decimals; otherwise show as is
                    $skaitsDisplay = fmod($sk, 1) == 0.0 ? (int) $sk : rtrim(rtrim(number_format($sk, 2, ',', ''), '0'), ',');
                @endphp
                @php
                    $s = strtolower($p->statuss ?? 'neizpildits');
                    $statusLabels = [
                        'izpildits'   => 'izpildīts',
                        'neizpildits' => 'neizpildīts',
                        'atcelts'     => 'atcelts',
                    ];
                    $displayStatus = $statusLabels[$s] ?? $s;
                @endphp
                <tr class="request-row status-{{ \Illuminate\Support\Str::slug($s) }}" style="">
                    <td style="border: 1px solid #080000ff; padding: 4px; text-align:center;">
                        <span class="badge-status badge-status-{{ \Illuminate\Support\Str::slug($s) }}">
                            {{ $displayStatus }}
                        </span>
                    </td>
                    <td style="border: 1px solid #080000ff; padding: 4px;">{{ $p->datums?->format('d/m/Y') }}</td>
                    <td class="toggle-details"
                        style="border: 1px solid #080000ff; padding: 4px; cursor:pointer;"
                        title="Klikšķiniet, lai redzētu detaļas">
                        <b>{{ $p->product?->nosaukums ?? ($artikuliMap[$p->artikula_id]->nosaukums ?? '-') }}</b>
                    </td>
                    <td style="border: 1px solid #080000ff; padding: 4px; text-align:center;">
                        {{ $skaitsDisplay }}
                    </td>
                    <td style="border: 1px solid #080000ff; padding: 4px;">{{ $p->pasutijuma_numurs }}</td>
                    <td style="border: 1px solid #080000ff; padding: 4px;">
                        {!! nl2br(e(str_replace(' ', "\n", $p->receptes_numurs))) !!}
                    </td>
                    <td style="border: 1px solid #080000ff; padding: 4px;">{{ $p->vards_uzvards }}</td>
                    <td style="border: 1px solid #080000ff; padding: 4px;">{{ $p->talrunis_epasts }}</td>
                    <td style="border: 1px solid #080000ff; padding: 4px; text-align:center;">{{ optional($p->pasutijuma_datums)->format('d/m/Y') }}</td>
                    @if(auth()->check() && strtolower(auth()->user()->role) !== 'farmaceiti')
                    <td style="border: 1px solid #080000ff; padding: 4px;">
                        <button type="button" class="btn btn-sm btn-primary edit-btn"
                                data-id="{{ $p->id }}"
                                data-datums="{{ optional($p->datums)->format('d/m/Y') }}"
                                data-artikula_name="{{ $p->product?->nosaukums ?? ($artikuliMap[$p->artikula_id]->nosaukums ?? '') }}"
                                data-artikula_id="{{ $p->artikula_id }}"
                                data-skaits="{{ $p->skaits }}"
                                data-pasutijuma_numurs="{{ e($p->pasutijuma_numurs) }}"
                                data-receptes_numurs="{{ e($p->receptes_numurs) }}"
                                data-vards_uzvards="{{ e($p->vards_uzvards) }}"
                                data-talrunis_epasts="{{ e($p->talrunis_epasts) }}"
                                data-pasutijuma_datums="{{ optional($p->pasutijuma_datums)->format('d/m/Y') }}"
                                data-komentari="{{ ($p->komentari) }}"
                                data-statuss="{{ $s }}"
                                data-bs-toggle="modal" data-bs-target="#pasutijumiModal">
                            Labot
                        </button>

                        <form id="delete-form-{{ $p->id }}"
                            action="{{ route('pasutijumi.destroy', $p->id) }}"
                            method="POST"
                            style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_url" value="{{ url()->full() }}">
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    onclick="if(confirm('Dzēst?')) document.getElementById('delete-form-{{ $p->id }}').submit();">
                                Dzēst
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                <tr class="additional-info" style="display:none;">
                    <td colspan="11" style="background-color: #f8f9fa; border: 1px solid #080000ff;">
                        <div style="padding: 10px;">
                            <strong>Komentāri:</strong> {{ $p->komentari ?: '-' }} <br>
                            <strong>Izveidoja:</strong> 
                            @if($p->creator)
                                {{ $p->creator->name }}
                                @if($p->created_at)
                                    ({{ $p->created_at->format('d/m/Y') }})
                                @endif
                            @else
                                -
                            @endif
                            <br><strong>Izpildīja:</strong>
                            @if($p->statuss === 'izpildits' && $p->completer)
                                {{ $p->completer->name }} 
                                @if($p->completed_at)
                                    ({{ $p->completed_at->format('d/m/Y') }})
                                @endif
                            @else
                                -
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" class="text-center">Nav ierakstu</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $pasutijumi->links() }}

<style>
    .toggle-details:hover {
        color: #0d6efd; /* Bootstrap primary blue */
    }
    /* keep header */
.custom-requests-table thead th {
    position: sticky;
    top: 0;
    z-index: 2; 
    background-color: #373330;
    color: #ffffff;
}

/* Badge styles */
.badge-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid transparent;
    text-transform: none;
}
.badge-status-neizpildits {
    background: #dddcdc;
    color: #412f1e;
    border:1px solid #412f1e;
}
.badge-status-izpildits {
    background: #d8f1d5;
    color: #2d7922;
    border:1px solid #2d7922;
}
.badge-status-atcelts {
    background: #eecabd;
    color: #c75126;
    border:1px solid #c75126;
}

/* Row background colors by status only (no alternating rows) */
.request-row.status-neizpildits { background-color: #f1eae3 !important; }
.request-row.status-izpildits   { background-color: #a6c2a2 !important; }
.request-row.status-atcelts     { background-color: #e9a58d !important; }

/* Hover behavior: use same color at 50% opacity */
/* neizpildits */
.request-row.status-neizpildits:hover {
    background-color: rgba(241,234,227,0.5) !important;
}

/* izpildits */
.request-row.status-izpildits:hover {
    background-color: rgba(166,194,162,0.5) !important;
}

/* atcelts */
.request-row.status-atcelts:hover {
    background-color: rgba(233,165,141,0.5) !important;
}
/* Soft pastel buttons for table actions */
.custom-requests-table .btn-sm.btn-primary {
  background-color: #a8d0ff;   /* pastel blue */
  color: #0b3a66;
  border: 1px solid rgba(11,58,102,0.12);
  box-shadow: none;
}

.custom-requests-table .btn-sm.btn-primary:hover,
.custom-requests-table .btn-sm.btn-primary:focus {
  background-color: #8cc0ff;   /* slightly stronger on hover */
  color: #04283f;
}

/* Soft pastel danger (delete) */
.custom-requests-table .btn-sm.btn-danger {
  background-color: #ffb3b3;   /* pastel red/pink */
  color: #6a0f0f;
  border: 1px solid rgba(170,10,10,0.12);
  box-shadow: none;
}

.custom-requests-table .btn-sm.btn-danger:hover,
.custom-requests-table .btn-sm.btn-danger:focus {
  background-color: #ff9999;
  color: #540b0b;
}

/* Optional: slightly rounder corners */
.custom-requests-table .btn-sm {
  border-radius: 6px;
}

/* Softer focus outline */
.custom-requests-table .btn:focus {
  box-shadow: 0 0 0 0.12rem rgba(0,0,0,0.06) !important;
  outline: none !important;
}

</style>

<script>

document.addEventListener('click', function (event) {
    const cell = event.target.closest('.toggle-details');
    if (!cell) return;

    const mainRow = cell.closest('tr');
    const additionalRow = mainRow.nextElementSibling;

    if (additionalRow && additionalRow.classList.contains('additional-info')) {
        additionalRow.style.display =
            (additionalRow.style.display === 'none' || additionalRow.style.display === '')
                ? 'table-row'
                : 'none';
    }
});
</script>
