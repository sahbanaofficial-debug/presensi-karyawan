@extends('layouts.app')

@section('title', 'Catat Permohonan Pertukaran Jadwal')

@section('content')
    @php
        $activeEmployeeCount = $employees->count();

        $canCreateRequest = $activeEmployeeCount >= 2;

        $authenticatedUser = auth()->user();

        $recorderRoleLabel = match (
            $authenticatedUser?->role
        ) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            default => 'Pengguna',
        };
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Catat Permohonan Pertukaran Jadwal
            </h1>

            <p class="page-description">
                Catat permohonan pertukaran jadwal antara
                dua karyawan aktif.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{
                    route(
                        'schedule-swap-requests.index'
                    )
                }}"
                class="btn btn-outline-secondary"
            >
                Daftar Permohonan
            </a>
        </div>
    </header>

    @if ($activeEmployeeCount === 0)
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Karyawan aktif tidak tersedia.
            </div>

            <p class="mb-3">
                Permohonan pertukaran jadwal belum dapat
                dicatat karena tidak terdapat karyawan aktif.
            </p>

            <a
                href="{{ route('employees.index') }}"
                class="btn btn-sm btn-outline-dark"
            >
                Lihat Data Karyawan
            </a>
        </div>
    @elseif ($activeEmployeeCount === 1)
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Jumlah karyawan aktif belum mencukupi.
            </div>

            <p class="mb-3">
                Pertukaran jadwal memerlukan minimal dua
                karyawan aktif yang berbeda.
            </p>

            <a
                href="{{ route('employees.index') }}"
                class="btn btn-sm btn-outline-dark"
            >
                Lihat Data Karyawan
            </a>
        </div>
    @endif

    <section
        class="content-card p-3 p-md-4 mb-4"
        aria-labelledby="record-information-heading"
    >
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-3"
        >
            <div>
                <h2
                    id="record-information-heading"
                    class="h5 fw-bold mb-1"
                >
                    Informasi Pencatatan
                </h2>

                <p class="small text-secondary mb-0">
                    Permohonan ini sedang dicatat oleh
                    {{ $recorderRoleLabel }}.
                </p>
            </div>

            <div>
                <span class="badge text-bg-light border px-3 py-2">
                    {{ $activeEmployeeCount }}
                    karyawan aktif
                </span>
            </div>
        </div>
    </section>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="create-schedule-swap-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="create-schedule-swap-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Permohonan
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        <form
            method="POST"
            action="{{
                route(
                    'schedule-swap-requests.store'
                )
            }}"
        >
            @csrf

            @include(
                'schedule-swap-requests._form',
                [
                    'employees' => $employees,
                ]
            )

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{
                        route(
                            'schedule-swap-requests.index'
                        )
                    }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled(! $canCreateRequest)
                >
                    Simpan Permohonan
                </button>
            </div>
        </form>
    </section>
@endsection