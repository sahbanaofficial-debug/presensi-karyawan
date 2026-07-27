@extends('layouts.app')

@section('title', 'Tambah Presensi Manual')

@push('styles')
    <style>
        .manual-attendance-page {
            --manual-surface: var(--neutral-0);
            --manual-border: var(--neutral-200);
            --manual-muted: var(--neutral-600);
            --manual-orange-soft: var(--brand-50);
        }

        .manual-attendance-intro {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--manual-border);
            border-radius: var(--radius-lg);
            background: var(--manual-surface);
            box-shadow: var(--shadow-xs);
        }

        .manual-attendance-intro-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--manual-border);
            background: var(--neutral-25);
        }

        .manual-attendance-intro-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .manual-attendance-intro-copy {
            max-width: 48rem;
            margin: var(--space-1) 0 0;
            color: var(--manual-muted);
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        .manual-attendance-intro-body {
            padding: var(--space-4);
        }

        .manual-attendance-rule-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .manual-attendance-rule {
            min-width: 0;
            padding: var(--space-3);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .manual-attendance-rule-icon {
            display: inline-flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--manual-orange-soft);
            font-size: 0.9375rem;
        }

        .manual-attendance-rule-label {
            color: var(--manual-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .manual-attendance-rule-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .manual-attendance-info {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            margin-top: var(--space-4);
            padding: var(--space-4);
            border: 1px solid #cde0eb;
            border-radius: var(--radius-md);
            color: var(--info-700);
            background: var(--info-50);
        }

        .manual-attendance-info-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: rgba(59, 126, 161, 0.09);
            font-size: 1rem;
        }

        .manual-attendance-info-title {
            margin: 0 0 var(--space-1);
            font-size: 0.875rem;
            font-weight: 800;
        }

        .manual-attendance-info-copy {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        @media (min-width: 768px) {
            .manual-attendance-intro-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 1199.98px) {
            .manual-attendance-rule-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .manual-attendance-intro-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .manual-attendance-rule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="manual-attendance-page">
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
                    href="{{ route(
                        'attendance-monitoring.index'
                    ) }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Kembali ke Monitoring
                </a>
            </div>
        </header>

        <section
            class="manual-attendance-intro"
            aria-labelledby="manual-attendance-rule-heading"
        >
            <div class="manual-attendance-intro-header">
                <div>
                    <h2
                        id="manual-attendance-rule-heading"
                        class="manual-attendance-intro-title"
                    >
                        Ketentuan Presensi Manual
                    </h2>

                    <p class="manual-attendance-intro-copy">
                        Presensi manual digunakan ketika catatan
                        presensi tidak dapat diperoleh melalui
                        pemindaian QR Code, tetapi kehadiran
                        karyawan telah diverifikasi oleh HRD.
                    </p>
                </div>

                <span class="badge text-bg-warning">
                    Khusus HRD
                </span>
            </div>

            <div class="manual-attendance-intro-body">
                <div class="manual-attendance-rule-grid">
                    <article class="manual-attendance-rule">
                        <span class="manual-attendance-rule-icon">
                            <i
                                class="bi bi-pencil-square"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div class="manual-attendance-rule-label">
                            Sumber Presensi
                        </div>

                        <div class="manual-attendance-rule-value">
                            Koreksi Manual
                        </div>
                    </article>

                    <article class="manual-attendance-rule">
                        <span class="manual-attendance-rule-icon">
                            <i
                                class="bi bi-qr-code"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div class="manual-attendance-rule-label">
                            Validasi QR dan TOTP
                        </div>

                        <div class="manual-attendance-rule-value">
                            Tidak digunakan
                        </div>
                    </article>

                    <article class="manual-attendance-rule">
                        <span class="manual-attendance-rule-icon">
                            <i
                                class="bi bi-geo-alt"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div class="manual-attendance-rule-label">
                            Validasi Lokasi
                        </div>

                        <div class="manual-attendance-rule-value">
                            Tidak digunakan
                        </div>
                    </article>

                    <article class="manual-attendance-rule">
                        <span class="manual-attendance-rule-icon">
                            <i
                                class="bi bi-clock-history"
                                aria-hidden="true"
                            ></i>
                        </span>

                        <div class="manual-attendance-rule-label">
                            Jejak Audit
                        </div>

                        <div class="manual-attendance-rule-value">
                            Wajib disimpan
                        </div>
                    </article>
                </div>

                <div class="manual-attendance-info">
                    <span class="manual-attendance-info-icon">
                        <i
                            class="bi bi-info-circle"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div>
                        <h3 class="manual-attendance-info-title">
                            Validasi sistem tetap berlaku
                        </h3>

                        <p class="manual-attendance-info-copy">
                            Sistem tetap memeriksa jadwal kerja
                            dan mencegah presensi masuk atau
                            pulang ganda pada jadwal yang sama.
                            Status tepat waktu atau terlambat
                            dihitung otomatis berdasarkan waktu
                            presensi dan pola jadwal.
                        </p>
                    </div>
                </div>
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
    </div>
@endsection
