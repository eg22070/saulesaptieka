<x-app-layout>
    <x-slot name="header">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Aizvērt"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-slot>

    <div class="py-4">
        <div class="container" style="width:90%; max-width:1600px; margin:0 auto;">
            <h4 class="mb-3">Pieprasījumi</h4>

            <form method="POST" action="{{ route('pasutijumu-pieprasijumi.store') }}" class="card p-3 mb-4">
                @csrf
                <div class="d-flex align-items-end gap-3">
                    <div>
                        <label class="form-label">Pieprasījuma datums</label>
                        <input type="text" id="pp_datums" name="datums" class="form-control" placeholder="DD/MM/YYYY" required value="{{ now()->format('d/m/Y') }}">
                    </div>
                    <button class="btn btn-primary" type="submit">Izveidot pieprasījumu</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Datums</th>
                            <th>Statuss</th>
                            <th>Pasūtījumu skaits</th>
                            <th>Izveidoja</th>
                            <th>Pabeidza</th>
                            <th style="width:280px;">Darbības</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pieprasijumi as $p)
                            <tr>
                                <td>{{ optional($p->datums)->format('d/m/Y') }}</td>
                                <td>
                                    @if($p->completed)
                                        <span class="badge bg-success">Pabeigts</span>
                                    @else
                                        <span class="badge bg-secondary">Atvērts</span>
                                    @endif
                                </td>
                                <td>{{ $p->pasutijumi_count }}</td>
                                <td>{{ $p->creator?->name ?? '—' }}</td>
                                <td>
                                    @if($p->completed)
                                        {{ $p->completer?->name ?? '—' }} ({{ optional($p->completed_at)->format('d/m/Y H:i') }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="d-flex gap-2">
                                    <a href="{{ route('pasutijumu-pieprasijumi.show', $p) }}" class="btn btn-sm btn-primary">Atvērt</a>
                                    @if(!$p->completed)
                                        <form method="POST" action="{{ route('pasutijumu-pieprasijumi.destroy', $p) }}" onsubmit="return confirm('Dzēst pieprasījumu?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">Dzēst</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Nav izveidotu pieprasījumu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $pieprasijumi->links() }}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('pp_datums')) {
                flatpickr("#pp_datums", {
                    dateFormat: "d/m/Y",
                    locale: "lv"
                });
            }
        });
    </script>
</x-app-layout>
