@extends('layouts.app')

@section('title', 'Buka Sesi Presensi')

@section('content')
    @php
        $availableBranchCount = $branches->count();

        $canOpenSession = $availableBranchCount > 0;

        $authenticatedUser = auth()->user();

        $creatorRoleLabel = match (
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
                Buka Sesi Presensi
            </h1>

            <p class="page-description">
                Buat sesi presensi masuk atau pulang dengan
                QR Code dinamis berbasis TOTP.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('attendance-sessions.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Sesi
            </a>
        </div>
    </header>

    @if ($availableBranchCount === 0)
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Cabang yang memenuhi syarat tidak tersedia.
            </div>

            <p class="mb-3">
                Sesi presensi belum dapat dibuka karena tidak
                terdapat cabang aktif dengan konfigurasi
                geofence yang lengkap.
            </p>

            @if ($authenticatedUser?->role === 'hrd')
                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-sm btn-outline-dark"
                >
                    Periksa Data Cabang
                </a>
            @endif
        </div>
    @endif

    <section
        class="content-card p-3 p-md-4 mb-4"
        aria-labelledby="session-information-heading"
    >
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-3"
        >
            <div>
                <h2
                    id="session-information-heading"
                    class="h5 fw-bold mb-1"
                >
                    Informasi Pembukaan Sesi
                </h2>

                <p class="small text-secondary mb-0">
                    Sesi akan dibuat oleh
                    {{ $creatorRoleLabel }}
                    dengan akun
                    {{ $authenticatedUser?->name ?? '-' }}.
                </p>
            </div>

            <div>
                <span class="badge text-bg-light border px-3 py-2">
                    {{ $availableBranchCount }}
                    cabang tersedia
                </span>
            </div>
        </div>
    </section>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="create-attendance-session-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="create-attendance-session-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Sesi Presensi
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('attendance-sessions.store') }}"
        >
            @csrf

            @include(
                'attendance-sessions._form',
                [
                    'branches' => $branches,
                ]
            )

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{ route('attendance-sessions.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled(! $canOpenSession)
                >
                    Buka Sesi Presensi
                </button>
            </div>
        </form>
    </section>

    <section class="content-card p-3 p-md-4 mt-4">
        <h2 class="h5 fw-bold mb-3">
            Mekanisme QR Code Dinamis
        </h2>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1">
                        1. Secret Dibuat Server
                    </div>

                    <p class="small text-secondary mb-0">
                        Sistem membuat secret TOTP secara acak
                        dan menyimpannya dalam bentuk terenkripsi.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1">
                        2. Token Berubah Berkala
                    </div>

                    <p class="small text-secondary mb-0">
                        Kode enam digit diperbarui setiap
                        30 detik selama sesi masih aktif.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-1">
                        3. Payload Terbatas
                    </div>

                    <p class="small text-secondary mb-0">
                        QR Code hanya memuat UUID publik sesi
                        dan token TOTP, tanpa secret internal.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection