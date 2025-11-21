<x-app-layout>
    <x-slot name="header">
        <h1>Pieraksti</h1>
    </x-slot>

    <div class="container" style="width: 80%;">
        <!-- Search -->
        <form method="GET" action="{{ route('pieprasijumi.index') }}" class="mb-3">
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

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Aizvērt"></button>
            </div>
        @endif

        <!-- Table -->
        <table class="table table-striped" style="table-layout: fixed; border-collapse: collapse; width: 100%; overflow-wrap: break-word;">
            <thead>
                <tr>
                    <th style="width: 3%; border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
                    <th style="width: 9%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Datums</th>
                    <th style="width: 12%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aptieka</th>
                    <th style="width: 6%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Valsts</th>
                    <th style="width: 10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ID numurs</th>
                    <th style="width: 30%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
                    <th style="width: 5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Daudzums</th>
                    <th style="width: 5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Izrakstītais d.</th>
                    <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Statuss</th>
                    <th style="width: 8%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Aizliegums</th>
                    <th style="width: 6%; border: 1px solid #080000ff; padding: 4px; text-align: center;"> - </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pieprasijumi as $art)
                    <tr>
                        <td style="border: 1px solid #080000ff; padding: 4px;">
                            <!-- New column for status indicator -->
                            <span class="status-indicator" style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: {{ $art->completed ? 'green' : 'red' }};"></span>
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">{{ $art->datums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->aptiekas->nosaukums }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->valsts }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->id_numurs }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px;">{{ $art->artikuli->nosaukums }}</td>
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
                    <tr class="additional-info" style="display:none;">
                        <td colspan="11">
                            <strong>Paziņojuma datums:</strong> {{ $art->pazinojuma_datums }} <br>
                            <strong>Iepircējs:</strong> {{ $art->iepircejs }} <br>
                            <strong>Piegādes datums:</strong> {{ $art->piegades_datums }} <br>
                            <strong>Piezīmes:</strong> {{ $art->piezimes }} <br>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="11">
                            <a href="#" class="see-more">Redzēt vairāk</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14">Netika atrasti pieprasījumi!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $pieprasijumi->links() }}
    </div>

