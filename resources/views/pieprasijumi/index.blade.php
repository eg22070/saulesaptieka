<x-app-layout>
    <x-slot name="header">
        <h1>Pieraksti</h1>
    </x-slot>

    <div class="container" style="width: 150%;">
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
            <button class="btn btn-outline-secondary" type="submit">Filtrēt</button>
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
        <table class="table table-striped">
            <thead>
                <tr>
                    <th> - </th>
                    <th >Datums</th>
                    <th >Aptieka</th>
                    <th >Valsts</th>
                    <th >ID numurs</th>
                    <th c>Nosaukums</th>
                    <th >Dau</th>
                    <th>Iz d.</th>
                    <th>Paziņojuma datums</th>
                    <th>Stat</th>
                    <th>Aizlie</th>
                    <th>Iepircējs</th>
                    <th>Piegādes datums</th>
                    <th>Piezīmes</th>
                    <th> - </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pieprasijumi as $art)
                    <tr>
                        <td>
                            <!-- New column for status indicator -->
                            <span class="status-indicator" style="display: inline-block; width: 15px; height: 15px; border-radius: 50%; background-color: {{ $art->completed ? 'green' : 'red' }};"></span>
                        </td>
                        <td >{{ $art->datums }}</td>
                        <td >{{ $art->aptiekas->nosaukums }}</td>
                        <td >{{ $art->artikuli->valsts }}</td>
                        <td >{{ $art->artikuli->id_numurs }}</td>
                        <td >{{ $art->artikuli->nosaukums }}</td>
                        <td >{{ $art->daudzums }}</td>
                        <td>{{ $art->izrakstitais_daudzums }}</td>
                        <td>{{ $art->pazinojuma_datums }}</td>
                        <td>{{ $art->statuss }}</td>
                        <td>{{ $art->aizliegums }}</td>
                        <td>{{ $art->iepircejs }}</td>
                        <td>{{ $art->piegades_datums }}</td>
                        <td>{{ $art->piezimes }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-primary edit-request-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#requestModal"
                                    data-id="{{ $art->id }}"
                                    data-datums="{{ $art->datums }}"
                                    data-aptiekas_id="{{ $art->aptiekas->id }}"
                                    data-artikula_id="{{ $art->artikuli->id }}"
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
                            <input type="checkbox" class="form-check-input" id="completed" name="completed" value="1" >
                            <label class="form-check-label" for="completed">Pabeigts</label>
                        </div>
                    </div>
            <div class="mb-3">
                <label for="datums" class="form-label">Datums</label>
                <input type="date" class="form-control" id="datums" name="datums" required>
</div>
            <div class="mb-3">
    <label for="aptiekas_id" class="form-label">Aptieka</label>
    <input list="aptieki" id="aptiekas_id" name="aptiekas_id" class="form-control" placeholder="Rakstiet aptiekas nosaukumu" required>
    <datalist id="aptieki">
    @foreach($aptiekas as $aptieka)
        <option value="{{ $aptieka->id }}">{{ $aptieka->nosaukums }}</option>
    @endforeach
    </datalist>
</div>

<div class="mb-3">
    <label for="artikula_id" class="form-label">Artikuls</label>
    <input list="artikuli" id="artikula_id" name="artikula_id" class="form-control" placeholder="Rakstiet artikula nosaukumu" required>
    <datalist id="artikuli">
    @foreach($artikuli as $artikuls)
        <option value="{{ $artikuls->id }}">{{ $artikuls->nosaukums }}</option>
    @endforeach
    </datalist>
</div>
            <div class="mb-3">
                <label for="daudzums" class="form-label">Daudzums</label>
                <input type="number" class="form-control" id="daudzums" name="daudzums" required>
            </div>
            <div class="mb-3">
                <label for="izrakstitais_daudzums" class="form-label">Izrakstītais daudzums</label>
                <input type="number" class="form-control" id="izrakstitais_daudzums" name="izrakstitais_daudzums">
            </div>
            <div class="mb-3">
                <label for="pazinojuma_datums" class="form-label">Paziņojuma datums</label>
                <textarea class="form-control" id="pazinojuma_datums" name="pazinojuma_datums" rows="3"></textarea>
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
                <textarea class="form-control" id="piegades_datums" name="piegades_datums" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="piezimes" class="form-label">Piezīmes</label>
                <textarea class="form-control" id="piezimes" name="piezimes" rows="3"></textarea>
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
</x-app-layout>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('requestForm');
    const modalTitle = document.getElementById('requestModalLabel');
    const saveBtn = document.getElementById('requestModalSaveBtn');

    // "Add" button
    document.getElementById('addRequestBtn').addEventListener('click', function () {
      form.reset();
      form.action = "{{ route('pieprasijumi.store') }}"; // your route to store
      if (form.querySelector('input[name="_method"]')) {
        form.querySelector('input[name="_method"]').remove();
      }
      modalTitle.textContent = 'Pievienot jaunu pieprasījumu';
      saveBtn.textContent = 'Saglabāt';

      document.getElementById('completedContainer').style.display = 'none';
    });

    // "Edit" buttons
    document.querySelectorAll('.edit-request-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const data = this.dataset;
        form.action = "/pieprasijumi/" + data.id;
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
        document.getElementById('completed').checked = data.completed === '1';
        document.getElementById('completedContainer').style.display = 'block';
        // Update modal title and button
        modalTitle.textContent = 'Labot pieprasījumu';
        saveBtn.textContent = 'Atjaunināt';
      });
    });
  });
</script>