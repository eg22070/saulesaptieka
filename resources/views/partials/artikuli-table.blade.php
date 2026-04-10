        {{ $products->links() }}
<table class="table custom-artikuli-table">
            <thead>
                @php
                    $role = strtolower(auth()->user()->role ?? '');
                    $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
                    $effectiveRole = $isSpecialUser ? 'farmaceiti' : $role;
                @endphp
                <tr>
                    @if($effectiveRole === 'farmaceiti')
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
                        $roleIsBrivibas = $effectiveRole === 'brivibas';
                        $hoverBgForThisRow = '';

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
                                $bgOdd = '#e4f6ff';
                                $bgEven = '#e4f6ff';
                                $detailBg = '#e9faff';
                            } elseif ($hideFromKruzes && !$hideFromFarmaceiti) {
                                // Only Farmaceiti can see
                                $visibilityClass = 'vis-farmaceiti-only';
                                // Stronger warm palette so it's clearly distinguishable from "everyone".
                                $bgOdd = '#ffd4a4';
                                $bgEven = '#ffd4a4';
                                $detailBg = '#ffc894';
                            } else {
                                // Hidden from both (should normally not appear)
                                $visibilityClass = 'vis-hidden';
                                $bgOdd = '#e9a58d';
                                $bgEven = '#e9a58d';
                                $detailBg = '#f6f6f6';
                            }
                        } else {
                            // Match pieprasijumi default row look for non-brivibas roles.
                            $visibilityClass = '';
                            $bgOdd = '#f1eae3';
                            $bgEven = '#f1eae3';
                            $detailBg = '#f8f9fa';
                        }

                        if ($roleIsBrivibas) {
                            // Convert the visible base background color to RGBA for hover overlay.
                            // This ensures opacity is derived from the same $bgOdd/$bgEven the row uses.
                            $baseBgForRow = $loop->odd ? $bgOdd : $bgEven;
                            $baseBgHex = ltrim($baseBgForRow, '#');
                            $r = hexdec(substr($baseBgHex, 0, 2));
                            $g = hexdec(substr($baseBgHex, 2, 2));
                            $b = hexdec(substr($baseBgHex, 4, 2));
                            $hoverBgForThisRow = "rgba($r,$g,$b,0.5)";
                        }
                    @endphp

                    <tr class="artikuli-row {{ $roleIsBrivibas ? $visibilityClass : '' }}"
                        style="background-color: {{ $loop->odd ? $bgOdd : $bgEven }}; {{ $roleIsBrivibas ? '--hover-bg:' . $hoverBgForThisRow . ';' : '' }}">
                    @if($effectiveRole === 'farmaceiti')    
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
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{!! nl2br(e($artikuls->info)) !!}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{!! nl2br(e($artikuls->pielietojums)) !!}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{!! nl2br(e($artikuls->atzimes)) !!}</td>
                    @else
                        <td class="{{ $effectiveRole === 'brivibas' ? 'toggle-details' : '' }}"
                            style="border:1px solid #080000ff; padding:4px; cursor: {{ $effectiveRole === 'brivibas' ? 'pointer' : 'default' }};"
                            title="{{ $effectiveRole === 'brivibas' ? 'Klikšķiniet, lai redzētu detaļas' : '' }}">
                            <b>{{ $artikuls->nosaukums }}</b>
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->id_numurs }}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{{ $artikuls->valsts }}</td>
                        <td class="snn-cell" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">
                            {!! nl2br(e($artikuls->snn)) !!}
                        </td>
                        <td style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{!! nl2br(e($artikuls->analogs)) !!}</td>
                        <td class="ipasas" style="border: 1px solid #080000ff; padding: 4px; vertical-align: middle;">{!! nl2br(e($artikuls->atzimes)) !!}</td>
                        <td style="border: 1px solid #080000ff; padding: 4px; text-align: center;">
                             @if($effectiveRole !== 'farmaceiti')
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
                                        @if($effectiveRole === 'brivibas')
                                            data-atk="{{ $artikuls->atk }}"
                                            data-atk_validity_days="{{ $artikuls->atk_validity_days }}"
                                            data-info="{{ $artikuls->info }}"
                                            data-pielietojums="{{ $artikuls->pielietojums }}"
                                            data-hide_from_farmaceiti="{{ $artikuls->hide_from_farmaceiti ? 1 : 0 }}"
                                            data-hide_from_kruzes="{{ $artikuls->hide_from_kruzes ? 1 : 0 }}"
                                            data-without_arst="{{ $artikuls->without_arst ? 1 : 0 }}"
                                            data-nemedikamenti="{{ $artikuls->nemedikamenti ? 1 : 0 }}"
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
                @if($effectiveRole === 'brivibas')
                    <tr class="additional-info {{ $visibilityClass }}" style="display:none;">
                        <td colspan="7" style="background-color:{{ $hoverBgForThisRow ?: $detailBg }}; border:1px solid #080000ff;">
                            <div style="padding:10px;">
                                @php
                                    $atkValidityDaysForDot = (int) ($artikuls->atk_validity_days ?? 90);
                                    $atkDotBg = $atkValidityDaysForDot === 90 ? '#ffcccc' : '#ccffcc';
                                    $atkDotBorder = $atkValidityDaysForDot === 90 ? '#7a0000' : '#006600';
                                @endphp
                                <strong>ATĶ:</strong>
                                {{ $artikuls->atk ?: '-' }}
                                <span
                                    title="{{ $atkValidityDaysForDot === 90 ? '90 dienas' : '1 gads' }}"
                                    style="
                                        display:inline-block;
                                        width:12px;
                                        height:12px;
                                        margin-left:8px;
                                        border-radius:999px;
                                        background-color:{{ $atkDotBg }};
                                        border:2px solid {{ $atkDotBorder }};
                                        vertical-align:middle;
                                    "
                                ></span>
                                <br>
                                <strong>Info:</strong>{!! nl2br(e($artikuls->info ?: '-')) !!}<br>
                                <strong>Pielietojums:</strong> {!! nl2br(e($artikuls->pielietojums ?: '-')) !!}<br>
                                <strong>Paslēpts no Krūzes ielas:</strong> {{ $artikuls->hide_from_kruzes ? 'Jā' : 'Nē' }}<br>
                                <strong>Paslēpts no farmaceitiem:</strong> {{ $artikuls->hide_from_farmaceiti ? 'Jā' : 'Nē' }}<br>
                                <strong>Bez ārstnieciskām iestādēm:</strong> {{ $artikuls->without_arst ? 'Jā' : 'Nē' }}<br>
                                <strong>Ne medikamenti:</strong> {{ $artikuls->nemedikamenti ? 'Jā' : 'Nē' }}
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

    @if($effectiveRole === 'brivibas')
        .custom-artikuli-table tr.vis-everyone:hover {
            background-color: var(--hover-bg) !important;
        }
        .custom-artikuli-table tr.vis-kruzes-only:hover {
            background-color: var(--hover-bg) !important;
        }
        .custom-artikuli-table tr.vis-farmaceiti-only:hover {
            background-color: var(--hover-bg) !important;
        }
        .custom-artikuli-table tr.vis-hidden:hover {
            background-color: var(--hover-bg) !important;
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