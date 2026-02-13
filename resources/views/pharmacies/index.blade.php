<x-app-layout>
    <x-slot name="header">
        @if ($errors->any())
            <div class="alert alert-danger" style="margin: 20px;">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </x-slot>

    <div class="container" style="width: 90%; max-width: 2200px; margin: 0 auto;">
        <!-- Search Bar -->
        <form action="{{ route('pharmacies.index') }}" method="GET" class="mb-3 mt-3" id="pharmacySearchForm">
            <div class="input-group">
                <input type="text"
                       name="search"
                       id="pharmacySearchInput"
                       class="form-control"
                       placeholder="Meklēt pēc aptiekas nosaukuma vai adreses"
                       value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>
            </div>
        </form>

        <!-- Add Pharmacy Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#pharmacyModal" id="addPharmacyBtn">Pievienot jaunu aptieku</button>
        
        <div id="pharmaciesResults">
            @include('partials.pharmacies-table', ['pharmacies' => $pharmacies])
        </div>
    </div>
</x-app-layout>

<!-- Add Pharmacy Modal -->
<div class="modal fade" id="pharmacyModal" tabindex="-1" aria-labelledby="pharmacyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="pharmacyForm" method="POST" action="">
        @csrf
        @method('POST') <!-- This will be changed dynamically for edit -->
        <input type="hidden" name="search" id="search_hidden" value="{{ request('search') }}">

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

    const searchInput      = document.getElementById('pharmacySearchInput');
    const searchHidden = document.getElementById('search_hidden');
    const searchForm       = document.getElementById('pharmacySearchForm');
    const resultsContainer = document.getElementById('pharmaciesResults');

    let debounceTimer;
    
    if (searchInput && searchForm && resultsContainer) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                const searchTerm = searchInput.value;
                fetchResults(searchTerm);
            }, 300);
        });

        function fetchResults(searchTerm) {
          const params = new URLSearchParams({ search: searchTerm });
          const url = `${searchForm.action}?${params.toString()}`;

          // update URL so pagination/redirects know about it
          window.history.replaceState({}, '', url);

          fetch(url, {
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(response => response.text())
          .then(html => {
              resultsContainer.innerHTML = html;
          })
          .catch(error => console.error('Error fetching pharmacies:', error));
      }
    }
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

      if (searchHidden && searchInput) {
          searchHidden.value = searchInput.value;
      }
    });

    // Handle "Edit" buttons
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.edit-btn');
      if (!btn) return;

      const id        = btn.getAttribute('data-id');
      const nosaukums = btn.getAttribute('data-nosk');
      const adrese    = btn.getAttribute('data-adrese');

      // Set form action to the update route
      form.action = "/pharmacies/" + id;

      // Ensure method input exists and set to PUT
      let methodInput = form.querySelector('input[name="_method"]');
      if (!methodInput) {
          methodInput = document.createElement('input');
          methodInput.type = 'hidden';
          methodInput.name = '_method';
          form.appendChild(methodInput);
      }
      methodInput.value = 'PUT';

      // Fill form fields with current data
      form.querySelector('#nosaukums').value = nosaukums;
      form.querySelector('#adrese').value = adrese;

      // Update modal title and button text for editing
      modal.querySelector('.modal-title').textContent = 'Edit Pharmacy';
      saveBtn.textContent = 'Update';
  });
  });
</script>