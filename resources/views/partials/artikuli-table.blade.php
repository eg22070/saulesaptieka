        {{ $products->links() }}
<table class="table custom-artikuli-table">
            <thead>
                <tr>
                    <th style="width: 25%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
                    <th style="width: 10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ID numurs</th>
                    <th style="width: 5%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Valsts</th>
                    <th style="width: 25%; border: 1px solid #080000ff; padding: 4px; text-align: center;">SNN</th>
                    <th style="width: 14%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Analogs</th>
                    <th style="width: 15%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Īpašās atzīmes</th>
                    <th style="width: 6%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Darbības</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $artikuls)
                    <tr class="artikuli-row" style="background-color: {{ $loop->odd ? '#ffffff' : '#f0f0f0' }};">
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->nosaukums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->id_numurs }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->valsts }}</td>
                        <td class="snn-cell" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">
                            {!! nl2br(e($artikuls->snn)) !!}
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->analogs }}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->atzimes }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
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
<style>
    .custom-artikuli-table thead th {
        position: sticky;
        top: 0;
        z-index: 2; 
        background-color: #373330;
        color: #ffffff;
    }

    /* Hover color for main rows */
    .custom-artikuli-table .artikuli-row:hover {
        background-color: #b0e6ee !important;
    }
    .snn-cell {
        font-size: 0.9rem; /* or 12px, 14px, etc. */
    }
    .ipasas {
        font-size: 0.9rem; /* or 12px, 14px, etc. */
    }
    /* Soft pastel buttons for table actions */
.custom-artikuli-table .btn-sm.btn-primary {
  background-color: #a8d0ff;   /* pastel blue */
  color: #0b3a66;
  border: 1px solid rgba(11,58,102,0.12);
  box-shadow: none;
}

.custom-artikuli-table .btn-sm.btn-primary:hover,
.custom-artikuli-table .btn-sm.btn-primary:focus {
  background-color: #8cc0ff;   /* slightly stronger on hover */
  color: #04283f;
}

/* Soft pastel danger (delete) */
.custom-artikuli-table .btn-sm.btn-danger {
  background-color: #ffb3b3;   /* pastel red/pink */
  color: #6a0f0f;
  border: 1px solid rgba(170,10,10,0.12);
  box-shadow: none;
}

.custom-artikuli-table .btn-sm.btn-danger:hover,
.custom-artikuli-table .btn-sm.btn-danger:focus {
  background-color: #ff9999;
  color: #540b0b;
}

/* Optional: slightly rounder corners */
.custom-artikuli-table .btn-sm {
  border-radius: 6px;
}

/* Softer focus outline */
.custom-artikuli-table .btn:focus {
  box-shadow: 0 0 0 0.12rem rgba(0,0,0,0.06) !important;
  outline: none !important;
}

</style>