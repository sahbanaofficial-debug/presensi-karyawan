@extends('layouts.app')

@section('title', 'Detail Roster Mingguan')

@section('content')
    @php
        $statusLabels = [
            'draft' => 'Draft',
            'published' => 'Dipublikasikan',
        ];

        $statusClasses = [
            'draft' => 'text-bg-warning',
            'published' => 'text-bg-success',
        ];

        $itemStatusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $itemStatusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDateTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y H:i');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $status = $weeklySchedule->status;

        $statusLabel =
            $statusLabels[$status]
            ?? ucfirst($status);

        $statusClass =
            $statusClasses[$status]
            ?? 'text-bg-secondary';
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Detail Roster Mingguan
            </h1>

            <p class="page-description">
                Informasi roster, snapshot pola kerja,
                dan hasil publikasi jadwal harian.
            </p>
        </div>

        <div
            class="d-flex flex-wrap gap-2
                mt-3 mt-md-0"
        >
            <a
                href="{{ route('weekly-rosters.index') }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>

            @if ($weeklySchedule->isDraft())
                <button
                    type="button"
                    id="publish-weekly-roster"
                    class="btn btn-success"
                    data-publish-url="{{
                        route(
                            'weekly-rosters.publish',
                            $weeklySchedule
                        )
                    }}"
                >
                    Publikasikan Roster
                </button>
            @endif
        </div>
    </header>

    <div
        id="weekly-roster-detail-alert"
        class="alert d-none"
        role="alert"
        aria-live="polite"
    ></div>
    @if ($weeklySchedule->isDraft())
        <div
            class="alert alert-warning"
            id="weekly-roster-publish-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Publikasi bersifat final
            </div>

            <div class="small">
                Sistem akan membentuk jadwal harian dari seluruh
                item roster. Publikasi ditolak apabila salah satu
                karyawan sudah memiliki jadwal pada tanggal yang
                sama, dan roster yang berhasil dipublikasikan
                tidak dapat dipublikasikan ulang.
            </div>
        </div>
    @else
        <div
            class="alert alert-success"
            id="weekly-roster-published-summary"
            role="status"
        >
            Roster telah dipublikasikan dan jadwal harian
            berhasil dibentuk.
        </div>
    @endif

    <section class="content-card p-3 p-md-4 mb-4">
        <div class="row g-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Cabang
                </div>

                <div class="fw-bold">
                    {{
                        $weeklySchedule->branch?->code
                        ?? '-'
                    }}
                </div>

                <div class="small text-secondary">
                    {{
                        $weeklySchedule->branch?->name
                        ?? 'Cabang tidak tersedia'
                    }}
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Periode
                </div>

                <div class="fw-bold">
                    {{
                        $formatDate(
                            $weeklySchedule
                                ->week_start_date
                        )
                    }}
                </div>

                <div class="small text-secondary">
                    sampai
                    {{
                        $formatDate(
                            $weeklySchedule
                                ->week_end_date
                        )
                    }}
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Status
                </div>

                <span class="badge {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Jumlah item
                </div>

                <div class="fw-bold">
                    {{ $weeklySchedule->items->count() }}
                    item
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="small text-secondary mb-1">
                    Dibuat oleh
                </div>

                <div class="fw-semibold">
                    {{
                        $weeklySchedule->creator?->name
                        ?? '-'
                    }}
                </div>

                <div class="small text-secondary">
                    {{
                        $formatDateTime(
                            $weeklySchedule->created_at
                        )
                    }}
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="small text-secondary mb-1">
                    Publikasi
                </div>

                @if ($weeklySchedule->isPublished())
                    <div class="fw-semibold">
                        {{
                            $weeklySchedule
                                ->publisher?->name
                            ?? '-'
                        }}
                    </div>

                    <div class="small text-secondary">
                        {{
                            $formatDateTime(
                                $weeklySchedule
                                    ->published_at
                            )
                        }}
                    </div>
                @else
                    <div class="text-secondary">
                        Belum dipublikasikan
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="content-card">
        <div class="border-bottom p-3 p-md-4">
            <h2 class="h5 fw-bold mb-1">
                Item Jadwal Mingguan
            </h2>

            <p class="small text-secondary mb-0">
                Snapshot pola kerja tidak berubah ketika
                master pola jadwal diperbarui.
            </p>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover align-middle mb-0"
            >
                <thead class="table-light">
                    <tr>
                        <th
                            scope="col"
                            class="text-center"
                            style="width: 65px;"
                        >
                            No.
                        </th>

                        <th scope="col">
                            Tanggal
                        </th>

                        <th scope="col">
                            Karyawan
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status
                        </th>

                        <th scope="col">
                            Pola Kerja
                        </th>

                        <th scope="col">
                            Catatan
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Jadwal Harian
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $weeklySchedule->items
                        as $item
                    )
                        @php
                            $itemStatus =
                                $item->schedule_status;

                            $itemStatusLabel =
                                $itemStatusLabels[
                                    $itemStatus
                                ]
                                ?? ucfirst($itemStatus);

                            $itemStatusClass =
                                $itemStatusClasses[
                                    $itemStatus
                                ]
                                ?? 'text-bg-secondary';

                            $snapshotName =
                                $item
                                    ->work_schedule_name_snapshot;

                            $checkIn =
                                $item
                                    ->check_in_time_snapshot;

                            $checkOut =
                                $item
                                    ->check_out_time_snapshot;
                        @endphp

                        <tr>
                            <td
                                class="text-center
                                    text-secondary"
                            >
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $item
                                                ->schedule_date
                                        )
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $item->employee
                                            ?->full_name
                                        ?? '-'
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $item->employee
                                            ?->employee_number
                                        ?? '-'
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $item->employee
                                            ?->position
                                        ?? '-'
                                    }}
                                </div>
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge
                                        {{ $itemStatusClass }}"
                                >
                                    {{ $itemStatusLabel }}
                                </span>
                            </td>

                            <td>
                                @if (
                                    $itemStatus === 'work'
                                    && $snapshotName !== null
                                )
                                    <div class="fw-semibold">
                                        {{ $snapshotName }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            substr(
                                                (string) $checkIn,
                                                0,
                                                5
                                            )
                                        }}
                                        sampai
                                        {{
                                            substr(
                                                (string) $checkOut,
                                                0,
                                                5
                                            )
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        Batas masuk:
                                        {{
                                            (int) $item
                                                ->check_in_limit_minutes_snapshot
                                        }}
                                        menit
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Tidak menggunakan pola kerja
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{
                                    $item->notes
                                    ?? '-'
                                }}
                            </td>

                            <td class="text-center">
                                @if (
                                    $item->employeeSchedule
                                    !== null
                                )
                                    <span
                                        class="badge
                                            text-bg-success"
                                    >
                                        Terbentuk
                                    </span>
                                @elseif (
                                    $weeklySchedule
                                        ->isPublished()
                                )
                                    <span
                                        class="badge
                                            text-bg-danger"
                                    >
                                        Tidak ditemukan
                                    </span>
                                @else
                                    <span
                                        class="badge
                                            text-bg-secondary"
                                    >
                                        Belum diterbitkan
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="text-center
                                    text-secondary py-5"
                            >
                                Roster ini tidak mempunyai item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($weeklySchedule->isDraft())
        <form id="publish-csrf-form" class="d-none">
            @csrf
        </form>
    @endif
@endsection

@if ($weeklySchedule->isDraft())
    @push('scripts')
        <script>
            document.addEventListener(
                'DOMContentLoaded',
                function () {
                    const publishButton =
                        document.getElementById(
                            'publish-weekly-roster'
                        );

                    const alertBox =
                        document.getElementById(
                            'weekly-roster-detail-alert'
                        );

                    const csrfToken =
                        document.querySelector(
                            '#publish-csrf-form '
                            + 'input[name="_token"]'
                        ).value;

                    publishButton.addEventListener(
                        'click',
                        async function () {
                            const confirmed =
                                window.confirm(
                                    'Publikasikan roster ini '
                                    + 'menjadi jadwal harian?'
                                );

                            if (! confirmed) {
                                return;
                            }

                            publishButton.disabled = true;
                            publishButton.textContent =
                                'Memublikasikan...';

                            try {
                                const response =
                                    await fetch(
                                        publishButton
                                            .dataset
                                            .publishUrl,
                                        {
                                            method: 'PATCH',
                                            headers: {
                                                Accept:
                                                    'application/json',

                                                'Content-Type':
                                                    'application/json',

                                                'X-CSRF-TOKEN':
                                                    csrfToken,
                                            },
                                            body: JSON.stringify(
                                                {}
                                            ),
                                        }
                                    );

                                const payload =
                                    await response.json();

                                if (! response.ok) {
                                    const errors =
                                        payload.errors ?? {};

                                    const messages =
                                        Object.values(
                                            errors
                                        ).flat();

                                    throw new Error(
                                        messages[0]
                                        ?? payload.message
                                        ?? 'Roster gagal dipublikasikan.'
                                    );
                                }

                                window.location.reload();
                            } catch (error) {
                                alertBox.className =
                                    'alert alert-danger';

                                alertBox.textContent =
                                    error.message
                                    ?? 'Roster gagal dipublikasikan.';

                                publishButton.disabled =
                                    false;

                                publishButton.textContent =
                                    'Publikasikan Roster';
                            }
                        }
                    );
                }
            );
        </script>
    @endpush
@endif
