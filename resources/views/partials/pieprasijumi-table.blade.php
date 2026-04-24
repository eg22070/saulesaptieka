<form action="{{ route('pieprasijumi.bulkComplete') }}" method="GET" id="bulkCompleteForm">

    <div class="mb-2">
        <button type="submit" class="btn btn-sm btn-success" id="bulkCompleteBtn" disabled>
            Izpildīt šos ierakstu/us
        </button>
    </div>

    {{ $pieprasijumi->links() }}
<table class="table custom-requests-table">
    <thead>
        <tr>
            @php
                $isDateSort = ( ($sort ?? request('sort')) === 'created_at' );
                $currentDir = $direction ?? request('direction', 'asc');
                $nextDir    = $isDateSort && $currentDir === 'asc' ? 'desc' : 'asc';
                $artikuliMap = $artikuli->keyBy('id');
            @endphp
            <th style="width: 3%;  border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
            <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">
                @php
                    $query = [
                        'search'          => request('search'),
                        'status_filter'   => request('status_filter', $status_filter ?? ''),
                        'pharmacy_filter' => request('pharmacy_filter'),
                        'buyer_filter'    => request('buyer_filter'),
                        'date_from'       => request('date_from'),
                        'date_to'         => request('date_to'),
                        'sort'            => 'created_at',
                        'direction'       => $nextDir,
                    ];
                @endphp
                <a href="{{ route('pieprasijumi.index', $query) }}" style="color: inherit; text-decoration: none;">
                    Datums
                    @if($isDateSort)
                        @if($currentDir === 'asc')
                            ▲
                        @else
                            ▼
                        @endif
                    @endif
                </a>
            </th>
            <th style="width: 12%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aptieka</th>
            <th style="width: 5%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Valsts</th>
            <th style="width: 10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ID numurs</th>
            <th style="width: 27%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
            <th style="width: 5%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Sk.</th>
            <th style="width: 5%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Izr. sk.</th>
            <th style="width: 8%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Aizliegums</th>
            <th style="width: 7%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Statuss</th>
            <th style="width: 6%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Iepircējs</th>
            <th style="width: 6%;  border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pieprasijumi as $art)
            <!-- Main Row -->
            <tr class="request-row {{ $art->cito ? 'request-row-cito' : '' }}">
                <td style="border:1px solid #080000ff; padding:4px; text-align:center; vertical-align:middle;">
                    @if(!$art->completed)
                        <input type="checkbox"
                            name="ids[]"
                            value="{{ $art->id }}"
                            class="row-checkbox">
                    @else
                        <span style="font-size: 1.2rem;">✅</span>
                    @endif
                </td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                    {{ optional($art->datums)->format('d/m/Y') ?? ($art->pazinojuma_datums ?: $art->created_at->format('d/m/Y')) }}
                </td>
                <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->aptiekas->nosaukums }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->valsts }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->id_numurs }}</td>
                <td class="toggle-details"
                    style="border: 1px solid #080000ff; padding: 4px; cursor: pointer;"
                    title="Klikšķiniet, lai redzētu detaļas">
                    <b>{{ $art->artikuli->nosaukums }}</b>
                    <button type="button"
                            class="copy-btn"
                            data-name="{{ $art->artikuli->nosaukums }}"
                            onclick="copyProductName(this); event.stopPropagation();">
                        <img src="{{ asset('images/copy.png') }}"
                            alt="Kopēt"
                            class="copy-icon">
                        <span class="copy-text">Kopēt</span>
                    </button>
                </td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->daudzums }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->izrakstitais_daudzums }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                    <span class="badge-pill badge-aizliegums badge-aizliegums-{{ Str::slug($art->aizliegums) }}">
                        {{ $art->aizliegums }}
                    </span>
                </td>

                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                    <span class="badge-pill badge-status badge-status-{{ Str::slug($art->statuss) }}">
                        {{ $art->statuss }}
                    </span>
                </td>

                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                    <span class="badge-pill badge-buyer">
                        {{ $art->iepircejs }}
                    </span>
                </td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-sm btn-primary edit-request-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#requestModal"
                                    data-id="{{ $art->id }}"
                                    data-aptiekas_id="{{ $art->aptiekas->id }}"
                                    data-aptiekas_nosaukums="{{ $art->aptiekas->nosaukums }}" 
                                    data-artikula_id="{{ $art->artikuli->id }}"
                                    data-artikula_nosaukums="{{ $art->artikuli->nosaukums }}" 
                                    data-daudzums="{{ $art->daudzums }}"
                                    data-izrakstitais_daudzums="{{ $art->izrakstitais_daudzums }}"
                                    data-pazinojuma_datums="{{ $art->pazinojuma_datums }}"
                                    data-statuss="{{ $art->statuss }}"
                                    data-aizliegums="{{ $art->aizliegums }}"
                                    data-iepircejs="{{ $art->iepircejs }}"
                                    data-piegades_datums="{{ $art->piegades_datums }}"
                                    data-piezimes="{{ $art->piezimes }}"
                                    data-completed="{{ $art->completed ? '1' : '0' }}"
                                    data-cito="{{ $art->cito ? '1' : '0' }}"
                                    data-edit-mode="true">
                                    Labot</button>

                            <!-- Delete Form -->
                            <form action="{{ route('pieprasijumi.destroy', $art->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        onclick="if(confirm('Vai tiešām vēlaties dzēst šo pieprasījumu?')) document.getElementById('delete-form-{{ $art->id }}').submit();">
                                    Dzēst
                                </button>
                        </td>
                    </tr>
                    
                    <!-- Additional Info Row (Hidden by default) -->
                    <tr class="additional-info" style="display:none;">
                        <td colspan="12" style="background-color: #f8f9fa; border: 1px solid #080000ff;">
                            <div style="padding: 10px;">
                                @php
                                    $previousNames = [];
                                    if (is_array($art->previous_artikuli_ids)) {
                                        foreach ($art->previous_artikuli_ids as $prevId) {
                                            if (isset($artikuliMap[$prevId])) {
                                                $previousNames[] = $artikuliMap[$prevId]->nosaukums;
                                            }
                                        }
                                    }
                                @endphp
                                @if(!empty($previousNames))
                                    <strong>Bijušie artikuli:</strong>
                                    {{ implode(' -----> ', $previousNames) }} <br>
                                @endif
                                <strong>Piezīmes:</strong> {!! nl2br(e($art->piezimes ?? '-')) !!} <br>
                                <strong>Piegādes datums:</strong> {{ $art->piegades_datums }} <br>
                                
                                <strong>Izpildīja:</strong>

                                @if($art->completer)
                                    {{ $art->completer->name }} ({{ $art->completed_at ? $art->completed_at->format('d/m/Y') : '' }})
                                @else
                                     <!-- Or leave blank if you prefer -->
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">Netika atrasti pieprasījumi!</td>
                    </tr>
                @endforelse
            </tbody>
