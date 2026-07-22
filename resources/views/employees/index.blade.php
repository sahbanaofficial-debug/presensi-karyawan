@extends('layouts.app')

@section('title', 'Data Karyawan')

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Data Karyawan
            </h1>

            <p class="page-description">
                Kelola dan pantau akun serta profil karyawan perusahaan.
            </p>
        </div>

        @if (auth()->user()->hasRole('hrd'))
            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('employees.create') }}"
                    class="btn btn-primary"
                >
                    Tambah Karyawan
                </a>
            </div>
        @endif
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <form
            method="GET"
            action="{{ route('employees.index') }}"
        >
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
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
                        placeholder="Nama atau nomor karyawan"
                    >
                </div>

                <div class="col-md-6 col-lg-3">
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
                                    $selectedBranchId === $branch->id
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

                <div class="col-md-6 col-lg-2">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status karyawan
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
                            @selected($selectedStatus === 'active')
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            @selected($selectedStatus === 'inactive')
                        >
                            Tidak aktif
                        </option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Terapkan
                        </button>

                        <a
                            href="{{ route('employees.index') }}"
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
                    Daftar Karyawan
                </h2>

                <p class="text-secondary small mb-0">
                    Ditemukan {{ $employees->total() }} data karyawan.
                </p>
            </div>

            @if (
                $search !== ''
                || $selectedBranchId !== null
                || $selectedStatus !== ''
            )
                <span class="badge text-bg-info align-self-start">
                    Filter aktif
                </span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th
                            scope="col"
                            class="text-center"
                            style="width: 70px;"
                        >
                            No.
                        </th>

                        <th scope="col">
                            Karyawan
                        </th>

                        <th scope="col">
                            Cabang
                        </th>

                        <th scope="col">
                            Jabatan
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status Karyawan
                        </th>

                        <th
                            scope="col"
                            class="text-center"
                        >
                            Status Akun
                        </th>

                        <th
                            scope="col"
                            class="text-end"
                            style="width: 180px;"
                        >
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="text-center text-secondary">
                                {{
                                    ($employees->firstItem() ?? 0)
                                    + $loop->index
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $employee->full_name }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $employee->employee_number }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $employee->user?->email ?? '-' }}
                                </div>
                            </td>

                            <td>
                                @if ($employee->branch !== null)
                                    <div class="fw-semibold">
                                        {{ $employee->branch->code }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $employee->branch->name }}
                                    </div>

                                    @if (
                                        $employee->branch->status
                                        === 'inactive'
                                    )
                                        <span
                                            class="badge text-bg-warning mt-1"
                                        >
                                            Cabang tidak aktif
                                        </span>
                                    @endif
                                @else
                                    <span class="text-secondary">
                                        Cabang tidak tersedia
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $employee->position }}
                            </td>

                            <td class="text-center">
                                @if (
                                    $employee->employment_status
                                    === 'active'
                                )
                                    <span class="badge text-bg-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Tidak aktif
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if ($employee->user?->status === 'active')
                                    <span class="badge text-bg-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
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
                                                'employees.show',
                                                $employee
                                            )
                                        }}"
                                        class="btn btn-sm
                                            btn-outline-primary"
                                    >
                                        Detail
                                    </a>

                                    @if (
                                        auth()->user()->hasRole('hrd')
                                    )
                                        <a
                                            href="{{
                                                route(
                                                    'employees.edit',
                                                    $employee
                                                )
                                            }}"
                                            class="btn btn-sm
                                                btn-outline-secondary"
                                        >
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="text-center py-5"
                            >
                                <div class="fw-semibold mb-1">
                                    Data karyawan tidak ditemukan
                                </div>

                                <div class="text-secondary small">
                                    Periksa kata pencarian atau filter
                                    yang digunakan.
                                </div>

                                <a
                                    href="{{ route('employees.index') }}"
                                    class="btn btn-sm
                                        btn-outline-secondary mt-3"
                                >
                                    Reset pencarian
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            @php
                $startPage = max(
                    1,
                    $employees->currentPage() - 2
                );

                $endPage = min(
                    $employees->lastPage(),
                    $employees->currentPage() + 2
                );
            @endphp

            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center
                    gap-3 border-top p-3 p-md-4"
            >
                <div class="small text-secondary">
                    Menampilkan
                    {{ $employees->firstItem() }}
                    sampai
                    {{ $employees->lastItem() }}
                    dari
                    {{ $employees->total() }}
                    data.
                </div>

                <nav aria-label="Navigasi halaman karyawan">
                    <ul class="pagination pagination-sm mb-0">
                        <li
                            class="page-item
                                {{
                                    $employees->onFirstPage()
                                        ? 'disabled'
                                        : ''
                                }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $employees->previousPageUrl()
                                    ?? '#'
                                }}"
                                aria-label="Halaman sebelumnya"
                            >
                                Sebelumnya
                            </a>
                        </li>

                        @foreach (
                            $employees->getUrlRange(
                                $startPage,
                                $endPage
                            ) as $page => $url
                        )
                            <li
                                class="page-item
                                    {{
                                        $page
                                        === $employees->currentPage()
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
                            class="page-item
                                {{
                                    $employees->hasMorePages()
                                        ? ''
                                        : 'disabled'
                                }}"
                        >
                            <a
                                class="page-link"
                                href="{{
                                    $employees->nextPageUrl()
                                    ?? '#'
                                }}"
                                aria-label="Halaman berikutnya"
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