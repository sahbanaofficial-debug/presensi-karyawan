@extends('layouts.app')

@section('title', 'Tambah Pola Jadwal')

@push('styles')
    <style>
        .work-schedule-create-page {
            --work-schedule-create-surface: var(--neutral-0);
            --work-schedule-create-border: var(--neutral-200);
            --work-schedule-create-muted: var(--neutral-600);
            --work-schedule-create-soft: var(--brand-50);
        }

        .work-schedule-create-overview-card,
        .work-schedule-create-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--work-schedule-create-border);
            border-radius: var(--radius-lg);
            background: var(--work-schedule-create-surface);
            box-shadow: var(--shadow-xs);
        }

        .work-schedule-create-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--work-schedule-create-border);
            background: var(--neutral-25);
        }

        .work-schedule-create-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .work-schedule-create-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--work-schedule-create-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .work-schedule-create-overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .work-schedule-create-overview-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .work-schedule-create-overview-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--work-schedule-create-soft);
            font-size: 1rem;
        }

        .work-schedule-create-overview-label {
            color: var(--work-schedule-create-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .work-schedule-create-overview-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .work-schedule-create-form-body {
            padding: var(--space-4);
        }

        .work-schedule-create-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .work-schedule-create-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .work-schedule-create-overview-grid,
            .work-schedule-create-form-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .work-schedule-create-overview-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .work-schedule-create-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .work-schedule-create-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="work-schedule-create-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Tambah Pola Jadwal
                </h1>

                <p class="page-description">
                    Tambahkan pola jam kerja dan aturan waktu
                    presensi karyawan.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('work-schedules.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Pola Jadwal
                </a>
            </div>
        </header>

        <section
            class="work-schedule-create-overview-card"
            aria-labelledby="work-schedule-create-overview-heading"
        >
            <div class="work-schedule-create-card-header">
                <div>
                    <h2
                        id="work-schedule-create-overview-heading"
                        class="work-schedule-create-card-title"
                    >
                        Informasi Pembuatan Pola Jadwal
                    </h2>

                    <p class="work-schedule-create-card-copy">
                        Tentukan waktu kerja dan batas operasional
                        presensi untuk pola jadwal baru.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data baru
                </span>
            </div>

            <div class="work-schedule-create-overview-grid">
                <article
                    class="work-schedule-create-overview-item"
                >
                    <span
                        class="work-schedule-create-overview-icon"
                    >
                        <i
                            class="bi bi-calendar-week"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-create-overview-label"
                    >
                        Identitas Jadwal
                    </div>

                    <div
                        class="work-schedule-create-overview-value"
                    >
                        Nama pola dan status penggunaan jadwal.
                    </div>
                </article>

                <article
                    class="work-schedule-create-overview-item"
                >
                    <span
                        class="work-schedule-create-overview-icon"
                    >
                        <i
                            class="bi bi-clock"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-create-overview-label"
                    >
                        Jam Kerja
                    </div>

                    <div
                        class="work-schedule-create-overview-value"
                    >
                        Jam masuk dan jam pulang dalam WIB.
                    </div>
                </article>

                <article
                    class="work-schedule-create-overview-item"
                >
                    <span
                        class="work-schedule-create-overview-icon"
                    >
                        <i
                            class="bi bi-stopwatch"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div
                        class="work-schedule-create-overview-label"
                    >
                        Aturan Presensi
                    </div>

                    <div
                        class="work-schedule-create-overview-value"
                    >
                        Pembukaan, toleransi, dan batas presensi.
                    </div>
                </article>
            </div>
        </section>

        <section
            class="work-schedule-create-form-card"
            aria-labelledby="create-work-schedule-heading"
        >
            <div class="work-schedule-create-card-header">
                <div>
                    <h2
                        id="create-work-schedule-heading"
                        class="work-schedule-create-card-title"
                    >
                        Formulir Pola Jadwal Baru
                    </h2>

                    <p class="work-schedule-create-card-copy">
                        Kolom dengan tanda
                        <span class="text-danger">*</span>
                        wajib diisi.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    Wajib diisi
                </span>
            </div>

            <div class="work-schedule-create-form-body">
                <form
                    method="POST"
                    action="{{ route('work-schedules.store') }}"
                >
                    @csrf

                    @include('work-schedules._form')

                    <div class="work-schedule-create-actions">
                        <a
                            href="{{ route(
                                'work-schedules.index'
                            ) }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i
                                class="bi bi-save me-2"
                                aria-hidden="true"
                            ></i>

                            Simpan Pola Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
