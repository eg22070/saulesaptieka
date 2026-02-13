{{ $pieprasijumi->links() }}
<table class="table custom-requests-table">
    <thead>
        <tr>
            @php
                $isDateSort = ( ($sort ?? request('sort')) === 'datums' );
                $currentDir = $direction ?? request('direction', 'asc');
                $nextDir    = $isDateSort && $currentDir === 'asc' ? 'desc' : 'asc';
            @endphp
            <th style="width: 3%;  border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
            <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">
                @php
                    // keep existing filters/search in query when changing sort
                    $query = request()->except('page', 'sort', 'direction');
                    $query['sort']      = 'datums';
                    $query['direction'] = $nextDir;
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
            <tr class="request-row"
                style="background-color: {{ $loop->odd ? '#ffffff' : '#f0f0f0' }};">
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center; vertical-align: middle;">
                    @if($art->completed)
                        <span style="font-size: 1.2rem;">✅</span>
                    @endif
                </td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->datums->format('d/m/Y') }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->aptiekas->nosaukums }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->valsts }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->id_numurs }}</td>
                <td class="toggle-details"
                    style="border: 1px solid #080000ff; padding: 4px; cursor: pointer;"
                    title="Klikšķiniet, lai redzētu detaļas">
                    <b>{{ $art->artikuli->nosaukums }}</b>
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
                            <button class="btn btn-sm btn-primary edit-request-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#requestModal"
                                    data-id="{{ $art->id }}"
                                    data-datums="{{ $art->datums }}"
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
                                    data-edit-mode="true">
                                    Labot</button>

                            <!-- Delete Form -->
                            <form action="{{ route('pieprasijumi.destroy', $art->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Vai tiešām vēlaties dzēst šo pieprasījumu?')">Dzēst</button>
                            </form>
                        </td>
                    </tr>
                    
                    <!-- Additional Info Row (Hidden by default) -->
                    <tr class="additional-info" style="display:none;">
                        <td colspan="12" style="background-color: #f8f9fa; border: 1px solid #080000ff;">
                            <div style="padding: 10px;">
                                <strong>Piezīmes:</strong> {{ $art->piezimes }} <br>
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
<style>
    /* Header background */
    .custom-requests-table thead th {
        position: sticky;
        top: 0;
        z-index: 2; 
        background-color: #373330;
        color: #ffffff;
    }

    /* Hover color for main rows */
    .custom-requests-table .request-row:hover {
        background-color: #b0e6ee !important;
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