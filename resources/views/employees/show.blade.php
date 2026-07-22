@extends('layouts.app')

@section('title', 'Detail Karyawan')

@section('content')
    @php
        $user = $employee->user;
        $branch = $employee->branch;

        $isEmploymentActive =
            $employee->employment_status === 'active';

        $isAccountActive =
            $user?->status === 'active';

        $lastLogin = $user?->last_login_at
            ? \Illuminate\Support\Carbon::parse(
                $user->last_login_at
            )
                ->timezone('Asia/Jakarta')
                ->format('d-m-Y H:i')
            : null;
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Detail Karyawan
            </h1>

            <p class="page-description">
                Informasi profil, akun, cabang, dan aktivitas
                {{ $employee->full_name }}.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('employees.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Karyawan
            </a>

            @if (auth()->user()->hasRole('hrd'))
                <a
                    href="{{ route('employees.edit', $employee) }}"
                    class="btn btn-primary"
                >
                    Edit Karyawan
                </a>
            @endif
        </div>
    </header>

    <section class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Jadwal Kerja
                </div>

                <div class="fs-3 fw-bold">
                    {{ $employee->schedules_count ?? 0 }}
                </div>

                <div class="small text-secondary">
                    Data penetapan jadwal
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Riwayat Presensi
                </div>

                <div class="fs-3 fw-bold">
                    {{ $employee->attendances_count ?? 0 }}
                </div>

                <div class="small text-secondary">
                    Data presensi tersimpan
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Permohonan Pertukaran
                </div>

                <div class="fs-3 fw-bold">
                    {{
                        $employee
                            ->requested_schedule_swaps_count
                        ?? 0
                    }}
                </div>

                <div class="small text-secondary">
                    Diajukan oleh karyawan
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="content-card h-100 p-3">
                <div class="small text-secondary mb-1">
                    Sebagai Mitra Pertukaran
                </div>

                <div class="fs-3 fw-bold">
                    {{
                        $employee
                            ->partnered_schedule_swaps_count
                        ?? 0
                    }}
                </div>

                <div class="small text-secondary">
                    Permohonan dari karyawan lain
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-7">
            <section class="content-card h-100">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Informasi Karyawan
                    </h2>

                    <p class="small text-secondary mb-0">
                        Identitas dan status profil karyawan.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 mb-2">
                            Nomor Karyawan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            <span class="fw-semibold">
                                {{ $employee->employee_number }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Nama Lengkap
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->full_name }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Jabatan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->position }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Nomor Telepon
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{ $employee->phone_number ?: '-' }}
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Status Karyawan
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            @if ($isEmploymentActive)
                                <span class="badge text-bg-success">
                                    Aktif
                                </span>
                            @else
                                <span class="badge text-bg-secondary">
                                    Tidak aktif
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Dibuat
                        </dt>

                        <dd class="col-sm-7 mb-3">
                            {{
                                $employee->created_at
                                    ?->timezone('Asia/Jakarta')
                                    ->format('d-m-Y H:i')
                                ?? '-'
                            }}
                            WIB
                        </dd>

                        <dt class="col-sm-5 mb-2">
                            Terakhir Diperbarui
                        </dt>

                        <dd class="col-sm-7 mb-0">
                            {{
                                $employee->updated_at
                                    ?->timezone('Asia/Jakarta')
                                    ->format('d-m-Y H:i')
                                ?? '-'
                            }}
                            WIB
                        </dd>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="content-card mb-4">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Informasi Akun
                    </h2>

                    <p class="small text-secondary mb-0">
                        Akun yang digunakan untuk masuk ke sistem.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($user !== null)
                        <dl class="row mb-0">
                            <dt class="col-sm-5 mb-2">
                                Email
                            </dt>

                            <dd class="col-sm-7 mb-3 text-break">
                                {{ $user->email }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Role
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                <span class="badge text-bg-primary">
                                    Karyawan
                                </span>
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Status Akun
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                @if ($isAccountActive)
                                    <span class="badge text-bg-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Tidak aktif
                                    </span>
                                @endif
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Login Terakhir
                            </dt>

                            <dd class="col-sm-7 mb-0">
                                @if ($lastLogin !== null)
                                    {{ $lastLogin }} WIB
                                @else
                                    <span class="text-secondary">
                                        Belum pernah masuk
                                    </span>
                                @endif
                            </dd>
                        </dl>
                    @else
                        <div
                            class="alert alert-warning mb-0"
                            role="alert"
                        >
                            Akun pengguna tidak ditemukan.
                        </div>
                    @endif
                </div>
            </section>

            <section class="content-card">
                <div class="border-bottom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-1">
                        Penempatan Cabang
                    </h2>

                    <p class="small text-secondary mb-0">
                        Cabang tempat karyawan ditugaskan.
                    </p>
                </div>

                <div class="p-3 p-md-4">
                    @if ($branch !== null)
                        <dl class="row mb-0">
                            <dt class="col-sm-5 mb-2">
                                Kode Cabang
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                <span class="fw-semibold">
                                    {{ $branch->code }}
                                </span>
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Nama Cabang
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $branch->name }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Alamat
                            </dt>

                            <dd class="col-sm-7 mb-3">
                                {{ $branch->address }}
                            </dd>

                            <dt class="col-sm-5 mb-2">
                                Status Cabang
                            </dt>

                            <dd class="col-sm-7 mb-0">
                                @if ($branch->status === 'active')
                                    <span class="badge text-bg-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge text-bg-warning">
                                        Tidak aktif
                                    </span>
                                @endif
                            </dd>
                        </dl>
                    @else
                        <div
                            class="alert alert-warning mb-0"
                            role="alert"
                        >
                            Data cabang tidak ditemukan.
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection