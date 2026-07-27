@extends('layouts.app')

@section('title', 'Tambah Cabang')

@push('styles')
    <style>
        .branch-create-page {
            --branch-create-surface: var(--neutral-0);
            --branch-create-border: var(--neutral-200);
            --branch-create-muted: var(--neutral-600);
            --branch-create-soft: var(--brand-50);
        }

        .branch-create-intro-card,
        .branch-create-form-card {
            overflow: hidden;
            margin-bottom: var(--space-5);
            border: 1px solid var(--branch-create-border);
            border-radius: var(--radius-lg);
            background: var(--branch-create-surface);
            box-shadow: var(--shadow-xs);
        }

        .branch-create-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-4);
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--branch-create-border);
            background: var(--neutral-25);
        }

        .branch-create-card-title {
            margin: 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .branch-create-card-copy {
            margin: var(--space-1) 0 0;
            color: var(--branch-create-muted);
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .branch-create-intro-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
            padding: var(--space-4);
        }

        .branch-create-intro-item {
            min-width: 0;
            padding: var(--space-4);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-25);
        }

        .branch-create-intro-icon {
            display: inline-flex;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-3);
            border-radius: var(--radius-md);
            color: var(--brand-700);
            background: var(--branch-create-soft);
            font-size: 1rem;
        }

        .branch-create-intro-label {
            color: var(--branch-create-muted);
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .branch-create-intro-value {
            margin-top: var(--space-1);
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1.5;
        }

        .branch-create-form-body {
            padding: var(--space-4);
        }

        .branch-create-actions {
            display: flex;
            flex-direction: column-reverse;
            justify-content: flex-end;
            gap: var(--space-2);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--neutral-200);
        }

        @media (min-width: 576px) {
            .branch-create-actions {
                flex-direction: row;
            }
        }

        @media (min-width: 768px) {
            .branch-create-intro-grid,
            .branch-create-form-body {
                padding: var(--space-5);
            }
        }

        @media (max-width: 991.98px) {
            .branch-create-intro-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .branch-create-card-header {
                flex-direction: column;
                padding: var(--space-4);
            }

            .branch-create-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="branch-create-page">
        <header
            class="page-header d-md-flex align-items-start
                justify-content-between gap-3"
        >
            <div>
                <h1 class="page-title">
                    Tambah Cabang
                </h1>

                <p class="page-description">
                    Masukkan identitas dan konfigurasi lokasi cabang.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i
                        class="bi bi-arrow-left me-2"
                        aria-hidden="true"
                    ></i>

                    Daftar Cabang
                </a>
            </div>
        </header>

        <section
            class="branch-create-intro-card"
            aria-labelledby="branch-create-overview-heading"
        >
            <div class="branch-create-card-header">
                <div>
                    <h2
                        id="branch-create-overview-heading"
                        class="branch-create-card-title"
                    >
                        Informasi Pembuatan Cabang
                    </h2>

                    <p class="branch-create-card-copy">
                        Data cabang digunakan pada penempatan
                        karyawan, sesi presensi, dan validasi
                        geofence.
                    </p>
                </div>

                <span class="badge text-bg-primary">
                    Data baru
                </span>
            </div>

            <div class="branch-create-intro-grid">
                <article class="branch-create-intro-item">
                    <span class="branch-create-intro-icon">
                        <i
                            class="bi bi-building"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-create-intro-label">
                        Identitas
                    </div>

                    <div class="branch-create-intro-value">
                        Kode, nama, alamat, dan status cabang.
                    </div>
                </article>

                <article class="branch-create-intro-item">
                    <span class="branch-create-intro-icon">
                        <i
                            class="bi bi-geo-alt"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-create-intro-label">
                        Lokasi
                    </div>

                    <div class="branch-create-intro-value">
                        Koordinat latitude dan longitude cabang.
                    </div>
                </article>

                <article class="branch-create-intro-item">
                    <span class="branch-create-intro-icon">
                        <i
                            class="bi bi-bullseye"
                            aria-hidden="true"
                        ></i>
                    </span>

                    <div class="branch-create-intro-label">
                        Geofence
                    </div>

                    <div class="branch-create-intro-value">
                        Radius dan batas akurasi validasi lokasi.
                    </div>
                </article>
            </div>
        </section>

        <section
            class="branch-create-form-card"
            aria-labelledby="branch-create-form-heading"
        >
            <div class="branch-create-card-header">
                <div>
                    <h2
                        id="branch-create-form-heading"
                        class="branch-create-card-title"
                    >
                        Formulir Cabang
                    </h2>

                    <p class="branch-create-card-copy">
                        Lengkapi seluruh data wajib sebelum
                        menyimpan cabang.
                    </p>
                </div>

                <span class="badge text-bg-light border">
                    Wajib diisi
                </span>
            </div>

            <div class="branch-create-form-body">
                <form
                    method="POST"
                    action="{{ route('branches.store') }}"
                >
                    @csrf

                    @include('branches._form')

                    <div class="branch-create-actions">
                        <a
                            href="{{ route('branches.index') }}"
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

                            Simpan Cabang
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
