        {{ $products->links() }}
<table class="table custom-artikuli-table">
            <thead>
                @php
                    $role = strtolower(auth()->user()->role ?? '');
                @endphp
                <tr>
                    @if($role === 'farmaceiti')
                    <th style="width: 5%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ATĶ</th>
                    <th style="width: 20%; border: 1px solid #080000ff; padding: 4px; text-align: center;">SNN</th>
                    <th style="width: 25%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
                    <th style="width: 15%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Info</th>
                    <th style="width: 20%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Pielietojums</th>
                    <th style="width: 15%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Īpašās atzīmes</th>
                    @else
                    <th style="width: 25%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Nosaukums</th>
                    <th style="width: 10%; border: 1px solid #080000ff; padding: 4px; text-align: center;">ID numurs</th>
                    <th style="width: 5%;  border: 1px solid #080000ff; padding: 4px; text-align: center;">Valsts</th>
                    <th style="width: 25%; border: 1px solid #080000ff; padding: 4px; text-align: center;">SNN</th>
                    <th style="width: 14%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Analogs</th>
                    <th style="width: 15%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Īpašās atzīmes</th>
                    <th style="width: 6%; border: 1px solid #080000ff; padding: 4px; text-align: center;">Darbības</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $artikuls)
                    @php
                        $roleIsBrivibas = $role === 'brivibas';

                        // Visibility categories based on hide flags (brivibas only).
                        if ($roleIsBrivibas) {
                            $hideFromKruzes = (bool) ($artikuls->hide_from_kruzes ?? false);
                            $hideFromFarmaceiti = (bool) ($artikuls->hide_from_farmaceiti ?? false);

                            if (!$hideFromKruzes && !$hideFromFarmaceiti) {
                                $visibilityClass = 'vis-everyone';
                                $bgOdd = '#f1eae3';
                                $bgEven = '#f1eae3';
                                $detailBg = '#f8f9fa';
                            } elseif (!$hideFromKruzes && $hideFromFarmaceiti) {
                                // Only Kruzes can see
                                $visibilityClass = 'vis-kruzes-only';
                                $bgOdd = '#f2fbff';
                                $bgEven = '#e4f6ff';
                                $detailBg = '#e9faff';
                            } elseif ($hideFromKruzes && !$hideFromFarmaceiti) {
                                // Only Farmaceiti can see
                                $visibilityClass = 'vis-farmaceiti-only';
                                $bgOdd = '#fff6ee';
                                $bgEven = '#ffeede';
                                $detailBg = '#fff0dc';
                            } else {
                                // Hidden from both (should normally not appear)
                                $visibilityClass = 'vis-hidden';
                                $bgOdd = '#fafafa';
                                $bgEven = '#f2f2f2';
                                $detailBg = '#f6f6f6';
                            }
                        } else {
                            // Match pieprasijumi default row look for non-brivibas roles.
                            $visibilityClass = '';
                            $bgOdd = '#f1eae3';
                            $bgEven = '#f1eae3';
                            $detailBg = '#f8f9fa';
                        }
                    @endphp

                    <tr class="artikuli-row {{ $roleIsBrivibas ? $visibilityClass : '' }}" style="background-color: {{ $loop->odd ? $bgOdd : $bgEven }};">
                    @if($role === 'farmaceiti')    
                        @php
                            $atkValidityDays = (int) ($artikuls->atk_validity_days ?? 90);
                            $atkBg = $atkValidityDays === 90 ? '#ffcccc' : '#ccffcc';
                            $atkText = $atkValidityDays === 90 ? '#7a0000' : '#006600';
                        @endphp
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle; background-color: {{ $atkBg }}; color: {{ $atkText }};">
                            {{ $artikuls->atk }}
                        </td>
                        <td class="snn-cell" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">
                            {!! nl2br(e($artikuls->snn)) !!}
                        </td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">
                            <b>{{ $artikuls->nosaukums }}</b>
                        </td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->info }}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->pielietojums }}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->atzimes }}</td>
                    @else
                        <td class="{{ $role === 'brivibas' ? 'toggle-details' : '' }}"
                            style="border:1px solid #080000ff; padding:4px; cursor: {{ $role === 'brivibas' ? 'pointer' : 'default' }};"
                            title="{{ $role === 'brivibas' ? 'Klikšķiniet, lai redzētu detaļas' : '' }}">
                            <b>{{ $artikuls->nosaukums }}</b>
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->id_numurs }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->valsts }}</td>
                        <td class="snn-cell" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">
                            {!! nl2br(e($artikuls->snn)) !!}
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->analogs }}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->atzimes }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                             @if($role !== 'farmaceiti')
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
                                        @if($role === 'brivibas')
                                            data-atk="{{ $artikuls->atk }}"
                                            data-atk_validity_days="{{ $artikuls->atk_validity_days }}"
                                            data-info="{{ $artikuls->info }}"
                                            data-pielietojums="{{ $artikuls->pielietojums }}"
                                            data-hide_from_farmaceiti="{{ $artikuls->hide_from_farmaceiti ? 1 : 0 }}"
                                            data-hide_from_kruzes="{{ $artikuls->hide_from_kruzes ? 1 : 0 }}"
                                        @endif
                                        data-bs-toggle="modal" data-bs-target="#artikuliModal">
                                Labot</button>
                                <form action="{{ route('artikuli.destroy', $artikuls->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Vai tiešām vēlaties izdzēst šo artikulu?')">Dzēst</button>
                                </form>
                            @endif   
                        </td>
                    @endif
                </tr>           
                    {{-- Additional info for brivibas only --}}
                @if($role === 'brivibas')
                    <tr class="additional-info {{ $visibilityClass }}" style="display:none;">
                        <td colspan="7" style="background-color:{{ $detailBg }}; border:1px solid #080000ff;">
                            <div style="padding:10px;">
                                <strong>ATĶ:</strong> {{ $artikuls->atk ?: '-' }}<br>
                                <strong>Info:</strong> {{ $artikuls->info ?: '-' }}<br>
                                <strong>Pielietojums:</strong> {{ $artikuls->pielietojums ?: '-' }}<br>
                                <strong>Paslēpts no Krūzes ielas:</strong> {{ $artikuls->hide_from_kruzes ? 'Jā' : 'Nē' }}<br>
                                <strong>Paslēpts no farmaceitiem:</strong> {{ $artikuls->hide_from_farmaceiti ? 'Jā' : 'Nē' }}
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7">Netika atrasti artikuli!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $products->links() }}
<style>
    .custom-artikuli-table thead th {
        position: sticky;
        top: 0;
        z-index: 2; 
        background-color: #373330;
        color: #ffffff;
    }

    /* Match the pieprasijumi “nosaukums” hover styling */
    .toggle-details:hover {
        color: #0d6efd; /* Bootstrap primary blue */
    }

    @if($role === 'brivibas')
        .custom-artikuli-table tr.vis-everyone:hover {
            background-color: rgba(241,234,227,0.5) !important;
        }
        .custom-artikuli-table tr.vis-kruzes-only:hover {
            background-color: #bfefff !important;
        }
        .custom-artikuli-table tr.vis-farmaceiti-only:hover {
            background-color: #ffd7ab !important;
        }
        .custom-artikuli-table tr.vis-hidden:hover {
            background-color: #efefef !important;
        }
    @else
        /* Default row look (same as pieprasijumi non-cito rows) */
        .custom-artikuli-table .artikuli-row {
            background-color: #f1eae3 !important;
        }
        .custom-artikuli-table .artikuli-row:hover {
            background-color: rgba(241,234,227,0.5) !important;
        }
    @endif
    .snn-cell {
        font-size: 0.9rem; /* or 12px, 14px, etc. */
    }
    .ipasas {
        font-size: 0.9rem; /* or 12px, 14px, etc. */
    }
    /* Soft pastel buttons for table actions */
