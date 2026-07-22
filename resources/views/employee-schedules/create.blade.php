@extends('layouts.app')

@section('title', 'Tambah Jadwal Harian')

@section('content')
    @php
        $canCreateSchedule =
            $employees->isNotEmpty()
            && $workSchedules->isNotEmpty();
    @endphp

    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Tambah Jadwal Harian
            </h1>

            <p class="page-description">
                Tetapkan jadwal kerja, hari libur, izin,
                atau sakit untuk karyawan.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{
                    route('employee-schedules.index')
                }}"
                class="btn btn-outline-secondary"
            >
                Daftar Jadwal Harian
            </a>
        </div>
    </header>

    @if ($employees->isEmpty())
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Karyawan aktif tidak tersedia.
            </div>

            <p class="mb-3">
                Jadwal harian belum dapat ditambahkan karena
                tidak terdapat karyawan aktif.
            </p>

            <a
                href="{{ route('employees.index') }}"
                class="btn btn-sm btn-outline-dark"
            >
                Lihat Data Karyawan
            </a>
        </div>
    @endif

    @if ($workSchedules->isEmpty())
        <div
            class="alert alert-warning"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Pola jadwal aktif tidak tersedia.
            </div>

            <p class="mb-3">
                Status kerja belum dapat ditetapkan karena tidak
                terdapat pola jadwal kerja yang aktif.
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
        aria-labelledby="create-employee-schedule-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="create-employee-schedule-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Jadwal Harian Baru
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
                route('employee-schedules.store')
            }}"
        >
            @csrf

            @include('employee-schedules._form', [
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
                        route('employee-schedules.index')
                    }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled(! $canCreateSchedule)
                >
                    Simpan Jadwal Harian
                </button>
            </div>
        </form>
    </section>
@endsection