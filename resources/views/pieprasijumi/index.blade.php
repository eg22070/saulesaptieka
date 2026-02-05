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
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Aizvērt"></button>
            </div>
        @endif
    </x-slot>

    <div class="container" style="width: 90%; max-width: 2200px; margin: 0 auto;">
        <div class="search-filter-sticky">
        <!-- Search -->
        <form method="GET" action="{{ route('pieprasijumi.index') }}" class="mb-3 mt-3" id="searchForm">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Meklēt pēc aptiekas, artikula vai iepircēja" value="{{ request('search') }}" id="searchInput">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>
            </div>
        </form>
        <!-- Filter by completion status -->

    <div class="mb-3">
        <form method="GET" action="{{ route('pieprasijumi.index') }}" id="filtersForm">
            {{-- Status filter --}}
            <div class="form-check form-check-inline">
                <label class="form-label me-2 mr-3" for="status_filter">Statuss:</label>
                <select name="status_filter" id="status_filter" class="form-select form-select-sm">
                    <option value="" {{ request('status_filter') == '' ? 'selected' : '' }}>Visi</option>
                    <option value="completed"  {{ request('status_filter') == 'completed'  ? 'selected' : '' }}>Pabeigtie</option>
                    <option value="incomplete" {{ request('status_filter') == 'incomplete' ? 'selected' : '' }}>Nepabeigtie</option>
                </select>
            </div>

            {{-- Pharmacy filter --}}
            <div class="form-check form-check-inline ms-4">
                <label class="form-label me-2 mr-3" for="pharmacy_filter">Aptieka:</label>
                <select name="pharmacy_filter" id="pharmacy_filter" class="form-select form-select-sm">
                    <option value="" {{ request('pharmacy_filter') == '' ? 'selected' : '' }}>Visas aptiekas</option>
                    <option value="saule10" {{ request('pharmacy_filter') == 'saule10' ? 'selected' : '' }}>
                        Saule-10 (SIA Saules aptieka)
                    </option>
                </select>
            </div>

            {{-- Buyer filter --}}
            <div class="form-check form-check-inline ms-4">
                <label class="form-label me-2 mr-3" for="buyer_filter">Iepircējs:</label>
                <select name="buyer_filter" id="buyer_filter" class="form-select form-select-sm">
                    <option value="" {{ request('buyer_filter') == '' ? 'selected' : '' }}>Visi iepircēji</option>
                    <option value="Artūrs" {{ request('buyer_filter') == 'Artūrs' ? 'selected' : '' }}>Artūrs</option>
                    <option value="Liene"  {{ request('buyer_filter') == 'Liene'  ? 'selected' : '' }}>Liene</option>
                    <option value="Anna"   {{ request('buyer_filter') == 'Anna'   ? 'selected' : '' }}>Anna</option>
                    <option value="Iveta"  {{ request('buyer_filter') == 'Iveta'  ? 'selected' : '' }}>Iveta</option>
                </select>
            </div>
        </form>
    </div>
    </div>
        <!-- Add Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#requestModal" id="addRequestBtn">Pievienot jaunu pieprasījumu</button>

        

        <!-- Table -->
        <div id="searchResults">
            @include('partials.pieprasijumi-table', ['pieprasijumi' => $pieprasijumi])
        </div>
    </div>

