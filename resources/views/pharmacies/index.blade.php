<x-app-layout>
    <x-slot name="header">
        <h1>Aptiekas</h1>
    </x-slot>

    <div class="container" style="width: 80%;">
        <!-- Search Bar -->
        <form action="{{ route('pharmacies.index') }}" method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Meklēt pēc aptiekas nosaukuma vai adreses" value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>
            </div>
        </form>

        <!-- Add Pharmacy Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#pharmacyModal" id="addPharmacyBtn">Pievienot jaunu aptieku</button>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <!-- Pharmacies Table -->
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
                            <button class="btn btn-sm btn-primary edit-btn" data-bs-toggle="modal" data-bs-target="#pharmacyModal"
                            data-id="{{ $pharmacy->id }}"
                            data-nosk=" {{ $pharmacy->nosaukums }}"
                            data-adrese="{{ $pharmacy->adrese }}">Labot</button>
                            <form action="{{ route('pharmacies.destroy', $pharmacy->id) }}" method="POST" style="display: inline;">
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
    </div>
</x-app-layout>

<!-- Add Pharmacy Modal -->
<div class="modal fade" id="pharmacyModal" tabindex="-1" aria-labelledby="pharmacyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="pharmacyForm" method="POST" action="">
        @csrf
        @method('POST') <!-- This will be changed dynamically for edit -->

        <div class="modal-header">
          <h5 class="modal-title" id="pharmacyModalLabel">Pievienot/labot aptieku</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="nosaukums" class="form-label">Aptiekas nosaukums</label>
            <input type="text" class="form-control" id="nosaukums" name="nosaukums" required>
          </div>
          <div class="mb-3">
            <label for="adrese" class="form-label">Adrese</label>
            <textarea class="form-control" id="adrese" name="adrese" rows="3" required></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
          <button type="submit" class="btn btn-primary" id="modalSaveBtn">Saglabāt</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('pharmacyModal');
    var form = document.getElementById('pharmacyForm');
    var modalTitle = document.querySelector('.modal-title');
    var saveBtn = document.getElementById('modalSaveBtn');

    // Handle "Add Pharmacy" button click
    document.getElementById('addPharmacyBtn').addEventListener('click', function () {
      form.reset();

      // Set form action to the store route
      form.action = "{{ route('pharmacies.store') }}";

      // Reset method input (ensure POST)
      if (form.querySelector('input[name="_method"]')) {
        form.querySelector('input[name="_method"]').remove();
      }

      // Update modal title and button text
      modal.querySelector('.modal-title').textContent = 'Add Pharmacy';
      saveBtn.textContent = 'Add';

      // If your form has any other setup, do here
    });

    // Handle "Edit" buttons
    document.querySelectorAll('.edit-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = this.getAttribute('data-id');
        var nosaukums = this.getAttribute('data-nosk');
        var adrese = this.getAttribute('data-adrese');

        // Set form action to the update route
        form.action = "/pharmacies/" + id; 

        // Ensure method input exists and set to PUT
        if (!form.querySelector('input[name="_method"]')) {
          var methodInput = document.createElement('input');
          methodInput.type = 'hidden';
          methodInput.name = '_method';
          methodInput.value = 'PUT';
          form.appendChild(methodInput);
        } else {
          form.querySelector('input[name="_method"]').value = 'PUT';
        }

        // Fill form fields with current data
        form.querySelector('#nosaukums').value = nosaukums;
        form.querySelector('#adrese').value = adrese;

        // Update modal title and button text for editing
        modal.querySelector('.modal-title').textContent = 'Edit Pharmacy';
        saveBtn.textContent = 'Update';
      });
    });
  });
</script>