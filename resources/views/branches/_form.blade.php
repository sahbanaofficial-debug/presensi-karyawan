@php
    $branchModel = $branch ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="code" class="form-label">
            Kode cabang
        </label>

        <input
            type="text"
            id="code"
            name="code"
            value="{{ old('code', $branchModel?->code) }}"
            class="form-control @error('code') is-invalid @enderror"
            required
        >

        @error('code')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="name" class="form-label">
            Nama cabang
        </label>

        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $branchModel?->name) }}"
            class="form-control @error('name') is-invalid @enderror"
            required
        >

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label">
            Alamat
        </label>

        <textarea
            id="address"
            name="address"
            class="form-control @error('address') is-invalid @enderror"
            rows="3"
            required
        >{{ old('address', $branchModel?->address) }}</textarea>

        @error('address')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="latitude" class="form-label">
            Latitude
        </label>

        <input
            type="number"
            id="latitude"
            name="latitude"
            value="{{ old('latitude', $branchModel?->latitude) }}"
            class="form-control @error('latitude') is-invalid @enderror"
            step="0.00000001"
        >

        @error('latitude')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="longitude" class="form-label">
            Longitude
        </label>

        <input
            type="number"
            id="longitude"
            name="longitude"
            value="{{ old('longitude', $branchModel?->longitude) }}"
            class="form-control @error('longitude') is-invalid @enderror"
            step="0.00000001"
        >

        @error('longitude')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="geofence_radius" class="form-label">
            Radius geofence
        </label>

        <input
            type="number"
            id="geofence_radius"
            name="geofence_radius"
            value="{{ old(
                'geofence_radius',
                $branchModel?->geofence_radius ?? 30
            ) }}"
            class="form-control @error('geofence_radius') is-invalid @enderror"
            step="0.01"
            required
        >

        @error('geofence_radius')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="maximum_accuracy" class="form-label">
            Batas akurasi lokasi
        </label>

        <input
            type="number"
            id="maximum_accuracy"
            name="maximum_accuracy"
            value="{{ old(
                'maximum_accuracy',
                $branchModel?->maximum_accuracy ?? 25
            ) }}"
            class="form-control @error('maximum_accuracy') is-invalid @enderror"
            step="0.01"
            required
        >

        @error('maximum_accuracy')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="status" class="form-label">
            Status
        </label>

        <select
            id="status"
            name="status"
            class="form-select @error('status') is-invalid @enderror"
            required
        >
            <option
                value="active"
                @selected(
                    old('status', $branchModel?->status ?? 'active')
                    === 'active'
                )
            >
                Aktif
            </option>

            <option
                value="inactive"
                @selected(
                    old('status', $branchModel?->status)
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
    </div>
</div>