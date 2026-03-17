<div class="modal fade" id="pasutijumiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:40%;">
        <div class="modal-content">
            <form id="pasutijumiForm" method="POST" action="">
                @csrf
                <input type="hidden" id="form_method" name="_method" value="POST">

                <input type="hidden" name="return_url" value="{{ url()->full() }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Pasūtījums</h5>
                    <button type="button" class="btn btn-success" id="m_complete_btn" style="display:none;">
                        Izpildīt
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="status_filter" value="{{ request('status_filter', 'neizpildits') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                    <!-- other fields (datums, artikuls, skaits, ...) -->
                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Pieprasījuma datums</label>
                        <input type="text" id="m_datums" name="datums"
                            class="form-control"
                            style="width:160px;"
                            placeholder="DD/MM/YYYY" required>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Zāļu nosaukums</label>
                        <input id="m_artikula_name" type="text" class="form-control" list="m_artikuli" placeholder="Rakstiet artikula nosaukumu" style="flex:1;" required>
                        <input type="hidden" id="m_artikula_id" name="artikula_id">
                        <datalist id="m_artikuli">
                            @foreach($artikuli as $a)
                                <option value="{{ $a->nosaukums }}" data-id="{{ $a->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Skaits</label>
                        <input id="m_skaits" type="number" name="skaits"
                        min="0" step="0.01"
                        class="form-control" style="width:120px;" required>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Pasūtījuma numurs</label>
                        <input id="m_pasutijuma_numurs" type="text" name="pasutijuma_numurs" class="form-control" style="flex:1;">
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Receptes numurs</label>
                        <input id="m_receptes_numurs" type="text" name="receptes_numurs" class="form-control" style="flex:1;">
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Vārds, Uzvārds</label>
                        <input id="m_vards_uzvards" type="text" name="vards_uzvards" class="form-control" style="flex:1;" required>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Tālrunis, e-pasts</label>
                        <input id="m_talrunis_epasts" type="text" name="talrunis_epasts" class="form-control" style="flex:1;">
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Pasūtījuma datums</label>
                        <input type="text" id="m_pasutijuma_datums" name="pasutijuma_datums"
                            class="form-control"
                            style="width:160px;"
                            placeholder="DD/MM/YYYY">
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Komentāri</label>
                        <textarea id="m_komentari" name="komentari" class="form-control" rows="2" style="flex:1;"></textarea>
                    </div>
                    
                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Statuss</label>
                        <select id="m_statuss" name="statuss" class="form-control" style="width:200px;">
                            <option value="neizpildits">NeizpildĪts</option>
                            <option value="izpildits">IzpildĪts</option>
                            <option value="atcelts">Atcelts</option>
                        </select>
                    </div>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Atcelt</button>
                        <button type="submit" class="btn btn-primary" id="m_save_btn">Saglabāt</button>
                    </div>
            </form>
        </div>
    </div>
</div>
