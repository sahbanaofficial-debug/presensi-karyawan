@extends('layouts.app')

@section('title', 'Pola Jadwal Kerja')

@section('content')
    @php
        $formatTime = static function ($value): string {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            $time = (string) $value;

            return strlen($time) >= 5
                ? substr($time, 0, 5)
                : $time;
        };
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Pola Jadwal Kerja
            </h1>

            <p class="page-description">
                Kelola jam kerja dan aturan waktu presensi
                karyawan.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('work-schedules.create') }}"
                class="btn btn-primary"
            >
                Tambah Pola Jadwal
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('work-schedules.index') }}"
        >
            <div class="row g-3 align-items-end">
                <div class="col-md-7 col-lg-5">
                    <label
                        for="search"
                        class="form-label"
                    >
                        Pencarian
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Masukkan nama pola jadwal"
                    >
                </div>

                <div class="col-md-5 col-lg-3">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >
                        <option value="">
                            Semua status
                        </option>

                        <option
                            value="active"
                            @selected(
                                $selectedStatus === 'active'
                            )
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            @selected(
                                $selectedStatus === 'inactive'
                            )
                        >
                            Tidak aktif
                        </option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{
                                route('work-schedules.index')
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
                    Daftar Pola Jadwal
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $workSchedules->total() }}
                    pola jadwal kerja.
                </p>
            </div>

            @if (
                $search !== ''
                || $selectedStatus !== ''
            )
                <span
                    class="badge text-bg-info
                        align-self-start"
                >
                    Filter aktif
                </span>
            @endif
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover
                    align-middle mb-0"
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
                            Nama Jadwal
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
                            Presensi Masuk
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Presensi Pulang
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Penggunaan
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status
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
                        $workSchedules as $workSchedule
                    )
                        <tr>
                            <td
                                class="text-center
                                    text-secondary"
                            >
                                {{
                                    ($workSchedules->firstItem()
                                        ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $workSchedule->name }}
                                </div>

                                <div
                                    class="small
                                        text-secondary"
                                >
                                    ID:
                                    {{ $workSchedule->id }}
                                </div>
                            </td>

                            <td class="text-center">
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

                                <div
                                    class="small
                                        text-secondary"
                                >
                                    WIB
                                </div>
                            </td>

                            <td class="text-center">
                                <div>
                                    Dibuka
                                    <strong>
                                        {{
                                            $workSchedule
                                                ->check_in_open_minutes
                                        }}
                                    </strong>
                                    menit sebelumnya
                                </div>

                                <div
                                    class="small
                                        text-secondary mt-1"
                                >
                                    Toleransi:
                                    {{
                                        $workSchedule
                                            ->late_tolerance_minutes
                                    }}
                                    menit
                                </div>
                            </td>

                            <td class="text-center">
                                <div>
                                    Batas akhir
                                    <strong>
                                        {{
                                            $workSchedule
                                                ->check_out_limit_minutes
                                        }}
                                    </strong>
                                    menit
                                </div>

                                <div
                                    class="small
                                        text-secondary mt-1"
                                >
                                    Setelah jam pulang
                                </div>
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge
                                        text-bg-light border"
                                >
                                    {{
                                        $workSchedule
                                            ->employee_schedules_count
                                        ?? 0
                                    }}
                                    jadwal
                                </span>
                            </td>

                            <td class="text-center">
                                @if (
                                    $workSchedule->status
                                    === 'active'
                                )
                                    <span
                                        class="badge
                                            text-bg-success"
                                    >
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="badge
                                            text-bg-secondary"
                                    >
                                        Tidak aktif
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div
                                    class="d-flex flex-wrap
                                        justify-content-end gap-2"
                                >
                                    <a
                                        href="{{
                                            route(
                                                'work-schedules.show',
                                                $workSchedule
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
                                                'work-schedules.edit',
                                                $workSchedule
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
                                colspan="8"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Pola jadwal tidak ditemukan
                                </div>

                                <div
                                    class="small
                                        text-secondary"
                                >
                                    Periksa pencarian atau
                                    filter yang digunakan.
                                </div>

                                <a
                                    href="{{
                                        route(
                                            'work-schedules.index'
                                        )
                                    }}"
                                    class="btn btn-sm
                                        btn-outline-secondary mt-3"
                                >
                                    Reset Pencarian
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($workSchedules->hasPages())
            @php
                $startPage = max(
                    1,
                    $workSchedules->currentPage() - 2
                );

                $endPage = min(
                    $workSchedules->lastPage(),
                    $workSchedules->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between
                    align-items-md-center gap-3
                    border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $workSchedules->firstItem() }}
                    sampai
                    {{ $workSchedules->lastItem() }}
                    dari
                    {{ $workSchedules->total() }}
                    data.
                </div>

                <nav
                    aria-label="Navigasi pola jadwal"
                >
                    <ul
                        class="pagination
                            pagination-sm mb-0"
                    >
                        <li
                            class="page-item {{
                                $workSchedules
                                    ->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $workSchedules
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $workSchedules->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $workSchedules
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
                                $workSchedules
                                    ->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $workSchedules
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