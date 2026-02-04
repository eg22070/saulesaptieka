<table class="table table-striped">
            <thead>
                <tr>
                    <th>Aptieka</th>
                    <th>Adrese</th>
                    <th>Darbības</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pharmacies as $pharmacy)
                    <tr>
                        <td>{{ $pharmacy->nosaukums }}</td>
                        <td>{{ $pharmacy->adrese }}</td>
                        <td>
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