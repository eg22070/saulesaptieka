<x-app-layout>
    @php
        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
    @endphp
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

    <div class="py-4">
        <div class="container" style="width:90%; max-width:2200px; margin:0 auto;">
            <form id="pasSearchForm" method="GET" action="{{ route('pasutijumi.index') }}" class="mb-3">
                <div class="input-group">
                    <input id="pasSearchInput" name="search" type="text" class="form-control"
                           placeholder="Meklēt pēc zāļu nosaukuma, Pasūt. nr., Receptes nr., Vārds uzvārds, tālr./e-pasts"
                           value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">Meklēt</button>
                </div>

                <div class="mt-2 d-flex align-items-center">
                    <label class="me-2" style="margin-right:12px;">Statuss:</label>
                    @php
                        $currentStatusFilter = request('status_filter', 'all');
                    @endphp
                    <select id="pas_status_filter" name="status_filter" class="form-select form-select-sm" style="width:220px; margin-right:12px;">
                        <option value="all"           {{ $currentStatusFilter === 'all'           ? 'selected' : '' }}>Visi</option>
                        <option value="neizpildits"   {{ $currentStatusFilter === 'neizpildits'   ? 'selected' : '' }}>Neizpildītie</option>
                        <option value="done"          {{ $currentStatusFilter === 'done'          ? 'selected' : '' }}>Izpildītie &amp; Atceltie</option>
                    </select>

                    <label class="me-2" style="margin-right:12px;">Datumu filtra diapazons:</label>
                    <input id="pas_date_range" class="form-control form-control-sm" style="max-width:320px;"
                           placeholder="Izvēlieties datumus"
                           value="{{ request('date_from') && request('date_to') ? request('date_from').' - '.request('date_to') : '' }}">
                    <input type="hidden" id="pas_date_from" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" id="pas_date_to" name="date_to" value="{{ request('date_to') }}">
                    @if($isSpecialUser)
                        <label class="me-2 ms-3" style="margin-right:12px;">Rādīt:</label>
                        <select id="pas_mine_filter" name="mine_filter" class="form-select form-select-sm" style="width:220px; margin-right:12px;">
                            <option value="all"  {{ request('mine_filter', 'all') === 'all' ? 'selected' : '' }}>Visi pasūtījumi</option>
                            <option value="mine" {{ request('mine_filter') === 'mine' ? 'selected' : '' }}>Dina</option>
                        </select>
                    @endif
                    <a href="{{ route('pasutijumi.index', ['status_filter' => 'all']) }}" class="btn btn-sm btn-outline-secondary ms-2">
                        Atiestatīt
                    </a>
                </div>
            </form>
            @if(auth()->check() && strtolower(auth()->user()->role) !== 'farmaceiti')
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <button class="btn btn-primary" id="openCreateModal">Pievienot Pasūtījumu</button>
                </div>
            </div>
            @elseif(auth()->check() && strtolower(auth()->user()->role) === 'farmaceiti')
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <button type="button" class="btn btn-primary" id="openKvitsModal">Jauna kvīts</button>
                </div>
            </div>
            @endif
            <div id="searchResults">
                @include('partials.pasutijumi-table', ['pasutijumi' => $pasutijumi, 'artikuli' => $artikuli])
            </div>
        </div>
    </div>

    @include('partials.pasutijumi-modal')
    @if(auth()->check() && strtolower(auth()->user()->role) === 'farmaceiti')
        @include('partials.pasutijumi-kvits-modal')
    @endif

    <script>
    const defaultPreviousFriday = "{{ \Carbon\Carbon::now()->subWeek()->startOfWeek()->addDays(4)->format('d/m/Y') }}";
    const artikuliDoctorFieldsHiddenById = @json(
        $artikuli->mapWithKeys(fn ($a) => [
            (string) $a->id => (bool) ($a->without_arst || $a->nemedikamenti),
        ])
    );
    
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr("#pas_date_range", {
            mode: "range",
            dateFormat: "d/m/Y",
            locale: "lv",
            defaultDate: {!! (request('date_from') && request('date_to')) ? "['".request('date_from')."','".request('date_to')."']" : 'null' !!},
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    const pad = n => String(n).padStart(2,'0');
                    const from = selectedDates[0], to = selectedDates[1];
                    const fromStr = pad(from.getDate()) + '/' + pad(from.getMonth()+1) + '/' + from.getFullYear();
                    const toStr   = pad(to.getDate())   + '/' + pad(to.getMonth()+1)   + '/' + to.getFullYear();
                    document.getElementById('pas_date_from').value = fromStr;
                    document.getElementById('pas_date_to').value = toStr;
                    document.getElementById('pasSearchForm').submit();
                }
            }
        });
        flatpickr("#m_datums", {
            dateFormat: "d/m/Y"
        });
        flatpickr("#m_pasutijuma_datums", {
            dateFormat: "d/m/Y"
        });
        let debounce;
        const searchInput = document.getElementById('pasSearchInput');
        searchInput?.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                const params = new URLSearchParams({
                    search: this.value,
                    status_filter: document.getElementById('pas_status_filter')?.value || 'all',
                    date_from: document.getElementById('pas_date_from').value || '',
                    date_to: document.getElementById('pas_date_to').value || '',
                    mine_filter: document.getElementById('pas_mine_filter')?.value || 'all',
                    sort: "{{ request('sort', 'datums') }}",
                    direction: "{{ request('direction', 'desc') }}",
                });
                const url = "{{ route('pasutijumi.index') }}?" + params.toString();
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        document.getElementById('searchResults').innerHTML = html;
                        bindPasutijumiModalHandlers();
                    })
                    .catch(()=>{});
            }, 300);
        });

        document.getElementById('pas_status_filter')?.addEventListener('change', function () {
            document.getElementById('pasSearchForm').submit();
        });
        document.getElementById('pas_mine_filter')?.addEventListener('change', function () {
            document.getElementById('pasSearchForm').submit();
        });

        bindPasutijumiModalHandlers();
        bindKvitsModalHandlers();
    });

    function bindKvitsModalHandlers() {
        const modalEl = document.getElementById('pasutijumiKvitsModal');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('pasutijumiKvitsForm');
        const nameInput = document.getElementById('kv_artikula_name');
        const idInput = document.getElementById('kv_artikula_id');
        const datalist = document.getElementById('kv_artikuli');
        const talInput = document.getElementById('kv_talrunis');
        const kvIestadeRow = document.getElementById('kv_arstniecibas_iestade_row');
        const kvArstsRow = document.getElementById('kv_arsts_row');
        const kvIestadeInput = document.getElementById('kv_arstniecibas_iestade');
        const kvArstsInput = document.getElementById('kv_arsts');

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
        const resolveKvitsSelectedOption = () => {
            if (!nameInput || !datalist) return null;
            return findMatchedOption(datalist, nameInput.value);
        };

        /** Sync HTML5 constraint validation (same native bubble as “Please fill out this field”). */
        function syncKvitsTalrunisConstraint() {
            if (!talInput) return;
            const n = String(talInput.value || '').replace(/\D/g, '').length;
            if (n >= 1 && n < 8) {
                talInput.setCustomValidity('Lūdzu, ievadiet vismaz 8 ciparus.');
            } else {
                talInput.setCustomValidity('');
            }
        }

        talInput?.addEventListener('input', syncKvitsTalrunisConstraint);
        talInput?.addEventListener('blur', syncKvitsTalrunisConstraint);

        function updateKvitsDoctorFieldsVisibility() {
            if (!nameInput || !datalist) return;
            const selectedOption = resolveKvitsSelectedOption();
            if (idInput) {
                idInput.value = selectedOption ? (selectedOption.dataset.id || selectedOption.getAttribute('data-id') || '') : '';
            }
            const selectedId = (idInput?.value || '').trim();
            let hideDoctorFields = true; // Hidden by default until a valid catalog item is selected.
            if (selectedOption) {
                const withoutArst = selectedOption.dataset.withoutArst === '1';
                const nemedikamenti = selectedOption.dataset.nemedikamenti === '1';
                hideDoctorFields = withoutArst || nemedikamenti;
            } else if (selectedId !== '' && Object.prototype.hasOwnProperty.call(artikuliDoctorFieldsHiddenById, selectedId)) {
                hideDoctorFields = !!artikuliDoctorFieldsHiddenById[selectedId];
            }
            if (kvIestadeRow) kvIestadeRow.style.display = hideDoctorFields ? 'none' : 'flex';
            if (kvArstsRow) kvArstsRow.style.display = hideDoctorFields ? 'none' : 'flex';
            if (hideDoctorFields) {
                if (kvIestadeInput) kvIestadeInput.value = '';
                if (kvArstsInput) kvArstsInput.value = '';
            }
        }

        if (nameInput && datalist && idInput) {
            const syncKvitsArtikuls = function () {
                const opt = findMatchedOption(datalist, this.value);
                idInput.value = opt ? (opt.dataset.id || opt.getAttribute('data-id') || '') : '';
                updateKvitsDoctorFieldsVisibility();
            };
            nameInput.addEventListener('input', syncKvitsArtikuls);
            nameInput.addEventListener('change', syncKvitsArtikuls);
            nameInput.addEventListener('blur', syncKvitsArtikuls);
        }

        document.getElementById('openKvitsModal')?.addEventListener('click', function (e) {
            e.preventDefault();
            form?.reset();
            if (idInput) idInput.value = '';
            if (nameInput) nameInput.value = '';
            if (kvIestadeInput) kvIestadeInput.value = '';
            if (kvArstsInput) kvArstsInput.value = '';
            syncKvitsTalrunisConstraint();
            updateKvitsDoctorFieldsVisibility();
            modal.show();
        });
    }

    function bindPasutijumiModalHandlers() {
        const modalEl = document.getElementById('pasutijumiModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const form = document.getElementById('pasutijumiForm');
        const methodInput = document.getElementById('form_method');

        const m_datums = document.getElementById('m_datums');
        const m_artikula_name = document.getElementById('m_artikula_name');
        const m_artikula_id = document.getElementById('m_artikula_id');
        const m_skaits = document.getElementById('m_skaits');
        const m_pasutijuma_numurs = document.getElementById('m_pasutijuma_numurs');
        const m_receptes_numurs = document.getElementById('m_receptes_numurs');
        const m_vards_uzvards = document.getElementById('m_vards_uzvards');
        const m_talrunis_epasts = document.getElementById('m_talrunis_epasts');
        const m_arstniecibas_iestade = document.getElementById('m_arstniecibas_iestade');
        const m_arsts = document.getElementById('m_arsts');
        const m_arstniecibas_iestade_row = document.getElementById('m_arstniecibas_iestade_row');
        const m_arsts_row = document.getElementById('m_arsts_row');
        const m_pasutijuma_datums = document.getElementById('m_pasutijuma_datums');
        const m_komentari = document.getElementById('m_komentari');
        const m_statuss = document.getElementById('m_statuss');
        const m_hide_from_visiem = document.getElementById('m_hide_from_visiem');
        const m_complete_btn = document.getElementById('m_complete_btn');
        const datalist = document.getElementById('m_artikuli');
        const m_zalu_free_row = document.getElementById('m_zalu_free_row');
        const m_farmaceita_nosaukums = document.getElementById('m_farmaceita_nosaukums');

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

        function setPasutijumiZaluCatalogOnly() {
            if (m_zalu_free_row) m_zalu_free_row.style.display = 'none';
            if (m_farmaceita_nosaukums) {
                m_farmaceita_nosaukums.disabled = true;
                m_farmaceita_nosaukums.removeAttribute('required');
                m_farmaceita_nosaukums.value = '';
            }
            if (m_artikula_name) m_artikula_name.setAttribute('required', 'required');
        }

        function setPasutijumiZaluFreeWithCatalogPick() {
            if (m_zalu_free_row) m_zalu_free_row.style.display = 'block';
            if (m_farmaceita_nosaukums) {
                m_farmaceita_nosaukums.disabled = false;
                m_farmaceita_nosaukums.setAttribute('required', 'required');
            }
            if (m_artikula_name) m_artikula_name.removeAttribute('required');
        }

        function syncPasutijumiArtikulaFromDatalist() {
            if (!m_artikula_name || !datalist || !m_artikula_id) return;
            const opt = findMatchedOption(datalist, m_artikula_name.value);
            const possible = findPossibleOptions(datalist, m_artikula_name.value);
            const foundId = opt ? (opt.dataset.id || opt.getAttribute('data-id') || '') : '';
            m_artikula_id.value = foundId;
            let hideDoctorFields = false;
            if (foundId !== '' && Object.prototype.hasOwnProperty.call(artikuliDoctorFieldsHiddenById, foundId)) {
                hideDoctorFields = !!artikuliDoctorFieldsHiddenById[foundId];
            } else {
                const withoutArst = possible.some(option => option.dataset.withoutArst === '1');
                const nemedikamenti = possible.some(option => option.dataset.nemedikamenti === '1');
                hideDoctorFields = withoutArst || nemedikamenti;
            }
            const freeVisible = m_zalu_free_row && m_zalu_free_row.style.display !== 'none';
            if (freeVisible && foundId) {
                setPasutijumiZaluCatalogOnly();
                if (m_farmaceita_nosaukums) m_farmaceita_nosaukums.value = '';
            }
            if (m_arstniecibas_iestade_row) m_arstniecibas_iestade_row.style.display = hideDoctorFields ? 'none' : '';
            if (m_arsts_row) m_arsts_row.style.display = hideDoctorFields ? 'none' : '';
            if (hideDoctorFields) {
                if (m_arstniecibas_iestade) m_arstniecibas_iestade.value = '';
                if (m_arsts) m_arsts.value = '';
            }
        }

        if (m_artikula_name && datalist) {
            m_artikula_name.addEventListener('input', syncPasutijumiArtikulaFromDatalist);
            m_artikula_name.addEventListener('change', syncPasutijumiArtikulaFromDatalist);
        }

        document.addEventListener('click', function (e) {
            if (e.target && e.target.id === 'openCreateModal') {
                e.preventDefault();
                form.reset();
                if (methodInput) methodInput.value = 'POST';
                form.action = "{{ route('pasutijumi.store') }}";

                if (m_artikula_id) m_artikula_id.value = '';
                if (m_artikula_name) m_artikula_name.value = '';
                setPasutijumiZaluCatalogOnly();
                if (m_arstniecibas_iestade_row) m_arstniecibas_iestade_row.style.display = '';
                if (m_arsts_row) m_arsts_row.style.display = '';

                // set default previous Friday for Pieprasījuma datums
                if (m_datums) m_datums.value = defaultPreviousFriday;

                // clear/optional for pasūtījuma datums
                if (m_pasutijuma_datums) m_pasutijuma_datums.value = '';
                if (m_hide_from_visiem) m_hide_from_visiem.checked = false;

                if (modal) modal.show();
            }
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const d = this.dataset;
                form.reset();
                if (methodInput) methodInput.value = 'PUT';
                form.action = "{{ url('pasutijumi') }}/" + d.id;
                if (m_datums) m_datums.value = d.datums || '';
                const artikulaIdRaw = this.getAttribute('data-artikula-id');
                const hasArtikulaId = artikulaIdRaw !== null && artikulaIdRaw !== '';
                if (hasArtikulaId) {
                    setPasutijumiZaluCatalogOnly();
                    if (m_artikula_id) m_artikula_id.value = artikulaIdRaw;
                    if (m_artikula_name) {
                        m_artikula_name.value = this.getAttribute('data-artikula-label') || '';
                    }
                    const hideDoctorFields = !!artikuliDoctorFieldsHiddenById[String(artikulaIdRaw)];
                    if (m_arstniecibas_iestade_row) m_arstniecibas_iestade_row.style.display = hideDoctorFields ? 'none' : '';
                    if (m_arsts_row) m_arsts_row.style.display = hideDoctorFields ? 'none' : '';
                } else {
                    setPasutijumiZaluFreeWithCatalogPick();
                    if (m_artikula_id) m_artikula_id.value = '';
                    if (m_artikula_name) m_artikula_name.value = '';
                    if (m_farmaceita_nosaukums) {
                        m_farmaceita_nosaukums.value = this.getAttribute('data-farmaceita-nosaukums') || '';
                    }
                    if (m_arstniecibas_iestade_row) m_arstniecibas_iestade_row.style.display = '';
                    if (m_arsts_row) m_arsts_row.style.display = '';
                }
                if (m_skaits) m_skaits.value = d.skaits || 1;
                if (m_pasutijuma_numurs) m_pasutijuma_numurs.value = d.pasutijuma_numurs || '';
                if (m_receptes_numurs) m_receptes_numurs.value = d.receptes_numurs || '';
                if (m_vards_uzvards) m_vards_uzvards.value = d.vards_uzvards || '';
                if (m_talrunis_epasts) m_talrunis_epasts.value = d.talrunis_epasts || '';
                if (m_arstniecibas_iestade) m_arstniecibas_iestade.value = d.arstniecibas_iestade || '';
                if (m_arsts) m_arsts.value = d.arsts || '';
                syncPasutijumiArtikulaFromDatalist();
                if (m_pasutijuma_datums) m_pasutijuma_datums.value = d.pasutijuma_datums || '';
                if (m_komentari) m_komentari.value = d.komentari || '';
                if (m_hide_from_visiem) m_hide_from_visiem.checked = (d.hide_from_visiem === '1' || d.hide_from_visiem === 1);
                if (m_statuss) {
                    const currentStatus = (d.statuss || 'neizpildits').toLowerCase();
                    m_statuss.value = currentStatus;

                    // Show "Izpildīt" for neizpildīti un neapstrādāti
                    if (m_complete_btn) {
                        const canComplete = currentStatus === 'neizpildits' || currentStatus === 'neapstradats';
                        m_complete_btn.style.display = canComplete ? '' : 'none';
                    }
                }

                // If you want status select itself to also control visibility when changed manually:
                if (m_statuss && m_complete_btn) {
                    m_statuss.onchange = function () {
                        const val = (this.value || '').toLowerCase();
                        const canComplete = val === 'neizpildits' || val === 'neapstradats';
                        m_complete_btn.style.display = canComplete ? '' : 'none';
                    };
                }

                if (m_complete_btn && form && m_statuss) {
                    m_complete_btn.onclick = function () {
                        // Set status to izpildits and submit
                        m_statuss.value = 'izpildits';
                        // Optionally hide the button before submit
                        this.style.display = 'none';
                        form.submit();
                    };
                }
                if (modal) modal.show();
            });
        });

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('row-checkbox')) {
                const any = document.querySelectorAll('.row-checkbox:checked').length > 0;
                const btn = document.getElementById('bulkCompleteBtn');
                if (btn) btn.disabled = !any;
            }
        });
    }
    </script>

</x-app-layout>
