@extends('layouts.app')

@section('title', 'Jadwal Harian Karyawan')

@section('content')
    @php
        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('d F Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatTime = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };

        $statusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $statusClasses = [
            'work' => 'text-bg-success',
            'off' => 'text-bg-secondary',
            'permit' => 'text-bg-warning',
            'sick' => 'text-bg-danger',
        ];
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Jadwal Harian Karyawan
            </h1>

            <p class="page-description">
                Kelola penetapan jadwal kerja, hari libur,
                izin, dan sakit setiap karyawan.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('employee-schedules.create') }}"
                class="btn btn-primary"
            >
                Tambah Jadwal Harian
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('employee-schedules.index') }}"
        >
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-3">
                    <label
                        for="search"
                        class="form-label"
                    >
                        Pencarian Karyawan
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Nama atau nomor karyawan"
                    >
                </div>

                <div class="col-md-6 col-xl-3">
                    <label
                        for="branch_id"
                        class="form-label"
                    >
                        Cabang
                    </label>

                    <select
                        id="branch_id"
                        name="branch_id"
                        class="form-select"
                    >
                        <option value="">
                            Semua cabang
                        </option>

                        @foreach ($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                @selected(
                                    (string) $selectedBranchId
                                    === (string) $branch->id
                                )
                            >
                                {{ $branch->code }}
                                — {{ $branch->name }}

                                @if ($branch->status === 'inactive')
                                    (Tidak aktif)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label
                        for="schedule_status"
                        class="form-label"
                    >
                        Status Jadwal
                    </label>

                    <select
                        id="schedule_status"
                        name="schedule_status"
                        class="form-select"
                    >
                        <option value="">
                            Semua status
                        </option>

                        <option
                            value="work"
                            @selected($selectedStatus === 'work')
                        >
                            Kerja
                        </option>

                        <option
                            value="off"
                            @selected($selectedStatus === 'off')
                        >
                            Libur
                        </option>

                        <option
                            value="permit"
                            @selected($selectedStatus === 'permit')
                        >
                            Izin
                        </option>

                        <option
                            value="sick"
                            @selected($selectedStatus === 'sick')
                        >
                            Sakit
                        </option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label
                        for="date_from"
                        class="form-label"
                    >
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="form-control"
                    >
                </div>

                <div class="col-md-6 col-xl-2">
                    <label
                        for="date_to"
                        class="form-label"
                    >
                        Tanggal Akhir
                    </label>

                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="form-control"
                    >
                </div>

                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Terapkan Filter
                        </button>

                        <a
                            href="{{
                                route(
                                    'employee-schedules.index'
                                )
                            }}"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section class="content-card">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-2 border-bottom p-3 p-md-4"
        >
            <div>
                <h2 class="h5 fw-bold mb-1">
                    Daftar Jadwal Harian
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $employeeSchedules->total() }}
                    data jadwal.
                </p>
            </div>

            @if (
                $search !== ''
                || $selectedBranchId !== null
                || $selectedStatus !== ''
                || $dateFrom !== null
                || $dateTo !== null
            )
                <span class="badge text-bg-info align-self-start">
                    Filter aktif
                </span>
            @endif
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

                        <th scope="col">
                            Cabang
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status
                        </th>

                        <th scope="col">
                            Pola Jadwal
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Jam Kerja
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Presensi
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                            style="width: 170px;"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $employeeSchedules
                        as $employeeSchedule
                    )
                        @php
                            $employee = $employeeSchedule->employee;
                            $branch = $employee?->branch;
                            $workSchedule =
                                $employeeSchedule->workSchedule;

                            $scheduleStatus =
                                $employeeSchedule->schedule_status;

                            $statusLabel =
                                $statusLabels[$scheduleStatus]
                                ?? ucfirst($scheduleStatus);

                            $statusClass =
                                $statusClasses[$scheduleStatus]
                                ?? 'text-bg-secondary';
                        @endphp

                        <tr>
                            <td class="text-center text-secondary">
                                {{
                                    ($employeeSchedules->firstItem()
                                        ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $employeeSchedule
                                                ->schedule_date
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        \Illuminate\Support\Carbon::parse(
                                            $employeeSchedule
                                                ->schedule_date
                                        )
                                            ->locale('id')
                                            ->translatedFormat('l')
                                    }}
                                </div>
                            </td>

                            <td>
                                @if ($employee !== null)
                                    <div class="fw-semibold">
                                        {{ $employee->full_name }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $employee
                                                ->employee_number
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $employee->position }}
                                    </div>
                                @else
                                    <span class="text-danger">
                                        Data karyawan tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($branch !== null)
                                    <div class="fw-semibold">
                                        {{ $branch->code }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $branch->name }}
                                    </div>

                                    @if ($branch->status === 'inactive')
                                        <span
                                            class="badge
                                                text-bg-warning mt-1"
                                        >
                                            Tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-secondary">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td>
                                @if (
                                    $scheduleStatus === 'work'
                                    && $workSchedule !== null
                                )
                                    <div class="fw-semibold">
                                        {{ $workSchedule->name }}
                                    </div>

                                    @if (
                                        $workSchedule->status
                                        === 'inactive'
                                    )
                                        <span
                                            class="badge
                                                text-bg-warning mt-1"
                                        >
                                            Pola tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-secondary">
                                        Tidak menggunakan pola jadwal
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if (
                                    $scheduleStatus === 'work'
                                    && $workSchedule !== null
                                )
                                    <div class="fw-semibold">
                                        {{
                                            $formatTime(
                                                $workSchedule
                                                    ->check_in_time
                                            )
                                        }}
                                        –
                                        {{
                                            $formatTime(
                                                $workSchedule
                                                    ->check_out_time
                                            )
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        WIB
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge text-bg-light border"
                                >
                                    {{
                                        $employeeSchedule
                                            ->attendances_count
                                        ?? 0
                                    }}
                                    data
                                </span>
                            </td>

                            <td class="text-end">
                                <div
                                    class="d-flex flex-wrap
                                        justify-content-end gap-2"
                                >
                                    <a
                                        href="{{
                                            route(
                                                'employee-schedules.show',
                                                $employeeSchedule
                                            )
                                        }}"
                                        class="btn btn-sm
                                            btn-outline-primary"
                                    >
                                        Detail
                                    </a>

                                    <a
                                        href="{{
                                            route(
                                                'employee-schedules.edit',
                                                $employeeSchedule
                                            )
                                        }}"
                                        class="btn btn-sm
                                            btn-outline-secondary"
                                    >
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="9"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Jadwal harian tidak ditemukan
                                </div>

                                <div class="small text-secondary">
                                    Belum ada jadwal atau data tidak
                                    sesuai dengan filter yang digunakan.
                                </div>

                                <a
                                    href="{{
                                        route(
                                            'employee-schedules.index'
                                        )
                                    }}"
                                    class="btn btn-sm
                                        btn-outline-secondary mt-3"
                                >
                                    Reset Filter
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employeeSchedules->hasPages())
            @php
                $startPage = max(
                    1,
                    $employeeSchedules->currentPage() - 2
                );

                $endPage = min(
                    $employeeSchedules->lastPage(),
                    $employeeSchedules->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $employeeSchedules->firstItem() }}
                    sampai
                    {{ $employeeSchedules->lastItem() }}
                    dari
                    {{ $employeeSchedules->total() }}
                    data.
                </div>

                <nav aria-label="Navigasi jadwal harian">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            class="page-item {{
                                $employeeSchedules->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $employeeSchedules
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $employeeSchedules->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $employeeSchedules
                                        ->currentPage()
                                        ? 'active'
                                        : ''
                                }}"
                            >
                                <a
                                    class="page-link"
                                    href="{{ $url }}"
                                >
                                    {{ $page }}
                                </a>
                            </li>
                        @endforeach

                        <li
                            class="page-item {{
                                $employeeSchedules
                                    ->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $employeeSchedules
                                        ->nextPageUrl()
                                    ?? '#'
                                }}"
                            >
                                Berikutnya
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </section>
@endsection