.custom-artikuli-table .btn-sm.btn-primary {
  background-color: #a8d0ff;   /* pastel blue */
  color: #0b3a66;
  border: 1px solid rgba(11,58,102,0.12);
  box-shadow: none;
}

.custom-artikuli-table .btn-sm.btn-primary:hover,
.custom-artikuli-table .btn-sm.btn-primary:focus {
  background-color: #8cc0ff;   /* slightly stronger on hover */
  color: #04283f;
}

/* Soft pastel danger (delete) */
.custom-artikuli-table .btn-sm.btn-danger {
  background-color: #ffb3b3;   /* pastel red/pink */
  color: #6a0f0f;
  border: 1px solid rgba(170,10,10,0.12);
  box-shadow: none;
}

.custom-artikuli-table .btn-sm.btn-danger:hover,
.custom-artikuli-table .btn-sm.btn-danger:focus {
  background-color: #ff9999;
  color: #540b0b;
}

/* Optional: slightly rounder corners */
.custom-artikuli-table .btn-sm {
  border-radius: 6px;
}

/* Softer focus outline */
.custom-artikuli-table .btn:focus {
  box-shadow: 0 0 0 0.12rem rgba(0,0,0,0.06) !important;
  outline: none !important;
}

</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const cell = event.target.closest('.toggle-details');
        if (!cell) return;

        const mainRow = cell.closest('tr');
        const additionalRow = mainRow.nextElementSibling;

        if (additionalRow && additionalRow.classList.contains('additional-info')) {
            additionalRow.style.display =
                (additionalRow.style.display === 'none' || additionalRow.style.display === '')
                    ? 'table-row'
                    : 'none';
        }
    });
});
</script>