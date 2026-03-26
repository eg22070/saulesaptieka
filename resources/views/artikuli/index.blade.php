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
        @php $role = strtolower(auth()->user()->role ?? ''); @endphp
        @if($role !== 'farmaceiti')
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#artikuliModal" id="addArtikulsBtn">Pievienot jaunu artikulu</button>
        @endif

       
                

        <!-- Artikuli Table -->
        <div id="artikuliResults">
            @include('partials.artikuli-table', ['products' => $products])
        </div>
    </div>
</x-app-layout>
<!-- Artikelus Modal -->
 @php $role = strtolower(auth()->user()->role ?? ''); @endphp
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
                <textarea class="form-control" id="snn" name="snn" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label for="analogs" class="form-label">Analogs</label>
                <input type="text" class="form-control" id="analogs" name="analogs">
            </div>
            <div class="mb-3">
                <label for="atzimes" class="form-label">Īpašās atzīmes</label>
                <textarea class="form-control" id="atzimes" name="atzimes" rows="3"></textarea>
            </div>
            @if($role === 'brivibas')
                <div class="mb-2">
                    <label>ATĶ</label>
                    <input type="text" name="atk" id="m_atk" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Receptes derīguma termiņš</label>
                    <select name="atk_validity_days" id="m_atk_validity_days" class="form-control">
                        <option value="90" selected>90 dienas</option>
                        <option value="365">1 gads</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Info</label>
                    <textarea name="info" id="m_info" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label>Pielietojums</label>
                    <textarea name="pielietojums" id="m_pielietojums" class="form-control" rows="2"></textarea>
                </div>
                <input type="hidden" name="hide_from_kruzes" value="0">
                <input type="hidden" name="hide_from_kruzes" value="0">
                <div class="mb-2 form-check">
                    <input type="checkbox" id="hide_from_kruzes" name="hide_from_kruzes" value="1" class="form-check-input">
                    <label for="hide_from_kruzes" class="form-check-label">Slēpt no Krūzes ielas</label>
                </div>
                <input type="hidden" name="hide_from_farmaceiti" value="0">
                <input type="hidden" name="hide_from_farmaceiti" value="0">
                <div class="mb-2 form-check">
                    <input type="checkbox" id="hide_from_farmaceiti" name="hide_from_farmaceiti" value="1" class="form-check-input">
                    <label for="hide_from_farmaceiti" class="form-check-label">Slēpt no farmaceitiem</label>
                </div>
            @endif
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

      const chkHideKruzes = document.getElementById('hide_from_kruzes');
      const chkHideFarm   = document.getElementById('hide_from_farmaceiti');
      if (chkHideKruzes) chkHideKruzes.checked = false;
      if (chkHideFarm)   chkHideFarm.checked   = false;

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

        const hideFromKruzes     = btn.dataset.hide_from_kruzes;
        const hideFromFarmaceiti = btn.dataset.hide_from_farmaceiti;

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

        // fill extra fields for brivibas if they exist
        // IDs in the modal are prefixed with "m_".
        const atkInput   = document.getElementById('m_atk');
        const infoInput  = document.getElementById('m_info');
        const pielInput  = document.getElementById('m_pielietojums');
        if (atkInput)  atkInput.value  = btn.dataset.atk  || '';
        if (infoInput) infoInput.value = btn.dataset.info || '';
        if (pielInput) pielInput.value = btn.dataset.pielietojums || '';

        const atkValidityInput = document.getElementById('m_atk_validity_days');
        if (atkValidityInput) {
            atkValidityInput.value = btn.dataset.atk_validity_days || '90';
        }

        // set checkboxes if present
        const chkHideKruzes = document.getElementById('hide_from_kruzes');
        const chkHideFarm   = document.getElementById('hide_from_farmaceiti');

        if (chkHideKruzes) {
            chkHideKruzes.checked = (hideFromKruzes === '1' || hideFromKruzes === 1);
        }
        if (chkHideFarm) {
            chkHideFarm.checked = (hideFromFarmaceiti === '1' || hideFromFarmaceiti === 1);
        }
        // Set modal title and button
        modalTitle.textContent = 'Labot artikulu';
        saveBtn.textContent    = 'Atjaunināt';
    });
  });
</script>