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
        <!-- Search -->
        <form method="GET" action="{{ route('pieprasijumi.index') }}" class="mb-3 mt-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Meklēt pēc aptiekas, artikula vai iepircēja" value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>
            </div>
        </form>
        <!-- Filter by completion status -->

    <div class="mb-3">
        <form method="GET" action="{{ route('pieprasijumi.index') }}">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status_filter" id="all" value="" {{ request('status_filter') ? '' : 'checked' }}>
                <label class="form-check-label" for="all">Visi</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status_filter" id="completed" value="completed" {{ request('status_filter') == 'completed' ? 'checked' : '' }}>
                <label class="form-check-label" for="completed">Pabeigtie</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status_filter" id="incomplete" value="incomplete" {{ request('status_filter') == 'incomplete' ? 'checked' : '' }}>
                <label class="form-check-label" for="incomplete">Nepabeigtie</label>
            </div>
        </form>
    </div>
        <!-- Add Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#requestModal" id="addRequestBtn">Pievienot jaunu pieprasījumu</button>

        

        <!-- Table -->
        <table class="table table-striped" style="table-layout: fixed; border-collapse: collapse; width: 100%; overflow-wrap: break-word;">
            <thead>
                <tr>
                    <th style="width: 3%; border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
                    <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Datums</th>
                    <th style="width: 12%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aptieka</th>
                    <th style="width: 5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Valsts</th>
                    <th style="width: 10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ID numurs</th>
                    <th style="width: 27%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
                    <th style="width: 7%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Daudzums</th>
                    <th style="width: 9%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Izraks. daudz.</th>
                    <th style="width: 7%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Statuss</th>
                    <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aizliegums</th>
                    <th style="width: 6%; border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pieprasijumi as $art)
                    <!-- Main Row -->
                    <tr>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                            <!-- Status Indicator (Now Clickable) -->
                            <span class="status-indicator toggle-details" 
                            style="background-color: {{ $art->completed ? 'green' : 'red' }};" 
                            title="Klikšķiniet, lai redzētu detaļas"></span>
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->datums->format('d/m/Y') }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->aptiekas->nosaukums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->valsts }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->id_numurs }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;"><b>{{ $art->artikuli->nosaukums }}</b></td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->daudzums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->izrakstitais_daudzums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->statuss }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->aizliegums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-primary edit-request-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#requestModal"
                                    data-id="{{ $art->id }}"
                                    data-datums="{{ $art->datums }}"
                                    data-aptiekas_id="{{ $art->aptiekas->id }}"
                                    data-aptiekas_nosaukums="{{ $art->aptiekas->nosaukums }}" 
                                    data-artikula_id="{{ $art->artikuli->id }}"
                                    data-artikula_nosaukums="{{ $art->artikuli->nosaukums }}" 
                                    data-daudzums="{{ $art->daudzums }}"
                                    data-izrakstitais_daudzums="{{ $art->izrakstitais_daudzums }}"
                                    data-pazinojuma_datums="{{ $art->pazinojuma_datums }}"
                                    data-statuss="{{ $art->statuss }}"
                                    data-aizliegums="{{ $art->aizliegums }}"
                                    data-iepircejs="{{ $art->iepircejs }}"
                                    data-piegades_datums="{{ $art->piegades_datums }}"
                                    data-piezimes="{{ $art->piezimes }}"
                                    data-completed="{{ $art->completed ? '1' : '0' }}"
                                    data-edit-mode="true">
                                    Labot</button>

                            <!-- Delete Form -->
                            <form action="{{ route('pieprasijumi.destroy', $art->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Vai tiešām vēlaties dzēst šo pieprasījumu?')">Dzēst</button>
                            </form>
                        </td>
                    </tr>
                    
                    <!-- Additional Info Row (Hidden by default) -->
                    <tr class="additional-info" style="display:none;">
                        <td colspan="11" style="background-color: #f8f9fa; border: 1px solid #080000ff;">
                            <div style="padding: 10px;">
                                <strong>Paziņojuma datums:</strong> {{ $art->pazinojuma_datums }} <br>
                                <strong>Iepircējs:</strong> {{ $art->iepircejs }} <br>
                                <strong>Piegādes datums:</strong> {{ $art->piegades_datums }} <br>
                                <strong>Piezīmes:</strong> {{ $art->piezimes }} <br>
                                <strong>Izpildīja:</strong> 
                                @if($art->completer)
                                    {{ $art->completer->name }} ({{ $art->completed_at ? $art->completed_at->format('d/m/Y H:i') : '' }})
                                @else
                                     <!-- Or leave blank if you prefer -->
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">Netika atrasti pieprasījumi!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $pieprasijumi->links() }}
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
            <div id="additionalFields" style="display:none;">
            <div class="mb-2 d-flex align-items-center">
                <label for="izrakstitais_daudzums" class="form-label mr-3" style="white-space: nowrap;">Izrakstītais daudzums</label>
                <input type="number" class="form-control" id="izrakstitais_daudzums" name="izrakstitais_daudzums" style="width: 100px;">
            </div>
            <div class="mb-2 d-flex align-items-center">
                <label for="pazinojuma_datums" class="form-label mr-3" style="white-space: nowrap;">Paziņojuma datums</label>
                <textarea class="form-control" id="pazinojuma_datums" name="pazinojuma_datums" rows="1"></textarea>
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
                <label for="aizliegums" class="form-label mr-3">Aizliegums</label>
                <select class="form-control" id="aizliegums" name="aizliegums" style="width: 200px;">
                    <option value>Izvēlieties aizliegumu</option>
                    <option value="Drīkst aizvietot">Drīkst aizvietot</option>
                    <option value="Nedrīkst aizvietot">Nedrīkst aizvietot</option>
                    <option value="NVD">NVD</option>
                    <option value="Stacionārs">Stacionārs</option>
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
    .status-indicator {
        display: inline-block;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        cursor: pointer;
        /* Initial state, add transition for smoothness */
        transition: background-color 0.3s ease, border 0.3s ease, box-shadow 0.3s ease; 
        border: 2px solid transparent; /* Start with a transparent border */
    }

    .status-indicator:hover {
        /* Example: slightly darken the color on hover */
        filter: brightness(85%); /* Makes color slightly darker */
        /* Example: Add a border */
        border: 2px solid rgba(0, 0, 0, 0.3); /* A subtle dark border */
        /* Example: Or a soft shadow */
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.2); 
    }
    
    /* If you want different hover effects for green vs. red */
    .status-indicator[style*="background-color: green"]:hover {
        filter: brightness(90%); /* Slightly less darken for green */
        border: 2px solid rgba(0, 128, 0, 0.5); /* Green border */
    }

    .status-indicator[style*="background-color: red"]:hover {
        filter: brightness(90%); /* Slightly less darken for red */
        border: 2px solid rgba(255, 0, 0, 0.5); /* Red border */
    }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('requestForm');
    const modalTitle = document.getElementById('requestModalLabel');
    const saveBtn = document.getElementById('requestModalSaveBtn');
    const izpilditBtn = document.getElementById('izpilditBtn');
    const undoBtn = document.getElementById('undoBtn');
    
    // Inputs
    const aptiekasNameInput = document.getElementById('aptiekas_name');
    const aptiekasIdInput = document.getElementById('aptiekas_id');
    const artikulaNameInput = document.getElementById('artikula_name');
    const artikulaIdInput = document.getElementById('artikula_id');
    const additionalFields = document.getElementById('additionalFields');

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
    document.querySelectorAll('.edit-request-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const data = this.dataset;
        form.action = "/pieprasijumi/" + data.id;

        // Populate Hidden IDs
        aptiekasIdInput.value = data.aptiekas_id;
        artikulaIdInput.value = data.artikula_id;

        // Populate Visible Text Inputs
        aptiekasNameInput.value = data.aptiekas_nosaukums; 
        artikulaNameInput.value = data.artikula_nosaukums;

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
        // Note: data.datums usually comes from DB as YYYY-MM-DD, we need DD/MM/YYYY for input
        document.getElementById('datums').value = formatDateForInput(data.datums);
        document.getElementById('daudzums').value = data.daudzums;
        document.getElementById('izrakstitais_daudzums').value = data.izrakstitais_daudzums;
        document.getElementById('pazinojuma_datums').value = data.pazinojuma_datums;
        document.getElementById('statuss').value = data.statuss;
        document.getElementById('aizliegums').value = data.aizliegums;
        document.getElementById('iepircejs').value = data.iepircejs;
        document.getElementById('piegades_datums').value = data.piegades_datums;
        document.getElementById('piezimes').value = data.piezimes;

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
    });

    // ---------------------------------------------------------
    // ROW EXPANSION LOGIC (Red/Green Circle Click)
    // ---------------------------------------------------------
    document.querySelectorAll('.toggle-details').forEach(indicator => {
        indicator.addEventListener('click', function (event) {
            event.stopPropagation();

            const mainRow = this.closest('tr');
            const additionalRow = mainRow.nextElementSibling;

            if (additionalRow && additionalRow.classList.contains('additional-info')) {
                if (additionalRow.style.display === 'none') {
                    additionalRow.style.display = 'table-row';
                } else {
                    additionalRow.style.display = 'none';
                }
            }
        });
    });

    // ---------------------------------------------------------
    // FILTER RADIO BUTTONS
    // ---------------------------------------------------------
    document.querySelectorAll('input[name="status_filter"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        this.form.submit();
      });
    });
  });
  izpilditBtn.addEventListener('click', function() {
        if (confirm('Vai tiešām vēlaties izpildīt šo ierakstu?')) {
            const form = document.getElementById('requestForm');
            const completedInput = document.createElement('input');
            completedInput.type = 'hidden';
            completedInput.name = 'completed';
            completedInput.value = '1';
            form.appendChild(completedInput);
            form.submit();
        }
    });
    undoBtn.addEventListener('click', function() {
        if (confirm('Vai tiešām vēlaties atgriezt šo pieprasījumu uz neizpildītu statusu?')) {
            const form = document.getElementById('requestForm');
            const completedInput = document.createElement('input');
            completedInput.type = 'hidden';
            completedInput.name = 'uncompleted';
            completedInput.value = '0';
            form.appendChild(completedInput);
            form.submit();
        }
    });
</script>