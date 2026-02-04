<table class="table table-striped">
    <thead>
                <tr>
                    <th style="width: 3%; border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
                    <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Datums</th>
                    <th style="width: 12%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aptieka</th>
                    <th style="width: 5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Valsts</th>
                    <th style="width: 10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ID numurs</th>
                    <th style="width: 27%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
                    <th style="width: 7%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Daudzums</th>
                    <th style="width: 9%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Izraks. daudz.</th>
                    <th style="width: 7%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Statuss</th>
                    <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aizliegums</th>
                    <th style="width: 6%; border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pieprasijumi as $art)
                    <!-- Main Row -->
                    <tr>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center; vertical-align: middle;">
                            @if($art->completed)
                                <span style="font-size: 1.2rem;">✅</span>
                                <!-- You can also use a Bootstrap icon if installed: <i class="bi bi-hand-thumbs-up-fill text-success"></i> -->
                            @endif
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->datums->format('d/m/Y') }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->aptiekas->nosaukums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->valsts }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->id_numurs }}</td>
                        <td class="toggle-details" style="border: 1px solid #080000ff; padding: 4px; cursor: pointer;" title="Klikšķiniet, lai redzētu detaļas">
                            <b>{{ $art->artikuli->nosaukums }}</b>
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->daudzums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->izrakstitais_daudzums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->statuss }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->aizliegums }}</td>
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
                        <td colspan="11" style="background-color: #f8f9fa; border: 1px solid #080000ff;">
                            <div style="padding: 10px;">
                                <strong>Paziņojuma datums:</strong> {{ $art->pazinojuma_datums }} <br>
                                <strong>Iepircējs:</strong> {{ $art->iepircejs }} <br>
                                <strong>Piegādes datums:</strong> {{ $art->piegades_datums }} <br>
                                <strong>Piezīmes:</strong> {{ $art->piezimes }} <br>
                                <strong>Izpildīja:</strong> 
                                @if($art->completer)
                                    {{ $art->completer->name }} ({{ $art->completed_at ? $art->completed_at->format('d/m/Y H:i') : '' }})
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
