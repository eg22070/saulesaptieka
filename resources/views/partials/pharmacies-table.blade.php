        {{ $pharmacies->links() }}
<table class="table custom-pharmacies-table">
            <thead>
                <tr>
                    <th style="border: 1px solid #080000ff; padding: 4px; text-align: center;">Aptieka</th>
                    <th style="border: 1px solid #080000ff; padding: 4px; text-align: center;">Adrese</th>
                    <th style="border: 1px solid #080000ff; padding: 4px; text-align: center;">Darbības</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pharmacies as $pharmacy)
                    <tr class="pharmacies-row" style="background-color: {{ $loop->odd ? '#ffffff' : '#f0f0f0' }};">
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center; vertical-align: middle;">{{ $pharmacy->nosaukums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center; vertical-align: middle;">{{ $pharmacy->adrese }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                              <button class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#pharmacyModal"
                              data-id="{{ $pharmacy->id }}"
                              data-nosk="{{ $pharmacy->nosaukums }}"
                              data-adrese="{{ $pharmacy->adrese }}">Labot</button>
                              
                              <form action="{{ route('pharmacies.destroy', $pharmacy->id) }}" method="POST">
                                  @csrf
                                  @method('DELETE')
                                  <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Vai tiešām vēlaties izdzēst šo aptieku?')">Dzēst</button>
                              </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Netika atrastas aptiekas!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        {{ $pharmacies->links() }}
<style>
    .custom-pharmacies-table thead th {
        position: sticky;
        top: 0;
        z-index: 2; 
        background-color: #373330;
        color: #ffffff;
    }

    /* Hover color for main rows */
    .custom-pharmacies-table .pharmacies-row:hover {
        background-color: #b0e6ee !important;
    }
        /* Soft pastel buttons for table actions */
.custom-pharmacies-table .btn-sm.btn-primary {
  background-color: #a8d0ff;   /* pastel blue */
  color: #0b3a66;
  border: 1px solid rgba(11,58,102,0.12);
  box-shadow: none;
}

.custom-pharmacies-table .btn-sm.btn-primary:hover,
.custom-pharmacies-table .btn-sm.btn-primary:focus {
  background-color: #8cc0ff;   /* slightly stronger on hover */
  color: #04283f;
}

/* Soft pastel danger (delete) */
.custom-pharmacies-table .btn-sm.btn-danger {
  background-color: #ffb3b3;   /* pastel red/pink */
  color: #6a0f0f;
  border: 1px solid rgba(170,10,10,0.12);
  box-shadow: none;
}

.custom-pharmacies-table .btn-sm.btn-danger:hover,
.custom-pharmacies-table .btn-sm.btn-danger:focus {
  background-color: #ff9999;
  color: #540b0b;
}

/* Optional: slightly rounder corners */
.custom-pharmacies-table .btn-sm {
  border-radius: 6px;
}

/* Softer focus outline */
.custom-pharmacies-table .btn:focus {
  box-shadow: 0 0 0 0.12rem rgba(0,0,0,0.06) !important;
  outline: none !important;
}
</style>