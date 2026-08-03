@extends('layouts.app')

@section('title', 'Detail Roster Mingguan')

@php
    $isDraft = $weeklySchedule->isDraft();
    $isPublished = $weeklySchedule->isPublished();

    $statusLabel = $isPublished
        ? 'Dipublikasikan'
        : 'Draft';

    $statusClass = $isPublished
        ? 'published'
        : 'draft';

    $weekStartLabel = $weeklySchedule->week_start_date
        ?->locale('id')
        ->translatedFormat('d F Y') ?? '-';

    $weekEndLabel = $weeklySchedule->week_end_date
        ?->locale('id')
        ->translatedFormat('d F Y') ?? '-';
@endphp

@push('styles')
    <style>
        .roster-detail-stack {
            display: grid;
            gap: var(--space-5);
        }

        .roster-summary,
        .roster-state,
        .roster-matrix-panel {
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-lg);
            background: var(--neutral-0);
            box-shadow: var(--shadow-xs);
        }

        .roster-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: var(--space-5);
            align-items: start;
            padding: var(--space-5);
        }

        .roster-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-4);
            margin-top: var(--space-4);
        }

        .roster-summary-item {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-50);
        }

        .roster-summary-label {
            display: block;
            color: var(--neutral-600);
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .roster-summary-value {
            display: block;
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.875rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .roster-status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: 0.45rem 0.75rem;
            border-radius: var(--radius-pill);
            font-size: 0.75rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .roster-status-badge::before {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            content: "";
        }

        .roster-status-badge.draft {
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .roster-status-badge.draft::before {
            background: var(--warning-500);
        }

        .roster-status-badge.published {
            color: var(--success-700);
            background: var(--success-50);
        }

        .roster-status-badge.published::before {
            background: var(--success-500);
        }

        .roster-state {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
        }

        .roster-state-copy {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
        }

        .roster-state-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            font-size: 1rem;
        }

        .roster-state.draft .roster-state-icon {
            color: var(--warning-700);
            background: var(--warning-50);
        }

        .roster-state.published .roster-state-icon {
            color: var(--success-700);
            background: var(--success-50);
        }

        .roster-state-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 0.95rem;
            font-weight: 850;
        }

        .roster-state-description {
            margin: var(--space-1) 0 0;
            color: var(--neutral-600);
            font-size: 0.8125rem;
            line-height: 1.6;
        }

        .roster-matrix-panel {
            overflow: hidden;
        }

        .roster-matrix-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--neutral-200);
        }

        .roster-legend {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .roster-legend-item,
        .roster-cell-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: var(--radius-pill);
            font-weight: 800;
        }

        .roster-legend-item {
            padding: 0.32rem 0.6rem;
            font-size: 0.68rem;
        }

        .roster-matrix-wrap {
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
        }

        .roster-matrix {
            width: 100%;
            min-width: 82rem;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .roster-matrix th,
        .roster-matrix td {
            border-right: 1px solid var(--neutral-200);
            border-bottom: 1px solid var(--neutral-200);
            vertical-align: middle;
        }

        .roster-matrix thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            min-width: 9.5rem;
            padding: var(--space-3);
            color: var(--neutral-700);
            background: var(--neutral-50);
            font-size: 0.75rem;
            text-align: center;
            white-space: nowrap;
        }

        .roster-matrix thead th:first-child,
        .roster-matrix tbody th {
            position: sticky;
            left: 0;
            z-index: 4;
            min-width: 15rem;
            max-width: 15rem;
            text-align: left;
        }

        .roster-matrix thead th:first-child {
            z-index: 5;
            background: var(--neutral-100);
        }

        .roster-matrix tbody th {
            padding: var(--space-3) var(--space-4);
            background: var(--neutral-0);
            box-shadow: 0.25rem 0 0.5rem -0.45rem rgba(15, 23, 42, 0.45);
        }

        .roster-matrix tbody td {
            min-width: 9.5rem;
            padding: var(--space-3);
            background: var(--neutral-0);
            text-align: center;
        }

        .roster-matrix tbody tr:last-child th,
        .roster-matrix tbody tr:last-child td {
            border-bottom: 0;
        }

        .roster-matrix th:last-child,
        .roster-matrix td:last-child {
            border-right: 0;
        }

        .roster-day-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.78rem;
            font-weight: 850;
        }

        .roster-day-date {
            display: block;
            margin-top: 0.2rem;
            color: var(--neutral-500);
            font-size: 0.68rem;
            font-weight: 700;
        }

        .roster-employee-name {
            display: block;
            color: var(--neutral-900);
            font-size: 0.82rem;
            font-weight: 850;
            line-height: 1.35;
        }

        .roster-employee-meta {
            display: block;
            margin-top: 0.25rem;
            color: var(--neutral-600);
            font-size: 0.7rem;
            font-weight: 650;
            line-height: 1.45;
        }

        .roster-cell {
            display: grid;
            gap: 0.35rem;
            justify-items: center;
        }

        .roster-cell-badge {
            min-width: 6.75rem;
            padding: 0.38rem 0.55rem;
            font-size: 0.68rem;
            line-height: 1.3;
            text-align: center;
        }

        .roster-cell-time {
            color: var(--neutral-600);
            font-size: 0.66rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .roster-cell-note {
            max-width: 8rem;
            color: var(--neutral-500);
            font-size: 0.62rem;
            line-height: 1.35;
        }

        .roster-choice-work {
            color: var(--success-700);
            border-color: var(--success-200);
            background: var(--success-50);
        }

        .roster-choice-off {
            color: var(--neutral-700);
            border-color: var(--neutral-300);
            background: var(--neutral-100);
        }

        .roster-choice-leave {
            color: var(--warning-700);
            border-color: var(--warning-200);
            background: var(--warning-50);
        }

        .roster-choice-permit {
            color: var(--brand-700);
            border-color: var(--brand-200);
            background: var(--brand-50);
        }

        .roster-choice-sick {
            color: var(--danger-700);
            border-color: var(--danger-200);
            background: var(--danger-50);
        }

        .roster-choice-empty {
            color: var(--neutral-500);
            border-color: var(--neutral-200);
            background: var(--neutral-50);
        }

        .roster-footer-note {
            padding: var(--space-4) var(--space-5);
            border-top: 1px solid var(--neutral-200);
            color: var(--neutral-600);
            background: var(--neutral-50);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        @media (max-width: 991.98px) {
            .roster-summary {
                grid-template-columns: 1fr;
            }

            .roster-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .roster-state,
            .roster-matrix-header {
                flex-direction: column;
            }
        }

        @media (max-width: 575.98px) {
            .roster-summary,
            .roster-state,
            .roster-matrix-header,
            .roster-footer-note {
                padding: var(--space-4);
            }

            .roster-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <header class="page-header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="page-title">Detail Roster Mingguan</h1>

                <p class="page-description">
                    Tinjau jadwal seluruh karyawan dalam bentuk matriks Senin sampai Minggu.
                </p>
            </div>

            <a href="{{ route('weekly-rosters.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>
                Kembali ke daftar
            </a>
        </div>
    </header>

    <div class="roster-detail-stack">
        <section class="roster-summary" aria-labelledby="roster-summary-title">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div>
                        <h2 id="roster-summary-title" class="section-title mb-1">
                            {{ $weeklySchedule->branch?->code ?? '-' }} — {{ $weeklySchedule->branch?->name ?? '-' }}
                        </h2>

                        <p class="section-description mb-0">
                            Periode {{ $weekStartLabel }} sampai {{ $weekEndLabel }}.
                        </p>
                    </div>

                    <span class="roster-status-badge {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="roster-summary-grid">
                    <div class="roster-summary-item">
                        <span class="roster-summary-label">Jumlah karyawan</span>
                        <strong class="roster-summary-value">{{ $rosterRows->count() }}</strong>
                    </div>

                    <div class="roster-summary-item">
                        <span class="roster-summary-label">Jumlah item</span>
                        <strong class="roster-summary-value">{{ $weeklySchedule->items->count() }}</strong>
                    </div>

                    <div class="roster-summary-item">
                        <span class="roster-summary-label">Dibuat oleh</span>
                        <strong class="roster-summary-value">{{ $weeklySchedule->creator?->name ?? '-' }}</strong>
                    </div>

                    <div class="roster-summary-item">
                        <span class="roster-summary-label">Waktu publikasi</span>
                        <strong class="roster-summary-value">
                            {{ $weeklySchedule->published_at?->format('d-m-Y H:i') ?? 'Belum dipublikasikan' }}
                        </strong>
                    </div>
                </div>
            </div>

            @if ($isDraft)
                <button type="button" id="publish-weekly-roster" class="btn btn-primary flex-shrink-0">
                    <i class="bi bi-send-check me-2" aria-hidden="true"></i>
                    Publikasikan Roster
                </button>
            @endif
        </section>

        @if ($isDraft)
            <section class="roster-state draft" aria-label="Status roster draft">
                <div class="roster-state-copy">
                    <span class="roster-state-icon">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    </span>

                    <div>
                        <h2 class="roster-state-title">Publikasi bersifat final</h2>
                        <p class="roster-state-description">
                            Periksa seluruh sel pada matriks sebelum publikasi. Setelah dipublikasikan,
                            roster menjadi hanya-baca dan sistem membentuk jadwal harian sesuai item kerja.
                        </p>
                    </div>
                </div>
            </section>
        @elseif ($isPublished)
            <section class="roster-state published" aria-label="Status roster dipublikasikan">
                <div class="roster-state-copy">
                    <span class="roster-state-icon">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                    </span>

                    <div>
                        <h2 class="roster-state-title">Roster telah dipublikasikan</h2>
                        <p class="roster-state-description">
                            Matriks di bawah merupakan snapshot final. Jadwal kerja harian telah dibentuk
                            dan roster tidak dapat dipublikasikan ulang.
                        </p>
                    </div>
                </div>
            </section>
        @endif

        <section class="roster-matrix-panel" aria-labelledby="roster-matrix-title">
            <div class="roster-matrix-header">
                <div>
                    <h2 id="roster-matrix-title" class="section-title mb-1">Matriks Roster</h2>
                    <p class="section-description mb-0">
                        Satu baris mewakili satu karyawan dan satu kolom mewakili satu hari.
                    </p>
                </div>

                <ul class="roster-legend" aria-label="Legenda status roster">
                    <li class="roster-legend-item roster-choice-work">Jadwal kerja</li>
                    <li class="roster-legend-item roster-choice-off">Libur</li>
                    <li class="roster-legend-item roster-choice-leave">Cuti</li>
                    <li class="roster-legend-item roster-choice-permit">Izin</li>
                    <li class="roster-legend-item roster-choice-sick">Sakit</li>
                </ul>
            </div>

            <div class="roster-matrix-wrap">
                <table id="weekly-roster-detail-matrix" class="roster-matrix">
                    <thead>
                        <tr>
                            <th scope="col">Karyawan</th>

                            @foreach ($weekDays as $day)
                                <th scope="col">
                                    <span class="roster-day-name">{{ $day['day_label'] }}</span>
                                    <span class="roster-day-date">{{ $day['date_label'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($rosterRows as $row)
                            <tr>
                                <th scope="row">
                                    <span class="roster-employee-name">
                                        {{ $row['employee']?->full_name ?? 'Karyawan tidak ditemukan' }}
                                    </span>
                                    <span class="roster-employee-meta">
                                        {{ $row['employee']?->employee_number ?? '-' }}
                                        @if ($row['employee']?->position)
                                            · {{ $row['employee']->position }}
                                        @endif
                                    </span>
                                </th>

                                @foreach ($weekDays as $day)
                                    @php
                                        $item = $row['cells']->get($day['date']);
                                        $cellClass = 'roster-choice-empty';
                                        $cellLabel = 'Belum diisi';

                                        if ($item?->schedule_status === 'work') {
                                            $cellClass = 'roster-choice-work';
                                            $cellLabel = $item->work_schedule_name_snapshot
                                                ?? $item->workSchedule?->name
                                                ?? 'Jadwal kerja';
                                        } elseif ($item !== null) {
                                            $cellClass = 'roster-choice-' . $item->schedule_status;
                                            $cellLabel = $scheduleStatusLabels[$item->schedule_status]
                                                ?? \Illuminate\Support\Str::title($item->schedule_status);
                                        }
                                    @endphp

                                    <td>
                                        <div class="roster-cell">
                                            <span class="roster-cell-badge {{ $cellClass }}">
                                                {{ $cellLabel }}
                                            </span>

                                            @if ($item?->schedule_status === 'work')
                                                <span class="roster-cell-time">
                                                    {{ substr((string) $item->check_in_time_snapshot, 0, 5) }}
                                                    –
                                                    {{ substr((string) $item->check_out_time_snapshot, 0, 5) }}
                                                </span>
                                            @endif

                                            @if ($item?->notes)
                                                <span class="roster-cell-note">{{ $item->notes }}</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    Roster ini belum memiliki item jadwal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="roster-footer-note">
                Geser tabel secara horizontal pada layar kecil. Kolom karyawan tetap terlihat untuk
                memudahkan pembacaan jadwal Senin sampai Minggu.
            </div>
        </section>
    </div>
@endsection

@if ($isDraft)
    @push('scripts')
        <script>
            (() => {
                const publishButton = document.getElementById(
                    'publish-weekly-roster'
                );

                if (! publishButton) {
                    return;
                }

                publishButton.addEventListener('click', async () => {
                    const confirmed = window.confirm(
                        'Publikasi bersifat final. Pastikan seluruh jadwal sudah benar. Lanjutkan publikasi?'
                    );

                    if (! confirmed) {
                        return;
                    }

                    publishButton.disabled = true;
                    publishButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memublikasikan...';

                    try {
                        const response = await fetch(
                            @json(route('weekly-rosters.publish', $weeklySchedule)),
                            {
                                method: 'PATCH',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': @json(csrf_token()),
                                },
                                body: JSON.stringify({}),
                            }
                        );

                        const payload = await response.json().catch(
                            () => ({})
                        );

                        if (! response.ok) {
                            throw new Error(
                                payload.message
                                    ?? 'Roster gagal dipublikasikan.'
                            );
                        }

                        window.location.reload();
                    } catch (error) {
                        window.alert(
                            error instanceof Error
                                ? error.message
                                : 'Roster gagal dipublikasikan.'
                        );

                        publishButton.disabled = false;
                        publishButton.innerHTML = '<i class="bi bi-send-check me-2" aria-hidden="true"></i>Publikasikan Roster';
                    }
                });
            })();
        </script>
    @endpush
@endif
