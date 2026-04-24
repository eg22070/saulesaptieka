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

    <style>
        .pp-toolbar {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            background: #fff;
        }
        .pp-card {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            background: #fff;
        }
        .pp-card-header {
            border-bottom: 1px solid #d7d7d7;
            background: #f7f7f7;
        }
        .pp-subtable thead th {
            background: #ececec;
            font-weight: 600;
        }
    </style>

    <div class="py-4">
        <div class="container" style="width:95%; max-width:1900px; margin:0 auto;">
            <div class="row {{ $pieprasijums->completed ? 'justify-content-center' : '' }}">
                <div class="{{ $pieprasijums->completed ? 'col-12 col-xl-10' : 'col-12' }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Pieprasījums #{{ $pieprasijums->id }}</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('pasutijumu-pieprasijumi.export.word', $pieprasijums) }}" class="btn btn-outline-primary btn-sm">
                                Eksportēt Word
                            </a>
                            <a href="{{ route('pasutijumu-pieprasijumi.index') }}" class="btn btn-outline-secondary btn-sm">Atpakaļ</a>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $allHierarchyRows = $assignedPasutijumi
                    ->map(function ($p) {
                        $p->entry_type = 'pasutijums';
                        return $p;
                    })
                    ->concat(
                        $brivieArtikuli->map(function ($row) {
                            $row->entry_type = 'brivais';
                            return $row;
                        })
                    );
                $hierarchy = $allHierarchyRows
                    ->groupBy(function ($p) {
                        if (!empty($p->artikula_id)) {
                            return 'a:'.$p->artikula_id;
                        }
                        return 'f:';
                    })
                    ->map(function ($rows) {
                        $first = $rows->first();
                        $artikulsName = $first->product?->nosaukums ?? '—';
                        $productValsts = trim((string) ($first->product?->valsts ?? ''));
                        $valstsFromNosaukums = '—';

                        if ($artikulsName !== '—') {
                            $normalizedName = trim((string) $artikulsName);
                            if (preg_match('/\bPZN\s*\d+\s*$/i', $normalizedName)) {
                                $valstsFromNosaukums = 'DE';
                            } else {
                                $parts = preg_split('/\s+/', $normalizedName);
                                $lastPart = is_array($parts) && !empty($parts) ? end($parts) : '';
                                $lastPart = strtoupper(trim((string) $lastPart, " \t\n\r\0\x0B,.;:()[]"));
                                if ($lastPart !== '') {
                                    $valstsFromNosaukums = $lastPart;
                                }
                            }
                        }

                        $doctorGroups = $rows
                            ->groupBy(function ($row) {
                                $institution = trim((string) ($row->arstniecibas_iestade ?? ''));
                                $doctor = trim((string) ($row->arsts ?? ''));
                                return strtolower($institution).'||'.strtolower($doctor);
                            })
                            ->map(function ($doctorRows) {
                                $firstRow = $doctorRows->first();
                                return [
                                    'iestade' => trim((string) ($firstRow->arstniecibas_iestade ?? '')) ?: 'Nav iestādes',
                                    'arsts' => trim((string) ($firstRow->arsts ?? '')) ?: 'Nav ārsta',
                                    'sum' => (float) $doctorRows->sum('skaits'),
                                    'entries' => $doctorRows->sortByDesc('id')->values(),
                                ];
                            })
                            ->sortBy([['iestade', 'asc'], ['arsts', 'asc']])
                            ->values();

                        return [
                            'artikula_id' => !empty($first->artikula_id) ? (int) $first->artikula_id : null,
                            'artikuls' => $artikulsName,
                            'sum' => (float) $rows->sum('skaits'),
                            'doctorGroups' => $doctorGroups,
                            'without_arst' => (bool) ($first->product?->without_arst ?? false),
                            'nemedikamenti' => (bool) ($first->product?->nemedikamenti ?? false),
                            'id_numurs' => trim((string) ($first->product?->id_numurs ?? '')),
                            'valsts' => $valstsFromNosaukums !== '—' ? $valstsFromNosaukums : ($productValsts ?: '—'),
                        ];
                    })
                    ->sortBy('artikuls')
                    ->values();
                $hierarchyNemedikamenti = $hierarchy
                    ->filter(fn ($item) => (bool) ($item['nemedikamenti'] ?? false))
                    ->values();

                $hierarchyNWithoutArst = $hierarchy
                    ->filter(function ($item) {
                        if ((bool) ($item['nemedikamenti'] ?? false)) {
                            return false;
                        }
                        $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                        return str_starts_with($idNumurs, 'N') && (bool) ($item['without_arst'] ?? false);
                    })
                    ->values();

                $hierarchyEU = $hierarchy
                    ->filter(function ($item) {
                        if ((bool) ($item['nemedikamenti'] ?? false)) {
                            return false;
                        }
                        $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                        return str_starts_with($idNumurs, 'EU');
                    })
                    ->values();

                $hierarchyNWithArst = $hierarchy
                    ->filter(function ($item) {
                        if ((bool) ($item['nemedikamenti'] ?? false)) {
                            return false;
                        }
                        $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                        return str_starts_with($idNumurs, 'N') && !(bool) ($item['without_arst'] ?? false);
                    })
                    ->values();

                $hierarchyOtherDetailed = $hierarchy
                    ->filter(function ($item) {
                        if ((bool) ($item['nemedikamenti'] ?? false)) {
                            return false;
                        }
                        $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                        if (str_starts_with($idNumurs, 'EU')) {
                            return false;
                        }
                        if (str_starts_with($idNumurs, 'N')) {
                            return false;
                        }
                        return true;
                    })
                    ->values();
                $detailedSections = collect([
                    ['title' => 'CENTRALIZĒTI REĢISTRĒTU ZĀĻU SAŅEMŠANAI', 'items' => $hierarchyEU],
                    ['title' => 'NEREĢISTRĒTU BEZRECEPŠU UN 
                    NEREĢISTRĒTU ZĀĻU SAŅEMŠANAI VAI LATVIJAS ZĀĻU REĢISTRĀ REĢISTRĒTU ZĀĻU SAŅEMŠANAI', 'items' => $hierarchyNWithArst],
                    ['title' => 'Citi artikuli', 'items' => $hierarchyOtherDetailed],
                ])->filter(fn ($section) => $section['items']->isNotEmpty())->values();

                $aizliegumsByArtikuls = is_array($pieprasijums->aizliegums_by_artikuls)
                    ? $pieprasijums->aizliegums_by_artikuls
                    : [];
                $aizliegumsOptions = ['Drīkst aizvietot', 'Nedrīkst aizvietot', 'NVD', 'Stacionārs'];
            @endphp

            <form id="syncPasutijumiForm" method="POST" action="{{ route('pasutijumu-pieprasijumi.sync', $pieprasijums) }}">
                @csrf
            </form>

            <form id="pieprasijumsSaveForm" method="POST" action="{{ route('pasutijumu-pieprasijumi.update', $pieprasijums) }}">
                @csrf
                @method('PUT')
                <div class="row {{ $pieprasijums->completed ? 'justify-content-center' : '' }}">
                    <div class="{{ $pieprasijums->completed ? 'col-12 col-xl-10' : 'col-12' }}">
                        <div class="pp-toolbar px-3 py-3 mb-3">
                            <div class="d-flex flex-wrap align-items-end gap-3">
                                <div>
                                    <label class="form-label mb-1">Datums</label>
                                    <input
                                        type="text"
                                        id="pp_edit_datums"
                                        name="datums"
                                        class="form-control"
                                        style="width: 150px;"
                                        value="{{ optional($pieprasijums->datums)->format('d/m/Y') }}"
                                        {{ $pieprasijums->completed ? 'disabled' : 'required' }}
                                    >
                                </div>
                                <div>
                                    <label class="form-label mb-1">Statuss</label>
                                    <div class="pt-1">
                                        @if($pieprasijums->completed)
                                            <span class="badge bg-success">Pabeigts</span>
                                        @else
                                            <span class="badge bg-secondary">Atvērts</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="ms-auto d-flex align-items-end gap-2">
                                    @if(!$pieprasijums->completed)
                                        <button class="btn btn-primary" type="submit">Saglabāt</button>
                                        <button class="btn btn-success" type="submit" name="complete_after_save" value="1" onclick="return confirm('Pabeigt pieprasījumu? Šī darbība saglabās izmaiņas un ģenerēs ierakstus sadaļā Pieprasījumi noliktavai.');">Pabeigt pieprasījumu</button>
                                    @else
                                        <small class="text-muted">
                                            Pabeidza: {{ $pieprasijums->completer?->name ?? '—' }},
                                            {{ optional($pieprasijums->completed_at)->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 {{ $pieprasijums->completed ? 'justify-content-center' : '' }}">
                    <div class="{{ $pieprasijums->completed ? 'col-12 col-xl-10' : 'col-lg-7' }}">
                        <div class="pp-card">
                            <div class="pp-card-header d-flex justify-content-between align-items-center px-3 py-2">
                                <strong>Pieprasījuma hierarhija</strong>
                                @if(!$pieprasijums->completed)
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addBrivaisArtikulsModal">
                                        Pievienot brīvo artikulu
                                    </button>
                                @endif
                            </div>
                            <div class="p-3">
                                @if($hierarchy->isEmpty())
                                    <div class="text-muted">Nav pievienotu pasūtījumu.</div>
                                @else
                                    @if($hierarchyNemedikamenti->isNotEmpty())
                                        <div class="border rounded mb-3 p-3">
                                            <h6 class="mb-3"><strong>NEREĢISTRĒTU NE -ZĀĻU SAŅEMŠANAI:</strong></h6>
                                            <ol class="mb-0">
                                                @foreach($hierarchyNemedikamenti as $item)
                                                    <li class="mb-1">
                                                        <strong>{{ $item['artikuls'] }}</strong>
                                                        -
                                                        {{ rtrim(rtrim(number_format((float) $item['sum'], 2, '.', ''), '0'), '.') }} oriģināli
                                                    </li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    @endif

                                    @if($hierarchyNWithoutArst->isNotEmpty())
                                        <div class="border rounded mb-3">
                                            <div class="px-3 py-2 border-bottom" style="background:#f6f6f6;">
                                                <strong>NEREĢISTRĒTU ZĀĻU SAŅEMŠANAI: </strong>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0 pp-subtable">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:12%;">Valsts</th>
                                                            <th style="width:58%;">Zāļu nosaukums</th>
                                                            <th style="width:15%;" class="text-center">Skaits</th>
                                                            <th style="width:15%;">Aizliegums</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($hierarchyNWithoutArst as $item)
                                                            @php
                                                                $selectedAizliegums = !empty($item['artikula_id'])
                                                                    ? ($aizliegumsByArtikuls[(string) $item['artikula_id']] ?? 'Drīkst aizvietot')
                                                                    : '—';
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $item['valsts'] }}</td>
                                                                <td><strong>{{ $item['artikuls'] }}</strong></td>
                                                                <td class="text-center">{{ rtrim(rtrim(number_format((float) $item['sum'], 2, '.', ''), '0'), '.') }}</td>
                                                                <td>
                                                                    @if(!empty($item['artikula_id']))
                                                                        @if($pieprasijums->completed)
                                                                            <span class="small fw-semibold">{{ $selectedAizliegums }}</span>
                                                                        @else
                                                                            <select
                                                                                id="aizliegums_n_{{ $item['artikula_id'] }}"
                                                                                name="aizliegums_by_artikuls[{{ $item['artikula_id'] }}]"
                                                                                class="form-select form-select-sm"
                                                                            >
                                                                                @foreach($aizliegumsOptions as $option)
                                                                                    <option value="{{ $option }}" {{ $selectedAizliegums === $option ? 'selected' : '' }}>
                                                                                        {{ $option }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif

                                    @foreach($detailedSections as $section)
                                        <div class="border rounded mb-3">
                                            <div class="px-3 py-2 border-bottom" style="background:#f6f6f6;">
                                                <strong>{{ $section['title'] }}:</strong>
                                            </div>
                                            <div class="p-2">
                                                @foreach($section['items'] as $idx => $item)
                                                    <div class="border rounded mb-3">
                                                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="background:#f6f6f6;">
                                                            <div class="fw-semibold">
                                                                {{ $idx + 1 }}. Zāļu nosaukums:
                                                                <span class="text-dark">{{ $item['artikuls'] }}</span>
                                                                @if(!empty($item['artikula_id']))
                                                                    @php
                                                                        $selectedAizliegums = $aizliegumsByArtikuls[(string) $item['artikula_id']] ?? 'Drīkst aizvietot';
                                                                    @endphp
                                                                    <div class="mt-2 d-flex align-items-center gap-2">
                                                                        <label class="small mb-0" for="aizliegums_{{ $item['artikula_id'] }}">Aizliegums:</label>
                                                                        @if($pieprasijums->completed)
                                                                            <span class="small fw-semibold">{{ $selectedAizliegums }}</span>
                                                                        @else
                                                                            <select
                                                                                id="aizliegums_{{ $item['artikula_id'] }}"
                                                                                name="aizliegums_by_artikuls[{{ $item['artikula_id'] }}]"
                                                                                class="form-select form-select-sm"
                                                                                style="width: 190px;"
                                                                            >
                                                                                @foreach($aizliegumsOptions as $option)
                                                                                    <option value="{{ $option }}" {{ $selectedAizliegums === $option ? 'selected' : '' }}>
                                                                                        {{ $option }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="fw-semibold">
                                                                Kopā: {{ rtrim(rtrim(number_format((float) $item['sum'], 2, '.', ''), '0'), '.') }}
                                                            </div>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered mb-0 pp-subtable">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:42%;">Ārst. iestāde</th>
                                                                        <th style="width:38%;">Ārsts</th>
                                                                        <th style="width:20%;" class="text-center">Daudz.</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($item['doctorGroups'] as $doctorGroup)
                                                                        <tr>
                                                                            <td>{{ $doctorGroup['iestade'] }}</td>
                                                                            <td>{{ $doctorGroup['arsts'] }}</td>
                                                                            <td class="text-center">{{ rtrim(rtrim(number_format((float) $doctorGroup['sum'], 2, '.', ''), '0'), '.') }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="3" class="small text-muted" style="background:#fcfcfc;">
                                                                                <strong>Ieraksti:</strong>
                                                                                @foreach($doctorGroup['entries'] as $entry)
                                                                                    <span class="me-2">
                                                                                        @if(($entry->entry_type ?? '') === 'brivais')
                                                                                            @if(!$pieprasijums->completed)
                                                                                                <button
                                                                                                    type="button"
                                                                                                    class="btn btn-link btn-sm p-0 align-baseline edit-brivais-artikuls-btn"
                                                                                                    data-id="{{ $entry->id }}"
                                                                                                    data-skaits="{{ $entry->skaits }}"
                                                                                                    data-arsts="{{ e($entry->arsts ?? '') }}"
                                                                                                    data-arstniecibas_iestade="{{ e($entry->arstniecibas_iestade ?? '') }}"
                                                                                                    data-artikula-id="{{ $entry->artikula_id ?? '' }}"
                                                                                                    data-artikula-label="{{ e($entry->product?->nosaukums ?? '') }}"
                                                                                                    data-without-arst="{{ $entry->product?->without_arst ? 1 : 0 }}"
                                                                                                    data-nemedikamenti="{{ $entry->product?->nemedikamenti ? 1 : 0 }}"
                                                                                                >
                                                                                                    Brīvais artikuls #{{ $entry->id }}
                                                                                                </button>
                                                                                            @else
                                                                                                Brīvais artikuls #{{ $entry->id }}
                                                                                            @endif
                                                                                            (skaits: {{ rtrim(rtrim(number_format((float) $entry->skaits, 2, '.', ''), '0'), '.') }})
                                                                                        @else
                                                                                            #{{ $entry->id }}
                                                                                            (nr. {{ $entry->pasutijuma_numurs ?: '—' }},
                                                                                            klients: {{ $entry->vards_uzvards ?: '—' }},
                                                                                            skaits: {{ rtrim(rtrim(number_format((float) $entry->skaits, 2, '.', ''), '0'), '.') }})
                                                                                        @endif
                                                                                    </span>
                                                                                @endforeach
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    @if(!$pieprasijums->completed)
                    <div class="col-lg-5">
                        <div class="pp-card mb-3">
                            <div class="pp-card-header d-flex justify-content-between align-items-center px-3 py-2">
                                <strong>Neapstrādātie pasūtījumi ({{ $availablePasutijumi->total() }})</strong>
                                @if(!$pieprasijums->completed)
                                    <button class="btn btn-sm btn-outline-primary" type="submit" form="syncPasutijumiForm">Saglabāt pasūtījumu sarakstu</button>
                                @endif
                            </div>
                            <div class="table-responsive" style="max-height: 360px;">
                                <table class="table table-sm table-bordered mb-0 pp-subtable">
                                    <thead>
                                        <tr>
                                            <th style="width:42px;">+</th>
                                            <th>Pasūt. nr.</th>
                                            <th>Zāles</th>
                                            <th>Skaits</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($availablePasutijumi as $p)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" form="syncPasutijumiForm" name="pasutijumi[]" value="{{ $p->id }}" {{ $pieprasijums->completed ? 'disabled' : '' }}>
                                                </td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-link btn-sm p-0 edit-pasutijums-btn"
                                                        data-id="{{ $p->id }}"
                                                        data-pasutijuma_numurs="{{ e($p->pasutijuma_numurs ?: '—') }}"
                                                        data-skaits="{{ $p->skaits }}"
                                                        data-arsts="{{ e($p->arsts ?? '') }}"
                                                        data-arstniecibas_iestade="{{ e($p->arstniecibas_iestade ?? '') }}"
                                                        data-artikula-id="{{ $p->artikula_id ?? '' }}"
                                                        data-artikula-label="{{ e($p->product?->nosaukums ?? '') }}"
                                                        data-without-arst="{{ $p->product?->without_arst ? 1 : 0 }}"
                                                        data-nemedikamenti="{{ $p->product?->nemedikamenti ? 1 : 0 }}"
                                                        data-farmaceita-nosaukums="{{ e($p->farmaceita_nosaukums ?? '') }}"
                                                        {{ $pieprasijums->completed ? 'disabled' : '' }}
                                                    >
                                                        {{ $p->pasutijuma_numurs ?: '—' }}
                                                    </button>
                                                </td>
                                                <td>{{ $p->product?->nosaukums ?? $p->farmaceita_nosaukums ?? '—' }}</td>
                                                <td>{{ rtrim(rtrim(number_format((float) $p->skaits, 2, '.', ''), '0'), '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center">Nav brīvu pasūtījumu.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-3 py-2 border-top d-flex justify-content-end">
                                {{ $availablePasutijumi->links() }}
                            </div>
                        </div>

                        <div class="pp-card">
                            <div class="pp-card-header px-3 py-2">
                                <strong>Pievienotie pasūtījumi ({{ $assignedPasutijumi->count() }})</strong>
                            </div>
                            <div class="table-responsive" style="max-height: 360px;">
                                <table class="table table-sm table-bordered mb-0 pp-subtable">
                                    <thead>
                                        <tr>
                                            <th style="width:42px;">✓</th>
                                            <th>Pasūt. nr.</th>
                                            <th>Zāles</th>
                                            <th>Skaits</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($assignedPasutijumi as $p)
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" form="syncPasutijumiForm" name="pasutijumi[]" value="{{ $p->id }}" checked {{ $pieprasijums->completed ? 'disabled' : '' }}>
                                                </td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-link btn-sm p-0 edit-pasutijums-btn"
                                                        data-id="{{ $p->id }}"
                                                        data-pasutijuma_numurs="{{ e($p->pasutijuma_numurs ?: '—') }}"
                                                        data-skaits="{{ $p->skaits }}"
                                                        data-arsts="{{ e($p->arsts ?? '') }}"
                                                        data-arstniecibas_iestade="{{ e($p->arstniecibas_iestade ?? '') }}"
                                                        data-artikula-id="{{ $p->artikula_id ?? '' }}"
                                                        data-artikula-label="{{ e($p->product?->nosaukums ?? '') }}"
                                                        data-without-arst="{{ $p->product?->without_arst ? 1 : 0 }}"
                                                        data-nemedikamenti="{{ $p->product?->nemedikamenti ? 1 : 0 }}"
                                                        data-farmaceita-nosaukums="{{ e($p->farmaceita_nosaukums ?? '') }}"
                                                        {{ $pieprasijums->completed ? 'disabled' : '' }}
                                                    >
                                                        {{ $p->pasutijuma_numurs ?: '—' }}
                                                    </button>
                                                </td>
                                                <td>{{ $p->product?->nosaukums ?? $p->farmaceita_nosaukums ?? '—' }}</td>
                                                <td>{{ rtrim(rtrim(number_format((float) $p->skaits, 2, '.', ''), '0'), '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center">Nav pievienotu pasūtījumu.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addBrivaisArtikulsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addBrivaisArtikulsForm" method="POST" action="{{ route('pasutijumu-pieprasijumi.brivie-artikuli.store', $pieprasijums) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Pievienot brīvo artikulu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Aizvērt"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Artikuls no saraksta</label>
                            <input type="text" id="ba_artikula_name" class="form-control" list="ba_artikuli_list" placeholder="Izvēlieties artikulu" required>
                            <input type="hidden" id="ba_artikula_id" name="artikula_id">
                            <datalist id="ba_artikuli_list">
                                @foreach($artikuli as $a)
                                    <option value="{{ $a->nosaukums }}" data-id="{{ $a->id }}" data-without-arst="{{ $a->without_arst ? 1 : 0 }}" data-nemedikamenti="{{ $a->nemedikamenti ? 1 : 0 }}"></option>
                                @endforeach
                            </datalist>
                            <div class="form-text">Brīvajam artikulam jāizvēlas kataloga artikuls.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Skaits</label>
                            <input type="number" step="0.01" min="0.01" id="ba_skaits" name="skaits" class="form-control" required>
                        </div>
                        <div class="mb-2" id="ba_iestade_row">
                            <label class="form-label">Ārstniecības iestāde</label>
                            <input type="text" id="ba_iestade" name="arstniecibas_iestade" class="form-control">
                        </div>
                        <div class="mb-2" id="ba_arsts_row">
                            <label class="form-label">Ārsts</label>
                            <input type="text" id="ba_arsts" name="arsts" class="form-control">
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

    <div class="modal fade" id="editBrivaisArtikulsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editBrivaisArtikulsForm" method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Labot brīvo artikulu <span id="eb_id_label"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Aizvērt"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Artikuls no saraksta</label>
                            <input type="text" id="eb_artikula_name" class="form-control" list="eb_artikuli_list" placeholder="Izvēlieties artikulu" required>
                            <input type="hidden" id="eb_artikula_id" name="artikula_id">
                            <datalist id="eb_artikuli_list">
                                @foreach($artikuli as $a)
                                    <option value="{{ $a->nosaukums }}" data-id="{{ $a->id }}" data-without-arst="{{ $a->without_arst ? 1 : 0 }}" data-nemedikamenti="{{ $a->nemedikamenti ? 1 : 0 }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Skaits</label>
                            <input type="number" step="0.01" min="0.01" id="eb_skaits" name="skaits" class="form-control" required>
                        </div>
                        <div class="mb-2" id="eb_iestade_row">
                            <label class="form-label">Ārstniecības iestāde</label>
                            <input type="text" id="eb_iestade" name="arstniecibas_iestade" class="form-control">
                        </div>
                        <div class="mb-2" id="eb_arsts_row">
                            <label class="form-label">Ārsts</label>
                            <input type="text" id="eb_arsts" name="arsts" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="submit" form="deleteBrivaisArtikulsForm" class="btn btn-danger" onclick="return confirm('Dzēst šo brīvo artikulu?')">Dzēst</button>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
                            <button type="submit" class="btn btn-primary">Saglabāt</button>
                        </div>
                    </div>
                </form>
                <form id="deleteBrivaisArtikulsForm" method="POST" action="" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPasutijumsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editPasutijumsForm" method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Labot pasūtījumu <span id="editPasutijumsNr"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Aizvērt"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Skaits</label>
                            <input type="number" step="0.01" min="0.01" id="ep_skaits" name="skaits" class="form-control" required>
                        </div>
                        <div class="mb-2" id="ep_iestade_row">
                            <label class="form-label">Ārstniecības iestāde</label>
                            <input type="text" id="ep_iestade" name="arstniecibas_iestade" class="form-control">
                        </div>
                        <div class="mb-2" id="ep_arsts_row">
                            <label class="form-label">Ārsts</label>
                            <input type="text" id="ep_arsts" name="arsts" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Artikuls no saraksta</label>
                            <input type="text" id="ep_artikula_name" class="form-control" list="ep_artikuli_list" placeholder="Izvēlieties vai ierakstiet artikulu">
                            <input type="hidden" id="ep_artikula_id" name="artikula_id">
                            <datalist id="ep_artikuli_list">
                                @foreach($artikuli as $a)
                                    <option value="{{ $a->nosaukums }}" data-id="{{ $a->id }}" data-without-arst="{{ $a->without_arst ? 1 : 0 }}" data-nemedikamenti="{{ $a->nemedikamenti ? 1 : 0 }}"></option>
                                @endforeach
                            </datalist>
                            <div class="form-text">Ja izvēlēts artikuls no saraksta, brīvās formas nosaukums netiek izmantots.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Zāļu nosaukums (brīvā forma)</label>
                            <input type="text" id="ep_farmaceita_nosaukums" name="farmaceita_nosaukums" class="form-control">
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
            const artikuliDoctorFieldsHiddenById = @json(
                $artikuli->mapWithKeys(fn ($a) => [
                    (string) $a->id => (bool) ($a->without_arst || $a->nemedikamenti),
                ])
            );
            if (document.getElementById('pp_edit_datums')) {
                flatpickr("#pp_edit_datums", {
                    dateFormat: "d/m/Y",
                    locale: "lv"
                });
            }

            const normalizeText = (value) => String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
            const findMatchedOption = (list, rawValue) => {
                const needle = normalizeText(rawValue);
                if (!needle) return null;
                return Array.from(list.options).find(opt => normalizeText(opt.value) === needle) || null;
            };
            const findPossibleOptions = (list, rawValue) => {
                const needle = normalizeText(rawValue);
                if (!needle) return [];
                return Array.from(list.options).filter(opt => {
                    const v = normalizeText(opt.value);
                    return v === needle || v.includes(needle) || needle.includes(v);
                });
            };

            function bindArtikulaPicker(config) {
                const {
                    artikulaNameInput,
                    artikulaIdInput,
                    freeNameInput,
                    artikuliList,
                    iestadeRow,
                    arstsRow,
                    iestadeInput,
                    arstsInput,
                } = config;

                function syncArtikulaId() {
                    const opt = findMatchedOption(artikuliList, artikulaNameInput.value);
                    const possible = findPossibleOptions(artikuliList, artikulaNameInput.value);
                    const foundId = opt ? (opt.dataset.id || '') : '';
                    artikulaIdInput.value = foundId;
                    let hideDoctorFields = false;
                    if (foundId !== '' && Object.prototype.hasOwnProperty.call(artikuliDoctorFieldsHiddenById, foundId)) {
                        hideDoctorFields = !!artikuliDoctorFieldsHiddenById[foundId];
                    } else {
                        hideDoctorFields = possible.some(option => option.dataset.withoutArst === '1' || option.dataset.nemedikamenti === '1');
                    }
                    if (iestadeRow) iestadeRow.style.display = hideDoctorFields ? 'none' : '';
                    if (arstsRow) arstsRow.style.display = hideDoctorFields ? 'none' : '';
                    if (hideDoctorFields) {
                        if (iestadeInput) iestadeInput.value = '';
                        if (arstsInput) arstsInput.value = '';
                    }
                }

                artikulaNameInput?.addEventListener('input', syncArtikulaId);
                artikulaNameInput?.addEventListener('change', syncArtikulaId);

                return { syncArtikulaId };
            }

            const modalEl = document.getElementById('editPasutijumsModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
            const form = document.getElementById('editPasutijumsForm');
            const nrLabel = document.getElementById('editPasutijumsNr');
            const skaitsInput = document.getElementById('ep_skaits');
            const iestadeInput = document.getElementById('ep_iestade');
            const arstsInput = document.getElementById('ep_arsts');
            const iestadeRow = document.getElementById('ep_iestade_row');
            const arstsRow = document.getElementById('ep_arsts_row');
            const artikulaNameInput = document.getElementById('ep_artikula_name');
            const artikulaIdInput = document.getElementById('ep_artikula_id');
            const freeNameInput = document.getElementById('ep_farmaceita_nosaukums');
            const artikuliList = document.getElementById('ep_artikuli_list');
            const actionTemplate = "{{ route('pasutijumu-pieprasijumi.pasutijumi.update', ['pieprasijums' => $pieprasijums->id, 'pasutijums' => '__PASUTIJUMS__']) }}";

            if (form && modal) {
                const { syncArtikulaId } = bindArtikulaPicker({
                    artikulaNameInput,
                    artikulaIdInput,
                    freeNameInput,
                    artikuliList,
                    iestadeRow,
                    arstsRow,
                    iestadeInput,
                    arstsInput,
                });

                document.querySelectorAll('.edit-pasutijums-btn').forEach((btn) => {
                    btn.addEventListener('click', function () {
                        const d = this.dataset;
                        form.action = actionTemplate.replace('__PASUTIJUMS__', d.id);
                        nrLabel.textContent = d.pasutijuma_numurs ? '(' + d.pasutijuma_numurs + ')' : '';
                        skaitsInput.value = d.skaits || '';
                        iestadeInput.value = d.arstniecibas_iestade || '';
                        arstsInput.value = d.arsts || '';
                        artikulaNameInput.value = d.artikulaLabel || '';
                        artikulaIdInput.value = d.artikulaId || '';
                        freeNameInput.value = d.farmaceitaNosaukums || '';
                        const hideDoctorFields = !!artikuliDoctorFieldsHiddenById[String(d.artikulaId || '')];
                        if (iestadeRow) iestadeRow.style.display = hideDoctorFields ? 'none' : '';
                        if (arstsRow) arstsRow.style.display = hideDoctorFields ? 'none' : '';
                        if (hideDoctorFields) {
                            iestadeInput.value = '';
                            arstsInput.value = '';
                        }
                        syncArtikulaId();
                        modal.show();
                    });
                });

                form.addEventListener('submit', function (e) {
                    syncArtikulaId();
                    const hasCatalog = (artikulaIdInput.value || '').trim() !== '';
                    const hasFreeName = (freeNameInput.value || '').trim() !== '';

                    if (!hasCatalog && !hasFreeName) {
                        e.preventDefault();
                        alert('Norādiet artikulu no saraksta vai ievadiet zāļu nosaukumu brīvā formā.');
                    }
                });
            }

            const brivaisForm = document.getElementById('addBrivaisArtikulsForm');
            if (brivaisForm) {
                const baIestadeInput = document.getElementById('ba_iestade');
                const baArstsInput = document.getElementById('ba_arsts');
                const baIestadeRow = document.getElementById('ba_iestade_row');
                const baArstsRow = document.getElementById('ba_arsts_row');
                const baArtikulaNameInput = document.getElementById('ba_artikula_name');
                const baArtikulaIdInput = document.getElementById('ba_artikula_id');
                const baArtikuliList = document.getElementById('ba_artikuli_list');

                const { syncArtikulaId: syncBrivaisArtikulaId } = bindArtikulaPicker({
                    artikulaNameInput: baArtikulaNameInput,
                    artikulaIdInput: baArtikulaIdInput,
                    freeNameInput: null,
                    artikuliList: baArtikuliList,
                    iestadeRow: baIestadeRow,
                    arstsRow: baArstsRow,
                    iestadeInput: baIestadeInput,
                    arstsInput: baArstsInput,
                });

                brivaisForm.addEventListener('submit', function (e) {
                    syncBrivaisArtikulaId();
                    const hasCatalog = (baArtikulaIdInput.value || '').trim() !== '';
                    if (!hasCatalog) {
                        e.preventDefault();
                        alert('Brīvajam artikulam izvēlieties artikulu no saraksta.');
                    }
                });
            }

            const editBrivaisModalEl = document.getElementById('editBrivaisArtikulsModal');
            const editBrivaisModal = editBrivaisModalEl ? new bootstrap.Modal(editBrivaisModalEl) : null;
            const editBrivaisForm = document.getElementById('editBrivaisArtikulsForm');
            const deleteBrivaisForm = document.getElementById('deleteBrivaisArtikulsForm');
            const editBrivaisIdLabel = document.getElementById('eb_id_label');
            const ebSkaitsInput = document.getElementById('eb_skaits');
            const ebIestadeInput = document.getElementById('eb_iestade');
            const ebArstsInput = document.getElementById('eb_arsts');
            const ebIestadeRow = document.getElementById('eb_iestade_row');
            const ebArstsRow = document.getElementById('eb_arsts_row');
            const ebArtikulaNameInput = document.getElementById('eb_artikula_name');
            const ebArtikulaIdInput = document.getElementById('eb_artikula_id');
            const ebArtikuliList = document.getElementById('eb_artikuli_list');
            const editBrivaisActionTemplate = "{{ route('pasutijumu-pieprasijumi.brivie-artikuli.update', ['pieprasijums' => $pieprasijums->id, 'brivaisArtikuls' => '__BRIVAIS__']) }}";
            const deleteBrivaisActionTemplate = "{{ route('pasutijumu-pieprasijumi.brivie-artikuli.destroy', ['pieprasijums' => $pieprasijums->id, 'brivaisArtikuls' => '__BRIVAIS__']) }}";

            if (editBrivaisForm && deleteBrivaisForm && editBrivaisModal) {
                const { syncArtikulaId: syncEditBrivaisArtikulaId } = bindArtikulaPicker({
                    artikulaNameInput: ebArtikulaNameInput,
                    artikulaIdInput: ebArtikulaIdInput,
                    freeNameInput: null,
                    artikuliList: ebArtikuliList,
                    iestadeRow: ebIestadeRow,
                    arstsRow: ebArstsRow,
                    iestadeInput: ebIestadeInput,
                    arstsInput: ebArstsInput,
                });

                document.querySelectorAll('.edit-brivais-artikuls-btn').forEach((btn) => {
                    btn.addEventListener('click', function () {
                        const d = this.dataset;
                        editBrivaisForm.action = editBrivaisActionTemplate.replace('__BRIVAIS__', d.id);
                        deleteBrivaisForm.action = deleteBrivaisActionTemplate.replace('__BRIVAIS__', d.id);
                        editBrivaisIdLabel.textContent = '#' + d.id;
                        ebSkaitsInput.value = d.skaits || '';
                        ebIestadeInput.value = d.arstniecibas_iestade || '';
                        ebArstsInput.value = d.arsts || '';
                        ebArtikulaNameInput.value = d.artikulaLabel || '';
                        ebArtikulaIdInput.value = d.artikulaId || '';
                        syncEditBrivaisArtikulaId();
                        editBrivaisModal.show();
                    });
                });

                editBrivaisForm.addEventListener('submit', function (e) {
                    syncEditBrivaisArtikulaId();
                    if ((ebArtikulaIdInput.value || '').trim() === '') {
                        e.preventDefault();
                        alert('Brīvajam artikulam izvēlieties artikulu no saraksta.');
                    }
                });
            }
        });
    </script>
</x-app-layout>