<!-- Pieprasījumi Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 40%;">
    <div class="modal-content">
      <form id="requestForm" method="POST" action="">
        @csrf
        @method('POST') <!-- Will be overridden for edit -->

        <div class="modal-header">
        <h5 class="modal-title" id="requestModalLabel">Pievienot/labot pieprasījumu</h5>
        <div class="d-flex align-items-center">
            <div class="me-3" id="izpilditContainer" style="display: none;">
            <button type="button" class="btn btn-success" id="izpilditBtn">Izpildīt</button>
            </div>
            <div class="me-3" id="undoContainer" style="display: none;">
            <button type="button" class="btn btn-warning" id="undoBtn">Atcelt izpildi</button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Aizvērt"></button>
        </div>
        </div>

        <div class="modal-body">
            <div class="mb-2">
                <label for="datums" class="form-label mr-3">Datums</label>
                <input type="text" id="datums" name="datums" placeholder="DD/MM/YYYY" required>
            </div>
            <div class="mb-2 d-flex align-items-center">
                <label for="aptiekas_name" class="form-label mr-3">Aptieka</label>
                <!-- Input for user's visible selection -->
                <input type="text" 
                    class="form-control" 
                    placeholder="Rakstiet aptiekas nosaukumu" 
                    required 
                    list="aptieki" 
                    id="aptiekas_name" />
                <!-- Hidden ID for form submission -->
                <input type="hidden" id="aptiekas_id" name="aptiekas_id">
                <datalist id="aptieki">
                    @foreach($aptiekas as $aptieka)
                        <option value="{{ $aptieka->nosaukums }}" data-id="{{ $aptieka->id }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="mb-2 d-flex align-items-center">
                <label for="artikula_name" class="form-label mr-3">Artikuls</label>
                <!-- Input for user's visible selection -->
                <input type="text" 
                    class="form-control" 
                    placeholder="Rakstiet artikula nosaukumu" 
                    required 
                    list="artikuli" 
                    id="artikula_name" />
                <!-- Hidden ID for form submission -->
                <input type="hidden" id="artikula_id" name="artikula_id">
                <datalist id="artikuli">
                    @foreach($artikuli as $artikuls)
                        <option value="{{ $artikuls->nosaukums }}" data-id="{{ $artikuls->id }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="mb-2 d-flex align-items-center">
                <label for="daudzums" class="form-label mr-3" style="white-space: nowrap;">Daudzums</label>
                <input type="number" class="form-control" id="daudzums" name="daudzums" style="width: 100px;" required>
            </div>
            <div class="mb-2 d-flex align-items-center">
                <label for="aizliegums" class="form-label mr-3">Aizliegums</label>
                <select class="form-control" id="aizliegums" name="aizliegums" style="width: 200px;">
                    <option value="Drīkst aizvietot">Drīkst aizvietot</option>
                    <option value="Nedrīkst aizvietot">Nedrīkst aizvietot</option>
                    <option value="NVD">NVD</option>
                    <option value="Stacionārs">Stacionārs</option>
                </select>
            </div>
            <div id="additionalFields" style="display:none;">
            <div class="mb-2 d-flex align-items-center">
                <label for="izrakstitais_daudzums" class="form-label mr-3" style="white-space: nowrap;">Izrakstītais daudzums</label>
                <input type="number" class="form-control" id="izrakstitais_daudzums" name="izrakstitais_daudzums" style="width: 100px;">
            </div>

            <div class="mb-2 d-flex align-items-center">
                <label for="statuss" class="form-label mr-3">Statuss</label>
                <select class="form-control" id="statuss" name="statuss" style="width: 200px;">
                    <option value>Izvēlieties statusu</option>
                    <option value="Pasūtīts">Pasūtīts</option>
                    <option value="Atcelts">Atcelts</option>
                    <option value="Mainīta piegāde">Mainīta piegāde</option>
                    <option value="Ir noliktavā">Ir noliktavā</option>
                    <option value="Daļēji atlikumā">Daļēji atlikumā</option>
                </select>
            </div>
            
            <div class="mb-2 d-flex align-items-center">
                <label for="iepircejs" class="form-label mr-3">Iepircējs</label>
                <select class="form-control" id="iepircejs" name="iepircejs" style="width: 200px;">
                    <option value>Izvēlieties iepircēju</option>
                    <option value="Artūrs">Artūrs</option>
                    <option value="Liene">Liene</option>
                    <option value="Anna">Anna</option>
                    <option value="Iveta">Iveta</option>
                </select>
            </div>
            <div class="mb-2 d-flex align-items-center">
                <label for="piegades_datums" class="form-label mr-3" style="white-space: nowrap;">Piegādes datums</label>
                <textarea class="form-control" id="piegades_datums" name="piegades_datums" rows="1"></textarea>
            </div>
            <div class="mb-1 d-flex align-items-center">
                <label for="piezimes" class="form-label mr-3">Piezīmes</label>
                <textarea class="form-control" id="piezimes" name="piezimes" rows="1"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
            <button type="submit" class="btn btn-primary" id="requestModalSaveBtn">Saglabāt</button>
        </div>
      </form>
    </div>
    </div>
  </div>
