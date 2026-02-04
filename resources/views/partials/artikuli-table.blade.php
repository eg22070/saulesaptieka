<table class="table table-striped">
            <thead>
                <tr>
                    <th>Nosaukums</th>
                    <th>Id numurs</th>
                    <th>Valsts</th>
                    <th>SNN</th>
                    <th>Analogs</th>
                    <th>Īpašās atzīmes</th>
                    <th>Darbības</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $artikuls)
                    <tr>
                        <td>{{ $artikuls->nosaukums }}</td>
                        <td>{{ $artikuls->id_numurs }}</td>
                        <td>{{ $artikuls->valsts }}</td>
                        <td>{{ $artikuls->snn }}</td>
                        <td>{{ $artikuls->analogs }}</td>
                        <td>{{ $artikuls->atzimes }}</td>
                        <td>
                            <div class="d-flex gap-2"> <!-- Using flexbox with a small gap -->
                                <button class="btn btn-sm btn-primary edit-artikuls-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#artikuliModal"
                                        data-id="{{ $artikuls->id }}"
                                        data-nosaukums="{{ $artikuls->nosaukums }}"
                                        data-id_numurs="{{ $artikuls->id_numurs }}"
                                        data-valsts="{{ $artikuls->valsts }}"
                                        data-snn="{{ $artikuls->snn }}"
                                        data-analogs="{{ $artikuls->analogs }}"
                                        data-atzimes="{{ $artikuls->atzimes }}"
                                >Labot</button>

                                <form action="{{ route('artikuli.destroy', $artikuls->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Vai tiešām vēlaties izdzēst šo artikulu?')">Dzēst</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Netika atrasti artikuli!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $products->links() }}