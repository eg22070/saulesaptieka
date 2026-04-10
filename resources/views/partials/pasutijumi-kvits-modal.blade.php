<div class="modal fade" id="pasutijumiKvitsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:40%;">
        <div class="modal-content">
            <form id="pasutijumiKvitsForm" method="POST" action="{{ route('pasutijumi.kvits') }}">
                @csrf
                @php
                    $kvitsReturnParams = array_filter([
                        'status_filter' => 'neapstradats',
                        'search' => request('search'),
                        'date_from' => request('date_from'),
                        'date_to' => request('date_to'),
                        'sort' => request('sort'),
                        'direction' => request('direction'),
                        'mine_filter' => request('mine_filter'),
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                <input type="hidden" name="return_url" value="{{ route('pasutijumi.index', $kvitsReturnParams) }}">

                <div class="modal-header">
                    <h5 class="modal-title">Jauna kvīts</h5>
                </div>
                <div class="modal-body">

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Vārds, Uzvārds</label>
                        <input id="kv_vards_uzvards" type="text" name="vards_uzvards" class="form-control" style="flex:1;" required>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Zāļu nosaukums</label>
                        <input id="kv_artikula_name" name="farmaceita_nosaukums" type="text" class="form-control" list="kv_artikuli"
                               placeholder="Izvēlieties no saraksta vai ierakstiet brīvi" style="flex:1;" required>
                        <input type="hidden" id="kv_artikula_id" name="artikula_id" value="">
                        <datalist id="kv_artikuli">
                            @foreach($artikuli as $a)
                                <option value="{{ $a->nosaukums }}" data-id="{{ $a->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Skaits</label>
                        <input id="kv_skaits" type="number" name="skaits" min="0" step="0.01" class="form-control" style="width:120px;" required>
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Receptes Nr.</label>
                        <input id="kv_receptes_numurs" type="text" name="receptes_numurs" class="form-control" style="flex:1;">
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Tālrunis</label>
                        <input id="kv_talrunis" type="text" name="talrunis" class="form-control" style="flex:1;" required
                               inputmode="tel" autocomplete="tel">
                    </div>
                    <p class="text-muted small mb-2" style="margin-left:143px;">
                        Vismaz 8 cipari (atstarpes un rakstzīmes netiek skaitītas).
                    </p>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">E-pasts <span class="text-muted fw-normal">(nav obligāts)</span></label>
                        <input id="kv_epasts" type="text" name="epasts" class="form-control" style="flex:1;">
                    </div>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="me-3" style="width:130px;">Komentāri <span class="text-muted fw-normal">(nav obligāts)</span></label>
                        <textarea id="kv_komentari" name="komentari" class="form-control" rows="2" style="flex:1;"></textarea>
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
