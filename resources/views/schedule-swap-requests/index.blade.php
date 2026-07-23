@extends('layouts.app')

@section('title', 'Pertukaran Jadwal')

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
                return substr((string) $value, 0, 10);
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

        $statusLabels = [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        $statusClasses = [
            'pending' => 'text-bg-warning',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Pertukaran Jadwal
            </h1>

            <p class="page-description">
                Catat dan kelola permohonan pertukaran jadwal
                antarkaryawan.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('schedule-swap-requests.create') }}"
                class="btn btn-primary"
            >
                Catat Permohonan
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('schedule-swap-requests.index') }}"
        >
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-xl-4">
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
                        placeholder="Nama, nomor karyawan, atau alasan"
                    >
                </div>

                <div class="col-md-6 col-xl-2">
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
                            value="pending"
                            @selected($selectedStatus === 'pending')
                        >
                            Menunggu
                        </option>

                        <option
                            value="approved"
                            @selected($selectedStatus === 'approved')
                        >
                            Disetujui
                        </option>

                        <option
                            value="rejected"
                            @selected($selectedStatus === 'rejected')
                        >
                            Ditolak
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

                <div class="col-xl-2">
                    <div class="d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{
                                route(
                                    'schedule-swap-requests.index'
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
                    Daftar Permohonan
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $scheduleSwapRequests->total() }}
                    permohonan pertukaran jadwal.
                </p>
            </div>

            @if (
                $search !== ''
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
                            Karyawan Pengaju
                        </th>

                        <th scope="col">
                            Jadwal Pengaju
                        </th>

                        <th scope="col">
                            Karyawan Pasangan
                        </th>

                        <th scope="col">
                            Jadwal Pasangan
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status
                        </th>

                        <th scope="col">
                            Keputusan
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                            style="width: 110px;"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $scheduleSwapRequests
                        as $scheduleSwapRequest
                    )
                        @php
                            $requester =
                                $scheduleSwapRequest
                                    ->requesterEmployee;

                            $requesterBranch =
                                $requester?->branch;

                            $partner =
                                $scheduleSwapRequest
                                    ->partnerEmployee;

                            $partnerBranch =
                                $partner?->branch;

                            $requestStatus =
                                strtolower(
                                    (string) $scheduleSwapRequest
                                        ->status
                                );

                            $statusLabel =
                                $statusLabels[$requestStatus]
                                ?? ucfirst($requestStatus);

                            $statusClass =
                                $statusClasses[$requestStatus]
                                ?? 'text-bg-secondary';
                        @endphp

                        <tr>
                            <td class="text-center text-secondary">
                                {{
                                    ($scheduleSwapRequests->firstItem()
                                        ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                @if ($requester !== null)
                                    <div class="fw-semibold">
                                        {{ $requester->full_name }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $requester
                                                ->employee_number
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $requesterBranch?->code
                                            ?? '-'
                                        }}
                                        @if (
                                            $requesterBranch !== null
                                        )
                                            — {{
                                                $requesterBranch->name
                                            }}
                                        @endif
                                    </div>

                                    @if (
                                        $requester->employment_status
                                        === 'inactive'
                                    )
                                        <span
                                            class="badge
                                                text-bg-secondary mt-1"
                                        >
                                            Tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-danger">
                                        Data tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $scheduleSwapRequest
                                                ->requester_date
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    Jadwal yang akan ditukar
                                </div>
                            </td>

                            <td>
                                @if ($partner !== null)
                                    <div class="fw-semibold">
                                        {{ $partner->full_name }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $partner
                                                ->employee_number
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $partnerBranch?->code
                                            ?? '-'
                                        }}
                                        @if ($partnerBranch !== null)
                                            — {{
                                                $partnerBranch->name
                                            }}
                                        @endif
                                    </div>

                                    @if (
                                        $partner->employment_status
                                        === 'inactive'
                                    )
                                        <span
                                            class="badge
                                                text-bg-secondary mt-1"
                                        >
                                            Tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-danger">
                                        Data tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $formatDate(
                                            $scheduleSwapRequest
                                                ->partner_date
                                        )
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    Jadwal yang akan ditukar
                                </div>
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
                                    $requestStatus === 'pending'
                                )
                                    <span class="text-secondary">
                                        Belum diputuskan
                                    </span>
                                @elseif (
                                    $scheduleSwapRequest
                                        ->approver !== null
                                )
                                    <div class="fw-semibold">
                                        {{
                                            $scheduleSwapRequest
                                                ->approver
                                                ->name
                                        }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{
                                            $formatDateTime(
                                                $scheduleSwapRequest
                                                    ->approved_at
                                            )
                                        }}
                                        WIB
                                    </div>
                                @else
                                    <span class="text-secondary">
                                        Data pemberi keputusan
                                        tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a
                                    href="{{
                                        route(
                                            'schedule-swap-requests.show',
                                            $scheduleSwapRequest
                                        )
                                    }}"
                                    class="btn btn-sm
                                        btn-outline-primary"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Permohonan tidak ditemukan
                                </div>

                                <div class="small text-secondary">
                                    Belum terdapat permohonan atau
                                    data tidak sesuai dengan filter.
                                </div>

                                <a
                                    href="{{
                                        route(
                                            'schedule-swap-requests.index'
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

        @if ($scheduleSwapRequests->hasPages())
            @php
                $startPage = max(
                    1,
                    $scheduleSwapRequests->currentPage() - 2
                );

                $endPage = min(
                    $scheduleSwapRequests->lastPage(),
                    $scheduleSwapRequests->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $scheduleSwapRequests->firstItem() }}
                    sampai
                    {{ $scheduleSwapRequests->lastItem() }}
                    dari
                    {{ $scheduleSwapRequests->total() }}
                    permohonan.
                </div>

                <nav aria-label="Navigasi permohonan">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            class="page-item {{
                                $scheduleSwapRequests->onFirstPage()
                                    ? 'disabled'
                                    : ''
                            }}"
                        >
                            <a
                                href="{{
                                    $scheduleSwapRequests
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $scheduleSwapRequests->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item {{
                                    $page
                                    === $scheduleSwapRequests
                                        ->currentPage()
                                        ? 'active'
                                        : ''
                                }}"
                            >
                                <a
                                    href="{{ $url }}"
                                    class="page-link"
                                >
                                    {{ $page }}
                                </a>
                            </li>
                        @endforeach

                        <li
                            class="page-item {{
                                $scheduleSwapRequests
                                    ->hasMorePages()
                                    ? ''
                                    : 'disabled'
                            }}"
                        >
                            <a
                                href="{{
                                    $scheduleSwapRequests
                                        ->nextPageUrl()
                                    ?? '#'
                                }}"
                                class="page-link"
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