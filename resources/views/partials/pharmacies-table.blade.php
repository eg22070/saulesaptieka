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
                            <div class="d-flex gap-2"> <!-- Using flexbox with a small gap -->
                              <button class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#pharmacyModal"
                              data-id="{{ $pharmacy->id }}"
                              data-nosk="{{ $pharmacy->nosaukums }}"
                              data-adrese="{{ $pharmacy->adrese }}">Labot</button>
                              
                              <form action="{{ route('pharmacies.destroy', $pharmacy->id) }}" method="POST">
                                  @csrf
                                  @method('DELETE')
                                  <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Vai tiešām vēlaties izdzēst šo aptieku?')">Dzēst</button>
                              </form>
                          </div>
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
</style>