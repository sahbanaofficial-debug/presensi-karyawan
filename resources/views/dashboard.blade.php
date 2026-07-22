@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $employee = $user?->employee;

        $dashboardRoleLabel = match ($user?->role) {
            'hrd' => 'HRD',
            'admin' => 'Admin Operasional',
            'employee' => 'Karyawan',
            default => 'Pengguna',
        };
    @endphp

    <header class="page-header">
        <h1 class="page-title">
            Dashboard
        </h1>

        <p class="page-description">
            Ringkasan akun dan hak akses pengguna.
        </p>
    </header>

    <section
        class="content-card overflow-hidden mb-4"
        aria-labelledby="welcome-heading"
    >
        <div
            class="p-4 p-md-5 text-white"
            style="
                background:
                    linear-gradient(
                        135deg,
                        #16324f,
                        #24557a
                    );
            "
        >
            <p class="mb-2 opacity-75">
                Selamat datang
            </p>

            <h2
                id="welcome-heading"
                class="h2 fw-bold mb-2"
            >
                {{ $user?->name }}
            </h2>

            <p class="mb-0 opacity-75">
                Anda telah masuk sebagai {{ $dashboardRoleLabel }}
                pada Sistem Presensi Karyawan PT Gadai Ogan Baru.
            </p>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="content-card h-100 p-4">
                <h2 class="h5 fw-bold mb-4">
                    Informasi Akun
                </h2>

                <dl class="row mb-0">
                    <dt class="col-sm-5 text-secondary fw-normal mb-2">
                        Nama pengguna
                    </dt>

                    <dd class="col-sm-7 fw-semibold mb-3">
                        {{ $user?->name }}
                    </dd>

                    <dt class="col-sm-5 text-secondary fw-normal mb-2">
                        Alamat email
                    </dt>

                    <dd class="col-sm-7 fw-semibold mb-3">
                        {{ $user?->email }}
                    </dd>

                    <dt class="col-sm-5 text-secondary fw-normal mb-2">
                        Peran
                    </dt>

                    <dd class="col-sm-7 mb-3">
                        <span class="badge text-bg-primary">
                            {{ $dashboardRoleLabel }}
                        </span>
                    </dd>

                    <dt class="col-sm-5 text-secondary fw-normal mb-2">
                        Status akun
                    </dt>

                    <dd class="col-sm-7 mb-3">
                        @if ($user?->isActive())
                            <span class="badge text-bg-success">
                                Aktif
                            </span>
                        @else
                            <span class="badge text-bg-secondary">
                                Tidak aktif
                            </span>
                        @endif
                    </dd>

                    <dt class="col-sm-5 text-secondary fw-normal mb-2">
                        Login terakhir
                    </dt>

                    <dd class="col-sm-7 fw-semibold mb-0">
                        @if ($user?->last_login_at)
                            {{ $user->last_login_at->format('d-m-Y H:i') }}
                            WIB
                        @else
                            -
                        @endif
                    </dd>
                </dl>
            </section>
        </div>

        <div class="col-lg-6">
            @if ($user?->hasRole('hrd'))
                <section class="content-card h-100 p-4">
                    <h2 class="h5 fw-bold mb-3">
                        Akses HRD
                    </h2>

                    <p class="text-secondary">
                        HRD memiliki kewenangan untuk mengelola kebijakan,
                        data cabang, data karyawan, jadwal, persetujuan,
                        koreksi, dan laporan presensi.
                    </p>

                    <div class="d-grid d-sm-flex gap-2">
                        <a
                            href="{{ route('branches.index') }}"
                            class="btn btn-primary"
                        >
                            Buka Data Cabang
                        </a>
                    </div>
                </section>
            @elseif ($user?->hasRole('admin'))
                <section class="content-card h-100 p-4">
                    <h2 class="h5 fw-bold mb-3">
                        Akses Admin Operasional
                    </h2>

                    <p class="text-secondary mb-0">
                        Admin membantu operasional presensi harian,
                        pembukaan sesi, penayangan QR Code, pemantauan,
                        serta pengelolaan data sesuai kewenangan.
                    </p>

                    <div class="alert alert-info mt-4 mb-0">
                        Modul sesi presensi dan monitoring akan tersedia
                        pada tahap pengembangan berikutnya.
                    </div>
                </section>
            @elseif ($user?->hasRole('employee') && $employee !== null)
                <section class="content-card h-100 p-4">
                    <h2 class="h5 fw-bold mb-4">
                        Informasi Karyawan
                    </h2>

                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-secondary fw-normal mb-2">
                            Nomor karyawan
                        </dt>

                        <dd class="col-sm-7 fw-semibold mb-3">
                            {{ $employee->employee_number }}
                        </dd>

                        <dt class="col-sm-5 text-secondary fw-normal mb-2">
                            Nama lengkap
                        </dt>

                        <dd class="col-sm-7 fw-semibold mb-3">
                            {{ $employee->full_name }}
                        </dd>

                        <dt class="col-sm-5 text-secondary fw-normal mb-2">
                            Jabatan
                        </dt>

                        <dd class="col-sm-7 fw-semibold mb-3">
                            {{ $employee->position }}
                        </dd>

                        <dt class="col-sm-5 text-secondary fw-normal mb-2">
                            Cabang
                        </dt>

                        <dd class="col-sm-7 fw-semibold mb-3">
                            {{ $employee->branch?->name ?? '-' }}
                        </dd>

                        <dt class="col-sm-5 text-secondary fw-normal mb-2">
                            Status karyawan
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            @if ($employee->isActive())
                                <span class="badge text-bg-success">
                                    Aktif
                                </span>
                            @else
                                <span class="badge text-bg-secondary">
                                    Tidak aktif
                                </span>
                            @endif
                        </dd>
                    </dl>

                    <div class="alert alert-info mt-4 mb-0">
                        Jadwal hari ini dan status presensi akan ditampilkan
                        setelah modul jadwal dan presensi selesai dibangun.
                    </div>
                </section>
            @else
                <section class="content-card h-100 p-4">
                    <h2 class="h5 fw-bold mb-3">
                        Informasi Hak Akses
                    </h2>

                    <p class="text-secondary mb-0">
                        Informasi profil pengguna belum tersedia.
                    </p>
                </section>
            @endif
        </div>
    </div>
@endsection