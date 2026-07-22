@extends('layouts.app')

@section('title', 'Edit Jadwal Harian')

@section('content')
    @php
        $employee = $employeeSchedule->employee;
        $workSchedule = $employeeSchedule->workSchedule;

        $currentStatus = old(
            'schedule_status',
            $employeeSchedule->schedule_status
        );

        $statusLabels = [
            'work' => 'Kerja',
            'off' => 'Libur',
            'permit' => 'Izin',
            'sick' => 'Sakit',
        ];

        $currentStatusLabel = $statusLabels[$currentStatus]
            ?? ucfirst((string) $currentStatus);

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->locale('id')
                    ->translatedFormat('l, d F Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $hasAvailableEmployee = $employees->isNotEmpty();

        $hasAvailableWorkSchedule =
            $workSchedules->isNotEmpty();
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Edit Jadwal Harian
            </h1>

            <p class="page-description">
                Perbarui karyawan, tanggal, status,
                atau pola jadwal kerja.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('employee-schedules.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Jadwal
            </a>

            <a
                href="{{
                    route(
                        'employee-schedules.show',
                        $employeeSchedule
                    )
                }}"
                class="btn btn-outline-primary"
            >
                Detail Jadwal
            </a>
        </div>
    </header>

    <section class="content-card p-3 p-md-4 mb-4">
        <div
            class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-start gap-3"
        >
            <div>
                <div class="small text-secondary mb-1">
                    Jadwal yang sedang diubah
                </div>

                <h2 class="h5 fw-bold mb-1">
                    @if ($employee !== null)
                        {{ $employee->employee_number }}
                        — {{ $employee->full_name }}
                    @else
                        Data karyawan tidak tersedia
                    @endif
                </h2>

                <div class="text-secondary">
                    {{
                        $formatDate(
                            $employeeSchedule->schedule_date
                        )
                    }}
                </div>
            </div>

            <div>
                <span class="badge text-bg-light border px-3 py-2">
                    Status: {{ $currentStatusLabel }}
                </span>
            </div>
        </div>
    </section>

    @if (
        $employee !== null
        && $employee->employment_status === 'inactive'
    )
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Karyawan sudah tidak aktif.
            </div>

            <p class="mb-0">
                Karyawan lama tetap ditampilkan agar jadwal ini
                dapat dipertahankan. Apabila karyawan diganti,
                pilih karyawan yang masih aktif.
            </p>
        </div>
    @endif

    @if (
        $workSchedule !== null
        && $workSchedule->status === 'inactive'
    )
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Pola jadwal sudah tidak aktif.
            </div>

            <p class="mb-0">
                Pola jadwal lama tetap dapat dipertahankan.
                Apabila pola jadwal diganti, pilih pola yang
                masih aktif.
            </p>
        </div>
    @endif

    @if (! $hasAvailableEmployee)
        <div
            class="alert alert-danger"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Data karyawan tidak tersedia.
            </div>

            <p class="mb-0">
                Perubahan belum dapat dilakukan karena tidak
                terdapat data karyawan yang dapat dipilih.
            </p>
        </div>
    @endif

    @if (! $hasAvailableWorkSchedule)
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Pola jadwal kerja tidak tersedia.
            </div>

            <p class="mb-3">
                Status kerja tidak dapat dipilih sebelum terdapat
                pola jadwal kerja yang aktif.
            </p>

            <a
                href="{{ route('work-schedules.index') }}"
                class="btn btn-sm btn-outline-dark"
            >
                Kelola Pola Jadwal
            </a>
        </div>
    @endif

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="edit-employee-schedule-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="edit-employee-schedule-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Perubahan Jadwal
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        <form
            method="POST"
            action="{{
                route(
                    'employee-schedules.update',
                    $employeeSchedule
                )
            }}"
        >
            @csrf
            @method('PUT')

            @include('employee-schedules._form', [
                'employeeSchedule' => $employeeSchedule,
                'employees' => $employees,
                'workSchedules' => $workSchedules,
            ])

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{
                        route(
                            'employee-schedules.show',
                            $employeeSchedule
                        )
                    }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled(! $hasAvailableEmployee)
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>
@endsection