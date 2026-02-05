<table class="table custom-requests-table">
    <thead>
        <tr>
            <th style="width: 3%;  border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
            <th style="width: 8%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Datums</th>
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
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                    {{ $art->created_at->format('d/m/Y') }}
                </td>
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
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->aizliegums }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->statuss }}</td>
                <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->iepircejs }}</td>
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
</style>