</table>
{{ $pieprasijumi->links() }}
</form>
@foreach($pieprasijumi as $art)
    <form id="delete-form-{{ $art->id }}"
          action="{{ route('pieprasijumi.destroy', $art->id) }}"
          method="POST"
          style="display:none;">
        @csrf
        @method('DELETE')
    </form>
@endforeach
<style>
    /* Header background */
    .custom-requests-table thead th {
        position: sticky;
        top: 0;
        z-index: 2; 
        background-color: #373330;
        color: #ffffff;
    }


    .badge-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;      /* pill shape */
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid transparent;
    white-space: nowrap;
    }
    .request-row {
        background-color: #f1eae3 !important; /* non-cito default */
    }
    /* CITO rows override */
    .request-row.request-row-cito {
        background-color: #fff8b3 !important; /* light yellow as you had */
    }

    /* Hover for non-cito rows */
    .request-row:not(.request-row-cito):hover {
        background-color: rgba(241,234,227,0.5) !important; /* same color, 50% */
    }

    /* Hover for CITO rows – slightly darker/lighter */
    .request-row.request-row-cito:hover {
        background-color: #ffe97a !important; /* tweak as you like */
    }
    .copy-btn {
        border: none;
        background: transparent;
        padding: 0;
        margin-left: 6px;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        font-size: 0.9rem;
        color: #666;
    }

    /* remove focus/active outlines and borders */
    .copy-btn:focus,
    .copy-btn:active,
    .copy-btn:focus-visible {
        outline: none;
        box-shadow: none;
        border: none;
    }

    .copy-btn:hover {
        color: #000;
    }

    .copy-icon {
        width: 14px;
        height: 14px;
        margin-right: 4px;
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

/* Default styles 
.badge-status   { background-color: #e0f5e5; color: #1b5e20; border-color: #1b5e20; }
.badge-aizliegums { background-color: #e0ecff; color: #1a237e; border-color: #1a237e; }
.badge-buyer    { background-color: #f5f5f5; color: #424242; border-color: #9e9e9e; }

 Per-status colors (slugged variants) 
.badge-status-pasutits        { background-color: #4caf50; color: #fff; border-color: #2e7d32; }
.badge-status-atcelts         { background-color: #ffcdd2; color: #b71c1c; border-color: #b71c1c; }
.badge-status-mainita-piegade { background-color: #fff3e0; color: #ef6c00; border-color: #ef6c00; }
.badge-status-ir-noliktava    { background-color: #bbdefb; color: #0d47a1; border-color: #0d47a1; }
.badge-status-daleji-atlikuma { background-color: #fff9c4; color: #f9a825; border-color: #f9a825; }

 Per-aizliegums colors 
.badge-aizliegums-drikst-aizvietot   { background-color: #d1eaff; color: #003c8f; border-color: #003c8f; }
.badge-aizliegums-nedrikst-aizvietot { background-color: #ffebee; color: #b71c1c; border-color: #b71c1c; }
.badge-aizliegums-nvd               { background-color: #e8f5e9; color: #1b5e20; border-color: #1b5e20; }
.badge-aizliegums-stacionars        { background-color: #ede7f6; color: #4a148c; border-color: #4a148c; }
*/
</style>
