@extends('layouts.app')

@section('title', 'Tambah Presensi Manual')

@section('content')
    <header
        class="page-header d-md-flex
            align-items-start justify-content-between
            gap-3"
    >
        <div>
            <h1 class="page-title">
                Tambah Presensi Manual
            </h1>

            <p class="page-description">
                Tambahkan catatan presensi berdasarkan
                hasil pemeriksaan dan verifikasi HRD.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{
                    route(
                        'attendance-monitoring.index'
                    )
                }}"
                class="btn btn-outline-secondary"
            >
                Kembali ke Monitoring
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-lg-row
                align-items-lg-start gap-3"
        >
            <div class="flex-grow-1">
                <h2 class="h5 fw-bold mb-2">
                    Ketentuan Presensi Manual
                </h2>

                <p class="text-secondary mb-0">
                    Presensi manual digunakan ketika catatan
                    presensi tidak dapat diperoleh melalui
                    pemindaian QR Code, tetapi kehadiran
                    karyawan telah diverifikasi oleh HRD.
                </p>
            </div>

            <span
                class="badge text-bg-warning
                    align-self-start"
            >
                Khusus HRD
            </span>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Sumber Presensi
                </div>

                <div class="fw-semibold">
                    Koreksi Manual
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Validasi QR dan TOTP
                </div>

                <div class="fw-semibold">
                    Tidak digunakan
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Validasi Lokasi
                </div>

                <div class="fw-semibold">
                    Tidak digunakan
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="small text-secondary mb-1">
                    Jejak Audit
                </div>

                <div class="fw-semibold">
                    Wajib disimpan
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4 mb-0">
            Sistem tetap memeriksa jadwal kerja dan mencegah
            presensi masuk atau pulang ganda pada jadwal yang
            sama. Status tepat waktu atau terlambat dihitung
            otomatis berdasarkan waktu presensi dan pola jadwal.
        </div>
    </section>

    @include(
        'attendance-corrections._form',
        [
            'formAction' => route(
                'attendance-corrections.store'
            ),

            'formMethod' => 'POST',

            'submitLabel' =>
                'Simpan Presensi Manual',

            'cancelUrl' => route(
                'attendance-monitoring.index'
            ),
        ]
    )
@endsection