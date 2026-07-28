@extends('layouts.app')

@section('title', 'Detail Terminal Cabang')

@section('content')
<div class="container-fluid px-0">
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-start
            gap-3 mb-4"
    >
        <div>
            <a
                href="{{ route('branch-terminals.index') }}"
                class="text-decoration-none"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Terminal Cabang
            </a>

            <h1 class="h3 mt-3 mb-1">
                {{ $terminal->name }}
            </h1>

            <p class="text-secondary mb-0">
                {{ $terminal->branch->code }} â€”
                {{ $terminal->branch->name }}
            </p>
        </div>

        @if ($terminal->isPending())
            <span class="badge text-bg-warning fs-6">
                Pending
            </span>
        @elseif ($terminal->isActive())
            <span class="badge text-bg-success fs-6">
                Aktif
            </span>
        @else
            <span class="badge text-bg-secondary fs-6">
                Dicabut
            </span>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (session('activation_code'))
        <div class="alert alert-warning border-warning shadow-sm">
            <h2 class="h5">
                Kode Aktivasi Sekali Tampil
            </h2>

            <p class="mb-2">
                Masukkan kode berikut pada perangkat terminal.
                Salin sekarang karena kode mentah tidak disimpan
                dan tidak dapat dilihat kembali.
            </p>

            <div
                class="display-6 fw-bold font-monospace
                    letter-spacing"
            >
                {{ session('activation_code') }}
            </div>

            <div class="small mt-2">
                Berlaku sampai:
                <strong>
                    {{
                        session(
                            'activation_expires_at',
                            '-'
                        )
                    }}
                </strong>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">
                        Informasi Terminal
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-md-4">Nama</dt>
                        <dd class="col-md-8">
                            {{ $terminal->name }}
                        </dd>

                        <dt class="col-md-4">Public ID</dt>
                        <dd
                            class="col-md-8 font-monospace
                                text-break"
                        >
                            {{ $terminal->public_id }}
                        </dd>

                        <dt class="col-md-4">Cabang</dt>
                        <dd class="col-md-8">
                            {{ $terminal->branch->code }} â€”
                            {{ $terminal->branch->name }}
                        </dd>

                        <dt class="col-md-4">Didaftarkan oleh</dt>
                        <dd class="col-md-8">
                            {{ $creator?->name ?? '-' }}
                        </dd>

                        <dt class="col-md-4">Didaftarkan pada</dt>
                        <dd class="col-md-8">
                            {{
                                $terminal->created_at
                                    ?->format('d M Y H:i:s')
                                ?? '-'
                            }}
                        </dd>

                        <dt class="col-md-4">Aktivasi kedaluwarsa</dt>
                        <dd class="col-md-8">
                            {{
                                $terminal
                                    ->activation_expires_at
                                    ?->format('d M Y H:i:s')
                                ?? '-'
                            }}
                        </dd>

                        <dt class="col-md-4">Diaktifkan pada</dt>
                        <dd class="col-md-8">
                            {{
                                $terminal->activated_at
                                    ?->format('d M Y H:i:s')
                                ?? '-'
                            }}
                        </dd>

                        <dt class="col-md-4">Terakhir terhubung</dt>
                        <dd class="col-md-8">
                            {{
                                $terminal->last_seen_at
                                    ?->format('d M Y H:i:s')
                                ?? '-'
                            }}
                        </dd>

                        <dt class="col-md-4">Dicabut oleh</dt>
                        <dd class="col-md-8">
                            {{ $revoker?->name ?? '-' }}
                        </dd>

                        <dt class="col-md-4">Dicabut pada</dt>
                        <dd class="col-md-8">
                            {{
                                $terminal->revoked_at
                                    ?->format('d M Y H:i:s')
                                ?? '-'
                            }}
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">
                        Jejak Penggunaan
                    </h2>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div
                                    class="text-secondary small"
                                >
                                    Transaksi presensi
                                </div>

                                <div class="fs-3 fw-semibold">
                                    {{ $attendanceCount }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div
                                    class="text-secondary small"
                                >
                                    Log validasi
                                </div>

                                <div class="fs-3 fw-semibold">
                                    {{ $validationLogCount }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Tindakan</h2>
                </div>

                <div class="card-body">
                    @if ($terminal->isPending())
                        <p class="text-secondary">
                            Buat kode aktivasi baru apabila kode
                            sebelumnya hilang atau kedaluwarsa.
                        </p>

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'branch-terminals.renew-activation',
                                    $terminal
                                )
                            }}"
                            class="mb-3"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-warning w-100"
                            >
                                Perbarui Kode Aktivasi
                            </button>
                        </form>
                    @endif

                    @if (! $terminal->isRevoked())
                        <p class="text-secondary">
                            Pencabutan menghapus credential aktivasi
                            dan token perangkat. Tindakan ini tidak
                            dapat dibatalkan.
                        </p>

                        <form
                            method="POST"
                            action="{{
                                route(
                                    'branch-terminals.revoke',
                                    $terminal
                                )
                            }}"
                            onsubmit="return confirm(
                                'Cabut akses terminal ini?'
                            )"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-outline-danger w-100"
                            >
                                Cabut Akses Terminal
                            </button>
                        </form>
                    @else
                        <div class="alert alert-secondary mb-0">
                            Terminal telah dicabut dan tidak dapat
                            digunakan untuk autentikasi maupun QR.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection