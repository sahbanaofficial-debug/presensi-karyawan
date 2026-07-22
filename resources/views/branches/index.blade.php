@extends('layouts.app')

@section('title', 'Data Cabang')

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Data Cabang
            </h1>

            <p class="page-description">
                Kelola identitas cabang dan parameter validasi lokasi.
            </p>
        </div>

        <a
            href="{{ route('branches.create') }}"
            class="btn btn-primary mt-3 mt-md-0"
        >
            Tambah Cabang
        </a>
    </header>

    <section
        class="content-card p-3 p-md-4 mb-4"
        aria-labelledby="branch-search-heading"
    >
        <h2
            id="branch-search-heading"
            class="visually-hidden"
        >
            Pencarian cabang
        </h2>

        <form
            method="GET"
            action="{{ route('branches.index') }}"
            class="row g-3 align-items-end"
        >
            <div class="col-lg-9">
                <label
                    for="search"
                    class="form-label fw-semibold"
                >
                    Cari cabang
                </label>

                <input
                    type="search"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    class="form-control"
                    placeholder="Masukkan kode, nama, atau alamat cabang"
                    maxlength="100"
                >
            </div>

            <div class="col-lg-3">
                <div class="d-grid d-sm-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary flex-fill"
                    >
                        Cari
                    </button>

                    @if ($search !== '')
                        <a
                            href="{{ route('branches.index') }}"
                            class="btn btn-outline-secondary flex-fill"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </section>

    <section
        class="content-card overflow-hidden"
        aria-labelledby="branch-list-heading"
    >
        <div
            class="d-flex align-items-center justify-content-between
                gap-3 border-bottom p-3 p-md-4"
        >
            <div>
                <h2
                    id="branch-list-heading"
                    class="h5 fw-bold mb-1"
                >
                    Daftar Cabang
                </h2>

                <p class="text-secondary small mb-0">
                    Total {{ $branches->total() }} data cabang.
                </p>
            </div>
        </div>

        @if ($branches->isEmpty())
            <div class="p-4 p-md-5 text-center">
                <h3 class="h5 fw-semibold mb-2">
                    Data cabang tidak ditemukan
                </h3>

                <p class="text-secondary mb-4">
                    @if ($search !== '')
                        Tidak ada cabang yang sesuai dengan pencarian
                        “{{ $search }}”.
                    @else
                        Belum ada data cabang yang tersimpan.
                    @endif
                </p>

                @if ($search !== '')
                    <a
                        href="{{ route('branches.index') }}"
                        class="btn btn-outline-primary"
                    >
                        Tampilkan Semua Cabang
                    </a>
                @else
                    <a
                        href="{{ route('branches.create') }}"
                        class="btn btn-primary"
                    >
                        Tambah Cabang
                    </a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th
                                scope="col"
                                class="text-center"
                                style="width: 4rem;"
                            >
                                No.
                            </th>

                            <th scope="col">
                                Cabang
                            </th>

                            <th scope="col">
                                Alamat
                            </th>

                            <th scope="col">
                                Konfigurasi Lokasi
                            </th>

                            <th scope="col">
                                Status
                            </th>

                            <th
                                scope="col"
                                class="text-end"
                                style="width: 11rem;"
                            >
                                Tindakan
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td class="text-center text-secondary">
                                    {{ $branches->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <p class="fw-semibold mb-1">
                                        {{ $branch->name }}
                                    </p>

                                    <span class="text-secondary small">
                                        {{ $branch->code }}
                                    </span>
                                </td>

                                <td>
                                    <span>
                                        {{ $branch->address }}
                                    </span>
                                </td>

                                <td>
                                    @if ($branch->hasGeofenceConfiguration())
                                        <span
                                            class="badge text-bg-success mb-2"
                                        >
                                            Sudah dikonfigurasi
                                        </span>

                                        <div class="small text-secondary">
                                            <div>
                                                Latitude:
                                                {{ number_format(
                                                    (float) $branch->latitude,
                                                    8,
                                                    '.',
                                                    ''
                                                ) }}
                                            </div>

                                            <div>
                                                Longitude:
                                                {{ number_format(
                                                    (float) $branch->longitude,
                                                    8,
                                                    '.',
                                                    ''
                                                ) }}
                                            </div>

                                            <div>
                                                Radius:
                                                {{ number_format(
                                                    (float) $branch->geofence_radius,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}
                                                meter
                                            </div>

                                            <div>
                                                Akurasi maksimum:
                                                {{ number_format(
                                                    (float) $branch->maximum_accuracy,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}
                                                meter
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge text-bg-warning mb-2">
                                            Belum dikonfigurasi
                                        </span>

                                        <p class="small text-secondary mb-0">
                                            Latitude dan longitude belum diisi.
                                        </p>
                                    @endif
                                </td>

                                <td>
                                    @if ($branch->isActive())
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
                                        class="d-inline-flex flex-wrap
                                            justify-content-end gap-2"
                                    >
                                        <a
                                            href="{{ route(
                                                'branches.show',
                                                $branch
                                            ) }}"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Detail
                                        </a>

                                        <a
                                            href="{{ route(
                                                'branches.edit',
                                                $branch
                                            ) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($branches->hasPages())
                <div class="border-top p-3 p-md-4">
                    {{ $branches
                        ->onEachSide(1)
                        ->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @endif
    </section>
@endsection