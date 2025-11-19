<x-app-layout>
    <x-slot name="header">
        <h1>Artikuli</h1>
    </x-slot>

    <div class="container" style="width: 80%;">
        <!-- Search Bar -->
        <form action="{{ route('artikuli.index') }}" method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Meklēt pēc nosaukuma, id numura vai valsts" value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>
            </div>
        </form>

        <!-- Add Artikuls Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#artikuliModal" id="addArtikulsBtn">Pievienot jaunu artikulu</button>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Artikuli Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nosaukums</th>
                    <th>Id numurs</th>
                    <th>Valsts</th>
                    <th>SNN</th>
                    <th>Analogs</th>
                    <th>Īpašās atzīmes</th>
                    <th>Darbības</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $artikuls)
                    <tr>
                        <td>{{ $artikuls->nosaukums }}</td>
                        <td>{{ $artikuls->id_numurs }}</td>
                        <td>{{ $artikuls->valsts }}</td>
                        <td>{{ $artikuls->snn }}</td>
                        <td>{{ $artikuls->analogs }}</td>
                        <td>{{ $artikuls->atzimes }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-artikuls-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#artikuliModal"
                                    data-id="{{ $artikuls->id }}"
                                    data-nosaukums="{{ $artikuls->nosaukums }}"
                                    data-id_numurs="{{ $artikuls->id_numurs }}"
                                    data-valsts="{{ $artikuls->valsts }}"
                                    data-snn="{{ $artikuls->snn }}"
                                    data-analogs="{{ $artikuls->analogs }}"
                                    data-atzimes="{{ $artikuls->atzimes }}"
                            >Labot</button>

                            <form action="{{ route('artikuli.destroy', $artikuls->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Vai tiešām vēlaties izdzēst šo artikulu?')">Dzēst</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Netika atrasti artikuli!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $products->links() }}
    </div>
</x-app-layout>
<!-- Artikelus Modal -->
<div class="modal fade" id="artikuliModal" tabindex="-1" aria-labelledby="artikuliModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="artikuliForm" method="POST" action="">
        @csrf
        @method('POST') <!-- Will be overridden for editing -->

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
                <input type="text" class="form-control" id="id_numurs" name="id_numurs" required>
            </div>
            <div class="mb-3">
                <label for="valsts" class="form-label">Valsts</label>
                <input type="text" class="form-control" id="valsts" name="valsts" required>
            </div>
            <div class="mb-3">
                <label for="snn" class="form-label">SNN</label>
                <input type="text" class="form-control" id="snn" name="snn" required>
            </div>
            <div class="mb-3">
                <label for="analogs" class="form-label">Analogs</label>
                <input type="text" class="form-control" id="analogs" name="analogs">
            </div>
            <div class="mb-3">
                <label for="atzimes" class="form-label">Atkāpe</label>
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
    });

    // Handle "Edit" buttons
    document.querySelectorAll('.edit-artikuls-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const nosaukums = this.dataset.nosaukums;
        const id_numurs = this.dataset.id_numurs;
        const valsts = this.dataset.valsts;
        const snn = this.dataset.snn;
        const analogs = this.dataset.analogs;
        const atzimes = this.dataset.atzimes;

        // Set form action for update
        form.action = "/artikuli/" + id;

        // Add or update PUT method
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
        document.getElementById('valsts').value = valsts;
        document.getElementById('snn').value = snn;
        document.getElementById('analogs').value = analogs;
        document.getElementById('atzimes').value = atzimes;

        // Set modal title and button
        modalTitle.textContent = 'Labot artikulu';
        saveBtn.textContent = 'Atjaunināt';
      });
    });
  });
</script>