@php
    $branchModel = $branch ?? null;
@endphp

@once
    @push('styles')
        <style>
            .branch-form {
                --branch-form-surface: var(--neutral-0);
                --branch-form-border: var(--neutral-200);
                --branch-form-muted: var(--neutral-600);
                --branch-form-soft: var(--brand-50);
            }

            .branch-form-section {
                overflow: hidden;
                border: 1px solid var(--branch-form-border);
                border-radius: var(--radius-lg);
                background: var(--branch-form-surface);
            }

            .branch-form-section + .branch-form-section {
                margin-top: var(--space-4);
            }

            .branch-form-section-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: var(--space-4);
                padding: var(--space-4);
                border-bottom: 1px solid var(--branch-form-border);
                background: var(--neutral-25);
            }

            .branch-form-section-title {
                margin: 0;
                color: var(--neutral-900);
                font-size: 0.9375rem;
                font-weight: 800;
                letter-spacing: -0.01em;
            }

            .branch-form-section-copy {
                margin: var(--space-1) 0 0;
                color: var(--branch-form-muted);
                font-size: 0.75rem;
                line-height: 1.6;
            }

            .branch-form-section-icon {
                display: inline-flex;
                width: 2.5rem;
                height: 2.5rem;
                flex: 0 0 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md);
                color: var(--brand-700);
                background: var(--branch-form-soft);
                font-size: 1rem;
            }

            .branch-form-section-body {
                padding: var(--space-4);
            }

            .branch-form-field-card {
                height: 100%;
                padding: var(--space-4);
                border: 1px solid var(--neutral-200);
                border-radius: var(--radius-md);
                background: var(--neutral-25);
            }

            .branch-form-field {
                position: relative;
            }

            .branch-form-field-icon {
                position: absolute;
                z-index: 2;
                top: 2.875rem;
                left: var(--space-3);
                color: var(--neutral-500);
                pointer-events: none;
            }

            .branch-form-field.has-icon .form-control,
            .branch-form-field.has-icon .form-select {
                padding-left: 2.75rem;
            }

            .branch-form-field.has-icon textarea.form-control {
                padding-left: var(--space-3);
            }

            .branch-form-required {
                color: var(--danger-500);
            }

            .branch-form-help {
                display: flex;
                align-items: flex-start;
                gap: var(--space-2);
                margin-top: var(--space-2);
                color: var(--branch-form-muted);
                font-size: 0.6875rem;
                line-height: 1.55;
            }

            .branch-form-configuration-note {
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

            .branch-form-note-icon {
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

            .branch-form-note-title {
                margin: 0 0 var(--space-1);
                font-size: 0.875rem;
                font-weight: 800;
            }

            .branch-form-note-copy {
                margin: 0;
                font-size: 0.75rem;
                line-height: 1.65;
            }

            @media (min-width: 768px) {
                .branch-form-section-header,
                .branch-form-section-body {
                    padding: var(--space-5);
                }
            }

            @media (max-width: 575.98px) {
                .branch-form-section-header {
                    flex-direction: column-reverse;
                }
            }
        </style>
    @endpush
@endonce

<div class="branch-form">
    <section
        class="branch-form-section"
        aria-labelledby="branch-identity-heading"
    >
        <div class="branch-form-section-header">
            <div>
                <h2
                    id="branch-identity-heading"
                    class="branch-form-section-title"
                >
                    Identitas Cabang
                </h2>

                <p class="branch-form-section-copy">
                    Isi kode, nama, alamat, dan status operasional
                    cabang.
                </p>
            </div>

            <span class="branch-form-section-icon">
                <i
                    class="bi bi-building"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="branch-form-section-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="code"
                                class="form-label"
                            >
                                Kode cabang
                                <span class="branch-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-hash
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="code"
                                name="code"
                                value="{{ old(
                                    'code',
                                    $branchModel?->code
                                ) }}"
                                class="form-control
                                    @error('code')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-info-circle"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Gunakan kode unik yang singkat
                                    dan mudah dikenali.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="name"
                                class="form-label"
                            >
                                Nama cabang
                                <span class="branch-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-building
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old(
                                    'name',
                                    $branchModel?->name
                                ) }}"
                                class="form-control
                                    @error('name')
                                        is-invalid
                                    @enderror"
                                required
                            >

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field">
                            <label
                                for="address"
                                class="form-label"
                            >
                                Alamat
                                <span class="branch-form-required">
                                    *
                                </span>
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                class="form-control
                                    @error('address')
                                        is-invalid
                                    @enderror"
                                rows="3"
                                required
                            >{{ old(
                                'address',
                                $branchModel?->address
                            ) }}</textarea>

                            @error('address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-geo-alt"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Gunakan alamat operasional
                                    yang sesuai dengan titik
                                    geofence cabang.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="status"
                                class="form-label"
                            >
                                Status
                                <span class="branch-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-toggle-on
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <select
                                id="status"
                                name="status"
                                class="form-select
                                    @error('status')
                                        is-invalid
                                    @enderror"
                                required
                            >
                                <option
                                    value="active"
                                    @selected(
                                        old(
                                            'status',
                                            $branchModel?->status
                                                ?? 'active'
                                        )
                                        === 'active'
                                    )
                                >
                                    Aktif
                                </option>

                                <option
                                    value="inactive"
                                    @selected(
                                        old(
                                            'status',
                                            $branchModel?->status
                                        )
                                        === 'inactive'
                                    )
                                >
                                    Tidak aktif
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-shield-check"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Hanya cabang aktif yang dapat
                                    digunakan pada operasional
                                    presensi.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="branch-form-section"
        aria-labelledby="branch-geofence-heading"
    >
        <div class="branch-form-section-header">
            <div>
                <h2
                    id="branch-geofence-heading"
                    class="branch-form-section-title"
                >
                    Konfigurasi Geofence
                </h2>

                <p class="branch-form-section-copy">
                    Tentukan koordinat pusat, radius validasi, dan
                    batas akurasi GPS.
                </p>
            </div>

            <span class="branch-form-section-icon">
                <i
                    class="bi bi-geo-alt"
                    aria-hidden="true"
                ></i>
            </span>
        </div>

        <div class="branch-form-section-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="latitude"
                                class="form-label"
                            >
                                Latitude
                            </label>

                            <i
                                class="bi bi-compass
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="number"
                                id="latitude"
                                name="latitude"
                                value="{{ old(
                                    'latitude',
                                    $branchModel?->latitude
                                ) }}"
                                class="form-control
                                    @error('latitude')
                                        is-invalid
                                    @enderror"
                                step="0.00000001"
                            >

                            @error('latitude')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-pin-map"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Gunakan koordinat desimal
                                    hingga delapan angka di belakang
                                    koma.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="longitude"
                                class="form-label"
                            >
                                Longitude
                            </label>

                            <i
                                class="bi bi-compass
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="number"
                                id="longitude"
                                name="longitude"
                                value="{{ old(
                                    'longitude',
                                    $branchModel?->longitude
                                ) }}"
                                class="form-control
                                    @error('longitude')
                                        is-invalid
                                    @enderror"
                                step="0.00000001"
                            >

                            @error('longitude')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-pin-map"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Pastikan longitude sesuai dengan
                                    lokasi fisik kantor cabang.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="geofence_radius"
                                class="form-label"
                            >
                                Radius geofence
                                <span class="branch-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-bullseye
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="number"
                                id="geofence_radius"
                                name="geofence_radius"
                                value="{{ old(
                                    'geofence_radius',
                                    $branchModel
                                        ?->geofence_radius
                                        ?? 30
                                ) }}"
                                class="form-control
                                    @error('geofence_radius')
                                        is-invalid
                                    @enderror"
                                step="0.01"
                                required
                            >

                            @error('geofence_radius')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-rulers"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Nilai diukur dalam meter dari
                                    titik pusat cabang.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="branch-form-field-card">
                        <div class="branch-form-field has-icon">
                            <label
                                for="maximum_accuracy"
                                class="form-label"
                            >
                                Batas akurasi lokasi
                                <span class="branch-form-required">
                                    *
                                </span>
                            </label>

                            <i
                                class="bi bi-crosshair
                                    branch-form-field-icon"
                                aria-hidden="true"
                            ></i>

                            <input
                                type="number"
                                id="maximum_accuracy"
                                name="maximum_accuracy"
                                value="{{ old(
                                    'maximum_accuracy',
                                    $branchModel
                                        ?->maximum_accuracy
                                        ?? 25
                                ) }}"
                                class="form-control
                                    @error('maximum_accuracy')
                                        is-invalid
                                    @enderror"
                                step="0.01"
                                required
                            >

                            @error('maximum_accuracy')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="branch-form-help">
                                <i
                                    class="bi bi-broadcast-pin"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Presensi ditolak ketika akurasi
                                    GPS perangkat melebihi batas
                                    ini.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="branch-form-configuration-note"
                role="note"
            >
                <span class="branch-form-note-icon">
                    <i
                        class="bi bi-info-circle"
                        aria-hidden="true"
                    ></i>
                </span>

                <div>
                    <h3 class="branch-form-note-title">
                        Validasi lokasi presensi
                    </h3>

                    <p class="branch-form-note-copy">
                        Koordinat, radius geofence, dan batas
                        akurasi digunakan server untuk menghitung
                        serta memvalidasi lokasi presensi
                        karyawan.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
