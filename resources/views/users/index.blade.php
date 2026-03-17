<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Lietotāji</h2>
        @if(session('success'))
            <div class="alert alert-success mt-2">{{ session('success') }}</div>
        @endif
    </x-slot>

    <div class="py-4">
        <div class="container" style="width:90%; max-width:1200px; margin:0 auto;">

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createUserModal">
                Pievienot lietotāju
            </button>

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vārds</th>
                        <th>E-pasts</th>
                        <th>Loma</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->role }}</td>
                            <td>
                                <button class="btn btn-sm btn-secondary edit-user-btn"
                                        data-id="{{ $u->id }}"
                                        data-name="{{ $u->name }}"
                                        data-email="{{ $u->email }}"
                                        data-role="{{ $u->role }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal">
                                    Labot
                                </button>

                                <form action="{{ route('users.destroy', $u) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="if(confirm('Dzēst šo lietotāju?')) this.closest('form').submit();">
                                        Dzēst
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>

    {{-- Create User Modal --}}
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog" style="max-width:40%;">
        <div class="modal-content">
          <form method="POST" action="{{ route('users.store') }}" autocomplete="off"> 
            @csrf
            <div class="modal-header">
              <h5 class="modal-title">Pievienot lietotāju</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2">
                <label>Vārds</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="mb-2">
                <label>E-pasts</label>
                <input type="email" name="email" class="form-control" required autocomplete="off">
              </div>
              <div class="mb-2">
                    <label>Loma</label>
                    <select name="role" class="form-control" required>
                        <option value="brivibas">brivibas</option>
                        <option value="kruzes">kruzes</option>
                        <option value="farmaceiti">farmaceiti</option>
                    </select>
                </div>
              <div class="mb-2">
                <label>Parole</label>
                <input type="password" name="password" class="form-control" required autocomplete="off">
              </div>
              <div class="mb-2">
                <label>Parole atkārtoti</label>
                <input type="password" name="password_confirmation" class="form-control" required >
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
              <button type="submit" class="btn btn-primary">Saglabāt</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Edit User Modal --}}
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog" style="max-width:40%;">
        <div class="modal-content">
          <form method="POST" id="editUserForm" action="" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="modal-header">
              <h5 class="modal-title">Labot lietotāju</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2">
                <label>Vārds</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
              </div>
              <div class="mb-2">
                <label>E-pasts</label>
                <input type="email" name="email" id="edit_email" class="form-control" required autocomplete="off">
              </div>
              <div class="mb-2">
                    <label>Loma</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="brivibas">brivibas</option>
                        <option value="kruzes">kruzes</option>
                        <option value="farmaceiti">farmaceiti</option>
                    </select>
                </div>
              <div class="mb-2">
                <label>Jauna parole (ja vēlaties mainīt)</label>
                <input type="password" name="password" class="form-control" autocomplete="off">
              </div>
              <div class="mb-2">
                <label>Jauna parole atkārtoti</label>
                <input type="password" name="password_confirmation" class="form-control">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
              <button type="submit" class="btn btn-primary">Saglabāt</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const editForm = document.getElementById('editUserForm');
        document.querySelectorAll('.edit-user-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id    = this.dataset.id;
                const name  = this.dataset.name;
                const email = this.dataset.email;
                const role  = this.dataset.role;

                editForm.action = "{{ url('users') }}/" + id;
                document.getElementById('edit_name').value  = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_role').value  = role;
            });
        });
    });
    </script>
</x-app-layout>
