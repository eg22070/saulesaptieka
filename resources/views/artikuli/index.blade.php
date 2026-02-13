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
        <form action="{{ route('artikuli.index') }}" method="GET" class="mb-3 mt-3" id="searchForm">
            <div class="input-group mb-2">
                <input type="text"
                    id="searchInput"
                    name="search"
                    class="form-control"
                    placeholder="Meklēt pēc nosaukuma, id numura vai valsts"
                    value="{{ request('search') }}">
            </div>
            <div class="input-group">
                <input type="text"
                    id="snnInput"
                    name="snn"
                    class="form-control"
                    placeholder="Meklēt pēc SNN"
                    value="{{ request('snn') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>
            </div>
        </form>

        <!-- Add Artikuls Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#artikuliModal" id="addArtikulsBtn">Pievienot jaunu artikulu</button>

       
                

        <!-- Artikuli Table -->
        <div id="artikuliResults">
            @include('partials.artikuli-table', ['products' => $products])
        </div>
    </div>
</x-app-layout>
<!-- Artikelus Modal -->
<div class="modal fade" id="artikuliModal" tabindex="-1" aria-labelledby="artikuliModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="artikuliForm" method="POST" action="">
        @csrf
        @method('POST') <!-- Will be overridden for editing -->
        <input type="hidden" name="search" id="search_hidden" value="{{ request('search') }}">

        <div class="modal-header">
          <h5 class="modal-title" id="artikuliModalLabel">Pievienot/labot artikulu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Aizvērt"></button>
        </div>
         <div class="modal-body">
            <div class="mb-3">
                <label for="nosaukums" class="form-label">Nosaukums</label>
                <input type="text" class="form-control" id="nosaukums" name="nosaukums" required>
            </div>
            <div class="mb-3">
                <label for="id_numurs" class="form-label">ID numurs</label>
                <input type="text" class="form-control" id="id_numurs" name="id_numurs">
            </div>
            <div class="mb-3">
                <label for="valsts" class="form-label">Valsts</label>
                <input type="text" class="form-control" id="valsts" name="valsts" required>
            </div>
            <div class="mb-3">
                <label for="snn" class="form-label">SNN</label>
                <input type="text" class="form-control" id="snn" name="snn">
            </div>
            <div class="mb-3">
                <label for="analogs" class="form-label">Analogs</label>
                <input type="text" class="form-control" id="analogs" name="analogs">
            </div>
            <div class="mb-3">
                <label for="atzimes" class="form-label">Īpašās atzīmes</label>
                <textarea class="form-control" id="atzimes" name="atzimes" rows="3"></textarea>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
            <button type="submit" class="btn btn-primary" id="artikuliModalSaveBtn">Saglabāt</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('artikuliForm');
    const modalTitle = document.querySelector('#artikuliModalLabel');
    const saveBtn = document.getElementById('artikuliModalSaveBtn');

    const searchInput      = document.getElementById('searchInput');
    const snnInput         = document.getElementById('snnInput');
    const searchForm       = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('artikuliResults');
    const searchHidden     = document.getElementById('search_hidden');

    let debounceTimer;

    if (searchInput && snnInput && searchForm && resultsContainer) {
        const triggerLiveSearch = function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                fetchResults(searchInput.value, snnInput.value);
            }, 300);
        };

        searchInput.addEventListener('input', triggerLiveSearch);
        snnInput.addEventListener('input', triggerLiveSearch);

        function fetchResults(searchTerm, snnTerm) {
            const params = new URLSearchParams({
                search: searchTerm || '',
                snn: snnTerm || ''
            });
            const url = `${searchForm.action}?${params.toString()}`;

            window.history.replaceState({}, '', url);

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.text())
            .then(html => { resultsContainer.innerHTML = html; })
            .catch(err => console.error(err));
        }
    }
    // Handle "Add" button click
    document.getElementById('addArtikulsBtn').addEventListener('click', function () {
      form.reset();

      // Set form to store route
      form.action = "{{ route('artikuli.store') }}";

      // Remove method override if exists
      const methodInput = form.querySelector('input[name="_method"]');
      if (methodInput) methodInput.remove();

      // Set modal title and button text
      modalTitle.textContent = 'Pievienot jaunu artikulu';
      saveBtn.textContent = 'Saglabāt';

      if (searchHidden && searchInput) {
        searchHidden.value = searchInput.value;
    }
    });

    // Handle "Edit" buttons
    
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.edit-artikuls-btn');
        if (!btn) return;
        if (!form) return;

        const id        = btn.dataset.id;
        const nosaukums = btn.dataset.nosaukums;
        const id_numurs = btn.dataset.id_numurs;
        const valsts    = btn.dataset.valsts;
        const snn       = btn.dataset.snn;
        const analogs   = btn.dataset.analogs;
        const atzimes   = btn.dataset.atzimes;

        form.action = "/artikuli/" + id;

        let methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            form.appendChild(methodInput);
        }
        methodInput.value = 'PUT';

        // Fill form fields
        document.getElementById('nosaukums').value = nosaukums;
        document.getElementById('id_numurs').value = id_numurs;
        document.getElementById('valsts').value   = valsts;
        document.getElementById('snn').value      = snn;
        document.getElementById('analogs').value  = analogs;
        document.getElementById('atzimes').value  = atzimes;

        // Set modal title and button
        modalTitle.textContent = 'Labot artikulu';
        saveBtn.textContent    = 'Atjaunināt';
    });
  });
</script>