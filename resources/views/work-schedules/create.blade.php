@extends('layouts.app')

@section('title', 'Tambah Pola Jadwal')

@section('content')
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
                Daftar Pola Jadwal
            </a>
        </div>
    </header>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="create-work-schedule-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="create-work-schedule-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Pola Jadwal Baru
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('work-schedules.store') }}"
        >
            @csrf

            @include('work-schedules._form')

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{ route('work-schedules.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Pola Jadwal
                </button>
            </div>
        </form>
    </section>
@endsection