</div>
</x-app-layout>

<style>
    .toggle-details:hover {
        color: #0d6efd; /* Bootstrap primary blue */
    }

</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('requestForm');
    const modalTitle = document.getElementById('requestModalLabel');
    const saveBtn = document.getElementById('requestModalSaveBtn');
    const izpilditBtn = document.getElementById('izpilditBtn');
    const undoBtn = document.getElementById('undoBtn');

    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('searchResults');

    let debounceTimer;
    // Inputs
    const aptiekasNameInput = document.getElementById('aptiekas_name');
    const aptiekasIdInput = document.getElementById('aptiekas_id');
    const artikulaNameInput = document.getElementById('artikula_name');
    const artikulaIdInput = document.getElementById('artikula_id');
    const additionalFields = document.getElementById('additionalFields');
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            const searchTerm = searchInput.value;
            fetchResults(searchTerm);
        }, 300); // Wait for 300ms after the user stops typing
    });

    function fetchResults(searchTerm) {
        const url = `${searchForm.action}?search=${encodeURIComponent(searchTerm)}`;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            resultsContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    // Initialize Date Picker
    flatpickr("#datums", {
        dateFormat: "d/m/Y" 
    });

    // Helper: Convert Carbon format (YYYY-MM-DD...) to Flatpickr format (DD/MM/YYYY)
    function formatDateForInput(dateStr) {
        if (!dateStr) return '';
        // If it comes as YYYY-MM-DD HH:MM:SS or just YYYY-MM-DD
        const datePart = dateStr.split(' ')[0]; 
        const [year, month, day] = datePart.split('-');
        if(year && month && day) {
            return `${day}/${month}/${year}`;
        }
        return dateStr; // Return original if parsing fails
    }

    // Auto-fill Hidden ID for Aptieka
    aptiekasNameInput.addEventListener('input', function() {
        const value = this.value;
        let found = false;
        Array.from(document.getElementById('aptieki').options).forEach(option => {
            if (option.value === value) {
                aptiekasIdInput.value = option.getAttribute('data-id');
                found = true;
            }
        });
        if(!found) aptiekasIdInput.value = ''; // Clear ID if text doesn't match list
    });

    // Auto-fill Hidden ID for Artikuls
    artikulaNameInput.addEventListener('input', function() {
        const value = this.value;
        let found = false;
        Array.from(document.getElementById('artikuli').options).forEach(option => {
            if (option.value === value) {
                artikulaIdInput.value = option.getAttribute('data-id');
                found = true;
            }
        });
        if(!found) artikulaIdInput.value = ''; // Clear ID if text doesn't match list
    });

    // ---------------------------------------------------------
    // "ADD" BUTTON LOGIC
    // ---------------------------------------------------------
    document.getElementById('addRequestBtn').addEventListener('click', function () {
      form.reset();
      form.action = "{{ route('pieprasijumi.store') }}"; 
      
      // Remove _method input if it exists (forcing standard POST)
      const methodInput = form.querySelector('input[name="_method"]');
      if (methodInput) {
        methodInput.remove();
      }

      // Set today's date
      const today = new Date();
      const dd = String(today.getDate()).padStart(2, '0');
      const mm = String(today.getMonth() + 1).padStart(2, '0');
      const yyyy = today.getFullYear();
      document.getElementById('datums').value = dd + '/' + mm + '/' + yyyy;

      // UI Changes
      modalTitle.textContent = 'Pievienot jaunu pieprasījumu';
      saveBtn.textContent = 'Saglabāt';
      additionalFields.style.display = 'none';
    });

    // ---------------------------------------------------------
    // "EDIT" BUTTON LOGIC
    // ---------------------------------------------------------
    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.edit-request-btn');
        if (!btn) return;           // click is not on an edit button
        if (!form) return;          // safety

        const data = btn.dataset;

        // Set form action
        form.action = "/pieprasijumi/" + data.id;

        // Populate Hidden IDs
        aptiekasIdInput.value = data.aptiekas_id;
        artikulaIdInput.value = data.artikula_id;

        // Populate Visible Text Inputs
        aptiekasNameInput.value  = data.aptiekas_nosaukums;
        artikulaNameInput.value  = data.artikula_nosaukums;

        // Ensure _method="PUT" exists
        let methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            form.appendChild(methodInput);
        }
        methodInput.value = 'PUT';

        // Populate Form Fields
        document.getElementById('datums').value                = formatDateForInput(data.datums);
        document.getElementById('daudzums').value              = data.daudzums;
        document.getElementById('izrakstitais_daudzums').value = data.izrakstitais_daudzums;
        document.getElementById('statuss').value               = data.statuss;
        document.getElementById('aizliegums').value            = data.aizliegums;
        document.getElementById('iepircejs').value             = data.iepircejs;
        document.getElementById('piegades_datums').value       = data.piegades_datums;
        document.getElementById('piezimes').value              = data.piezimes;

        // Handle Completed/Uncompleted Checkboxes
        if (data.completed === '1') {
            document.getElementById('izpilditContainer').style.display = 'none';
            document.getElementById('undoContainer').style.display = 'block';
        } else {
            document.getElementById('izpilditContainer').style.display = 'block';
            document.getElementById('undoContainer').style.display = 'none';
        }

        // UI Changes
        additionalFields.style.display = 'block';
        modalTitle.textContent = 'Labot pieprasījumu';
        saveBtn.textContent = 'Atjaunināt';
      });

    // ---------------------------------------------------------
    // ROW EXPANSION LOGIC (Red/Green Circle Click)
    // ---------------------------------------------------------
    document.addEventListener('click', function (event) {
        const indicator = event.target.closest('.toggle-details');
        if (!indicator) return;

        event.stopPropagation();

        const mainRow = indicator.closest('tr');
        const additionalRow = mainRow.nextElementSibling;

        if (additionalRow && additionalRow.classList.contains('additional-info')) {
            additionalRow.style.display =
                additionalRow.style.display === 'none' || additionalRow.style.display === ''
                    ? 'table-row'
                    : 'none';
        }
    });
    // ---------------------------------------------------------
    // FILTER RADIO BUTTONS
    // ---------------------------------------------------------
    const filtersForm = document.getElementById('filtersForm');

    document.getElementById('status_filter').addEventListener('change', function () {
        filtersForm.submit();
    });

    document.getElementById('pharmacy_filter').addEventListener('change', function () {
        filtersForm.submit();
    });

    document.getElementById('buyer_filter').addEventListener('change', function () {
        filtersForm.submit();
    });
  });
  izpilditBtn.addEventListener('click', function() {
            const form = document.getElementById('requestForm');
            const completedInput = document.createElement('input');
            completedInput.type = 'hidden';
            completedInput.name = 'completed';
            completedInput.value = '1';
            form.appendChild(completedInput);
            form.submit();
    });
    undoBtn.addEventListener('click', function() {

            const form = document.getElementById('requestForm');
            const completedInput = document.createElement('input');
            completedInput.type = 'hidden';
            completedInput.name = 'completed';
            completedInput.value = '0';
            form.appendChild(completedInput);
            form.submit();
    });
</script>