<!-- Pieprasījumi Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="requestForm" method="POST" action="">
        @csrf
        @method('POST') <!-- Will be overridden for edit -->

        <div class="modal-header">
          <h5 class="modal-title" id="requestModalLabel">Pievienot/labot pieprasījumu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Aizvērt"></button>
        </div>

        <div class="modal-body">
            <div class="mb-3" id="completedContainer" style="display: none;">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="completed" name="completed" value="1" checked>
                    <label class="form-check-label" for="completed">Pabeigts</label>
                </div>
            </div>
            <div class="mb-3" id="unCompletedContainer" style="display: none;">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="uncompleted" name="uncompleted" value="0">
                    <label class="form-check-label" for="uncompleted">Pabeigts</label>
                </div>
            </div>
            <div class="mb-3">
                <label for="datums" class="form-label">Datums</label>
                <input type="date" class="form-control" id="datums" name="datums" required>
            </div>
            <div class="mb-3">
                <label for="aptiekas_name" class="form-label">Aptieka</label>
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

            <div class="mb-3">
                <label for="artikula_name" class="form-label">Artikuls</label>
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
            <div class="mb-3">
                <label for="daudzums" class="form-label">Daudzums</label>
                <input type="number" class="form-control" id="daudzums" name="daudzums" required>
            </div>
            <div id="additionalFields" style="display:none;">
            <div class="mb-3">
                <label for="izrakstitais_daudzums" class="form-label">Izrakstītais daudzums</label>
                <input type="number" class="form-control" id="izrakstitais_daudzums" name="izrakstitais_daudzums">
            </div>
            <div class="mb-3">
                <label for="pazinojuma_datums" class="form-label">Paziņojuma datums</label>
                <input type="text" class="form-control" id="pazinojuma_datums" name="pazinojuma_datums">
            </div>
            <div class="mb-3">
                <label for="statuss" class="form-label">Statuss</label>
                <select class="form-control" id="statuss" name="statuss">
                    <option value>Izvēlieties statusu</option>
                    <option value="Pasūtīts">Pasūtīts</option>
                    <option value="Atcelts">Atcelts</option>
                    <option value="Mainīta piegāde">Mainīta piegāde</option>
                    <option value="Ir noliktavā">Ir noliktavā</option>
                    <option value="Daļēji atlikumā">Daļēji atlikumā</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="aizliegums" class="form-label">Aizliegums</label>
                <select class="form-control" id="aizliegums" name="aizliegums">
                    <option value>Izvēlieties aizliegumu</option>
                    <option value="Drīkst aizvietot">Drīkst aizvietot</option>
                    <option value="Nedrīkst aizvietot">Nedrīkst aizvietot</option>
                    <option value="NVD">NVD</option>
                    <option value="Stacionārs">Stacionārs</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="iepircejs" class="form-label">Iepircējs</label>
                <select class="form-control" id="iepircejs" name="iepircejs">
                    <option value>Izvēlieties iepircēju</option>
                    <option value="Artūrs">Artūrs</option>
                    <option value="Liene">Liene</option>
                    <option value="Anna">Anna</option>
                    <option value="Iveta">Iveta</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="piegades_datums" class="form-label">Piegādes datums</label>
                <input type="text" class="form-control" id="piegades_datums" name="piegades_datums">
            </div>
            <div class="mb-3">
                <label for="piezimes" class="form-label">Piezīmes</label>
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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('requestForm');
    const modalTitle = document.getElementById('requestModalLabel');
    const saveBtn = document.getElementById('requestModalSaveBtn');
    const aptiekasNameInput = document.getElementById('aptiekas_name');
    const aptiekasIdInput = document.getElementById('aptiekas_id');
    
    const artikulaNameInput = document.getElementById('artikula_name');
    const artikulaIdInput = document.getElementById('artikula_id');


    aptiekasNameInput.addEventListener('input', function() {
        // When input changes, update the hidden input with the corresponding ID
        const value = this.value;
        Array.from(document.getElementById('aptieki').options).forEach(option => {
            if (option.value === value) {
                aptiekasIdInput.value = option.getAttribute('data-id');
            }
        });
    });

    artikulaNameInput.addEventListener('input', function() {
        // When input changes, update the hidden input with the corresponding ID
        const value = this.value;
        Array.from(document.getElementById('artikuli').options).forEach(option => {
            if (option.value === value) {
                artikulaIdInput.value = option.getAttribute('data-id');
            }
        });
    });
    // "Add" button
    document.getElementById('addRequestBtn').addEventListener('click', function () {
      form.reset();
      form.action = "{{ route('pieprasijumi.store') }}"; // your route to store
      if (form.querySelector('input[name="_method"]')) {
        form.querySelector('input[name="_method"]').remove();
      }
      const today = new Date().toISOString().substring(0, 10);
      document.getElementById('datums').value = today;

      modalTitle.textContent = 'Pievienot jaunu pieprasījumu';
      saveBtn.textContent = 'Saglabāt';

      document.getElementById('completedContainer').style.display = 'none';
      additionalFields.style.display = 'none';
    });

    // "Edit" buttons
    document.querySelectorAll('.edit-request-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const data = this.dataset;
        form.action = "/pieprasijumi/" + data.id;

        aptiekasIdInput.value = data.aptiekas_id;
        artikulaIdInput.value = data.artikula_id;

        aptiekasNameInput.value = data.aptiekas_nosaukums; 
        artikulaNameInput.value = data.artikula_nosaukums;

        if (!form.querySelector('input[name="_method"]')) {
          const methodInput = document.createElement('input');
          methodInput.type = 'hidden';
          methodInput.name = '_method';
          methodInput.value = 'PUT';
          form.appendChild(methodInput);
        } else {
          form.querySelector('input[name="_method"]').value = 'PUT';
        }
        document.getElementById('datums').value = data.datums;
        document.getElementById('aptiekas_id').value = data.aptiekas_id;
        document.getElementById('artikula_id').value = data.artikula_id;
        document.getElementById('daudzums').value = data.daudzums;
        document.getElementById('izrakstitais_daudzums').value = data.izrakstitais_daudzums;
        document.getElementById('pazinojuma_datums').value = data.pazinojuma_datums;
        document.getElementById('statuss').value = data.statuss;
        document.getElementById('aizliegums').value = data.aizliegums;
        document.getElementById('iepircejs').value = data.iepircejs;
        document.getElementById('piegades_datums').value = data.piegades_datums;
        document.getElementById('piezimes').value = data.piezimes;
        if (data.completed === '1') {
            document.getElementById('completedContainer').style.display = 'block';
            document.getElementById('completed').checked = true;
            document.getElementById('unCompletedContainer').style.display = 'none';
            document.getElementById('uncompleted').checked = false;
        } else {
            document.getElementById('completedContainer').style.display = 'none';
            document.getElementById('completed').checked = true;
            document.getElementById('unCompletedContainer').style.display = 'block';
            document.getElementById('uncompleted').checked = false;
        }

        additionalFields.style.display = 'block';
        modalTitle.textContent = 'Labot pieprasījumu';
        saveBtn.textContent = 'Atjaunināt';
      });
    });
    // Handle "See More" link click
    document.querySelectorAll('.see-more').forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const additionalRow = this.closest('tr').previousElementSibling;

            // Toggle visibility of the additional info row
            if (additionalRow.style.display === 'none') {
                additionalRow.style.display = ''; // Show the additional information
                this.textContent = 'Redzēt mazāk'; // Change link text
            } else {
                additionalRow.style.display = 'none'; // Hide the additional information
                this.textContent = 'Redzēt vairāk'; // Reset link text
            }
        });
    });
    document.querySelectorAll('input[name="status_filter"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        this.form.submit(); // Submit the form when a radio button is changed
      });
    });
  });
</script>