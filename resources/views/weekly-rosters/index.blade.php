@extends('layouts.app')

@section('title', 'Roster Mingguan')

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
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Roster Mingguan
            </h1>

            <p class="page-description">
                Susun, tinjau, dan publikasikan jadwal kerja
                karyawan untuk satu minggu.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('weekly-rosters.create') }}"
                class="btn btn-primary"
            >
                <i
                    class="bi bi-calendar-plus me-2"
                    aria-hidden="true"
                ></i>

                Susun Roster
            </a>
        </div>
    </header>

    @if (session('success'))
        <div
            class="alert alert-success"
            role="alert"
        >
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="alert alert-danger"
            role="alert"
        >
            {{ session('error') }}
        </div>
    @endif

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('weekly-rosters.index') }}"
        >
            <div class="row g-3">
                <div class="col-12 col-lg-4">
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
                                    $selectedBranchId
                                    === (int) $branch->id
                                )
                            >
                                {{ $branch->code }}
                                ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status roster
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
                            value="draft"
                            @selected(
                                $selectedStatus === 'draft'
                            )
                        >
                            Draft
                        </option>

                        <option
                            value="published"
                            @selected(
                                $selectedStatus
                                === 'published'
                            )
                        >
                            Dipublikasikan
                        </option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label
                        for="week_start_date"
                        class="form-label"
                    >
                        Awal minggu
                    </label>

                    <input
                        type="date"
                        id="week_start_date"
                        name="week_start_date"
                        value="{{ $selectedWeekStartDate }}"
                        class="form-control"
                    >
                </div>

                <div
                    class="col-12 col-lg-2 d-flex
                        align-items-end gap-2"
                >
                    <button
                        type="submit"
                        class="btn btn-primary flex-grow-1"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('weekly-rosters.index') }}"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>
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
                    Daftar Roster
                </h2>

                <p class="small text-secondary mb-0">
                    Ditemukan
                    {{ $weeklySchedules->total() }}
                    roster mingguan.
                </p>
            </div>

            @if (
                $selectedBranchId !== null
                || $selectedStatus !== ''
                || $selectedWeekStartDate !== null
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
                            Periode
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

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Item
                        </th>

                        <th scope="col">
                            Dibuat oleh
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                            style="width: 120px;"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse (
                        $weeklySchedules
                        as $weeklySchedule
                    )
                        @php
                            $status =
                                $weeklySchedule->status;

                            $statusLabel =
                                $statusLabels[$status]
                                ?? ucfirst($status);

                            $statusClass =
                                $statusClasses[$status]
                                ?? 'text-bg-secondary';
                        @endphp

                        <tr>
                            <td
                                class="text-center
                                    text-secondary"
                            >
                                {{
                                    ($weeklySchedules
                                        ->firstItem() ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
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
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{
                                        $weeklySchedule
                                            ->branch?->code
                                        ?? '-'
                                    }}
                                </div>

                                <div class="small text-secondary">
                                    {{
                                        $weeklySchedule
                                            ->branch?->name
                                        ?? 'Cabang tidak tersedia'
                                    }}
                                </div>
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="text-center">
                                <span
                                    class="badge
                                        text-bg-light border"
                                >
                                    {{
                                        (int) $weeklySchedule
                                            ->items_count
                                    }}
                                    item
                                </span>
                            </td>

                            <td>
                                {{
                                    $weeklySchedule
                                        ->creator?->name
                                    ?? '-'
                                }}

                                @if (
                                    $weeklySchedule
                                        ->publisher !== null
                                )
                                    <div
                                        class="small
                                            text-secondary"
                                    >
                                        Dipublikasikan oleh
                                        {{
                                            $weeklySchedule
                                                ->publisher
                                                ->name
                                        }}
                                    </div>
                                @endif
                            </td>

                            <td class="text-end">
                                <a
                                    href="{{
                                        route(
                                            'weekly-rosters.show',
                                            $weeklySchedule
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
                                colspan="7"
                                class="text-center
                                    text-secondary py-5"
                            >

                                <div class="fw-semibold mb-1">
                                    Roster mingguan belum tersedia
                                </div>

                                <div class="small text-secondary">
                                    Belum ada roster atau data tidak
                                    sesuai dengan filter yang digunakan.
                                </div>

                                <a
                                    href="{{ route('weekly-rosters.create') }}"
                                    class="btn btn-sm
                                        btn-outline-primary mt-3"
                                >
                                    Susun Roster Pertama
                                </a>

                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($weeklySchedules->hasPages())
            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-between
                    align-items-sm-center gap-3
                    border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $weeklySchedules->firstItem() }}
                    sampai
                    {{ $weeklySchedules->lastItem() }}
                    dari
                    {{ $weeklySchedules->total() }}
                    data.
                </div>

                <nav aria-label="Navigasi halaman roster">
                    <ul class="pagination mb-0">
                        <li
                            class="page-item
                                {{
                                    $weeklySchedules
                                        ->onFirstPage()
                                        ? 'disabled'
                                        : ''
                                }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $weeklySchedules
                                        ->previousPageUrl()
                                    ?? '#'
                                }}"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        <li
                            class="page-item
                                {{
                                    $weeklySchedules
                                        ->hasMorePages()
                                        ? ''
                                        : 'disabled'
                                }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $weeklySchedules
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
