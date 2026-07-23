@php
    $selectedRequesterEmployeeId = old(
        'requester_employee_id'
    );

    $selectedPartnerEmployeeId = old(
        'partner_employee_id'
    );

    $selectedRequesterDate = old(
        'requester_date',
        ''
    );

    $selectedPartnerDate = old(
        'partner_date',
        ''
    );

    $reasonValue = old(
        'reason',
        ''
    );
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="border-bottom pb-2">
            <h2 class="h5 fw-bold mb-1">
                Data Pertukaran Jadwal
            </h2>

            <p class="small text-secondary mb-0">
                Pilih dua karyawan beserta tanggal jadwal
                yang akan dipertukarkan.
            </p>
        </div>
    </div>

    <div class="col-lg-6">
        <section
            class="border rounded-3 p-3 p-md-4 h-100"
            aria-labelledby="requester-section-title"
        >
            <div class="mb-3">
                <h3
                    id="requester-section-title"
                    class="h6 fw-bold mb-1"
                >
                    Karyawan Pengaju
                </h3>

                <p class="small text-secondary mb-0">
                    Karyawan yang mengajukan pertukaran jadwal.
                </p>
            </div>

            <div class="mb-3">
                <label
                    for="requester_employee_id"
                    class="form-label"
                >
                    Karyawan Pengaju
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="requester_employee_id"
                    name="requester_employee_id"
                    class="form-select
                        @error('requester_employee_id')
                            is-invalid
                        @enderror"
                    required
                >
                    <option value="">
                        Pilih karyawan pengaju
                    </option>

                    @foreach ($employees as $employeeOption)
                        <option
                            value="{{ $employeeOption->id }}"
                            @selected(
                                (string) $selectedRequesterEmployeeId
                                === (string) $employeeOption->id
                            )
                        >
                            {{ $employeeOption->employee_number }}
                            — {{ $employeeOption->full_name }}

                            @if ($employeeOption->branch !== null)
                                — {{ $employeeOption->branch->code }}
                            @endif
                        </option>
                    @endforeach
                </select>

                @error('requester_employee_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div>
                <label
                    for="requester_date"
                    class="form-label"
                >
                    Tanggal Jadwal Pengaju
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="date"
                    id="requester_date"
                    name="requester_date"
                    value="{{ $selectedRequesterDate }}"
                    class="form-control
                        @error('requester_date')
                            is-invalid
                        @enderror"
                    required
                >

                @error('requester_date')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

                <div class="form-text">
                    Karyawan pengaju harus sudah memiliki jadwal
                    pada tanggal ini.
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section
            class="border rounded-3 p-3 p-md-4 h-100"
            aria-labelledby="partner-section-title"
        >
            <div class="mb-3">
                <h3
                    id="partner-section-title"
                    class="h6 fw-bold mb-1"
                >
                    Karyawan Pasangan
                </h3>

                <p class="small text-secondary mb-0">
                    Karyawan yang jadwalnya akan dipertukarkan
                    dengan pengaju.
                </p>
            </div>

            <div class="mb-3">
                <label
                    for="partner_employee_id"
                    class="form-label"
                >
                    Karyawan Pasangan
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="partner_employee_id"
                    name="partner_employee_id"
                    class="form-select
                        @error('partner_employee_id')
                            is-invalid
                        @enderror"
                    required
                >
                    <option value="">
                        Pilih karyawan pasangan
                    </option>

                    @foreach ($employees as $employeeOption)
                        <option
                            value="{{ $employeeOption->id }}"
                            @selected(
                                (string) $selectedPartnerEmployeeId
                                === (string) $employeeOption->id
                            )
                        >
                            {{ $employeeOption->employee_number }}
                            — {{ $employeeOption->full_name }}

                            @if ($employeeOption->branch !== null)
                                — {{ $employeeOption->branch->code }}
                            @endif
                        </option>
                    @endforeach
                </select>

                @error('partner_employee_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

                <div class="form-text">
                    Karyawan pasangan harus berbeda dari
                    karyawan pengaju.
                </div>
            </div>

            <div>
                <label
                    for="partner_date"
                    class="form-label"
                >
                    Tanggal Jadwal Pasangan
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="date"
                    id="partner_date"
                    name="partner_date"
                    value="{{ $selectedPartnerDate }}"
                    class="form-control
                        @error('partner_date')
                            is-invalid
                        @enderror"
                    required
                >

                @error('partner_date')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

                <div class="form-text">
                    Karyawan pasangan harus sudah memiliki jadwal
                    pada tanggal ini.
                </div>
            </div>
        </section>
    </div>

    <div class="col-12">
        <label
            for="reason"
            class="form-label"
        >
            Alasan Pertukaran
            <span class="text-danger">*</span>
        </label>

        <textarea
            id="reason"
            name="reason"
            rows="5"
            class="form-control
                @error('reason')
                    is-invalid
                @enderror"
            placeholder="Tuliskan alasan pengajuan pertukaran jadwal"
            required
        >{{ $reasonValue }}</textarea>

        @error('reason')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <div class="form-text">
            Alasan harus menjelaskan kebutuhan pertukaran jadwal
            secara jelas.
        </div>
    </div>

    <div class="col-12">
        <div
            id="same-employee-warning"
            class="alert alert-danger d-none"
            role="alert"
        >
            Karyawan pengaju dan karyawan pasangan
            tidak boleh sama.
        </div>

        <div
            class="alert alert-info mb-0"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Ketentuan pengajuan
            </div>

            <ul class="small mb-0 ps-3">
                <li>
                    Kedua karyawan harus masih berstatus aktif.
                </li>

                <li>
                    Kedua karyawan harus memiliki jadwal pada
                    tanggal yang dipilih.
                </li>

                <li>
                    Permohonan baru disimpan dengan status
                    menunggu keputusan.
                </li>

                <li>
                    Persetujuan atau penolakan hanya dapat
                    dilakukan oleh HRD.
                </li>

                <li>
                    Jadwal yang sudah memiliki data presensi
                    tidak dapat dipertukarkan.
                </li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const requesterField = document.getElementById(
                'requester_employee_id'
            );

            const partnerField = document.getElementById(
                'partner_employee_id'
            );

            const warningElement = document.getElementById(
                'same-employee-warning'
            );

            if (
                requesterField === null
                || partnerField === null
            ) {
                return;
            }

            const synchronizeEmployeeOptions = function () {
                const requesterValue = requesterField.value;
                const partnerValue = partnerField.value;

                Array.from(requesterField.options).forEach(
                    function (option) {
                        if (option.value === '') {
                            option.disabled = false;

                            return;
                        }

                        option.disabled =
                            partnerValue !== ''
                            && option.value === partnerValue;
                    }
                );

                Array.from(partnerField.options).forEach(
                    function (option) {
                        if (option.value === '') {
                            option.disabled = false;

                            return;
                        }

                        option.disabled =
                            requesterValue !== ''
                            && option.value === requesterValue;
                    }
                );

                const hasSameEmployee =
                    requesterValue !== ''
                    && partnerValue !== ''
                    && requesterValue === partnerValue;

                if (warningElement !== null) {
                    warningElement.classList.toggle(
                        'd-none',
                        ! hasSameEmployee
                    );
                }

                partnerField.setCustomValidity(
                    hasSameEmployee
                        ? 'Karyawan pasangan harus berbeda dari karyawan pengaju.'
                        : ''
                );
            };

            requesterField.addEventListener(
                'change',
                synchronizeEmployeeOptions
            );

            partnerField.addEventListener(
                'change',
                synchronizeEmployeeOptions
            );

            synchronizeEmployeeOptions();
        });
    </script>
@endpush