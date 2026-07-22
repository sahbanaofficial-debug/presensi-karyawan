@extends('layouts.app')

@section('title', 'Edit Pola Jadwal')

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Edit Pola Jadwal
            </h1>

            <p class="page-description">
                Perbarui jam kerja dan aturan waktu presensi
                {{ $workSchedule->name }}.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('work-schedules.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Pola Jadwal
            </a>

            <a
                href="{{
                    route(
                        'work-schedules.show',
                        $workSchedule
                    )
                }}"
                class="btn btn-outline-primary"
            >
                Detail Pola Jadwal
            </a>
        </div>
    </header>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="edit-work-schedule-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="edit-work-schedule-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Perubahan Pola Jadwal
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        @if (
            ($workSchedule->employee_schedules_count ?? 0) > 0
        )
            <div
                class="alert alert-warning"
                role="alert"
            >
                <div class="fw-semibold mb-1">
                    Pola jadwal telah digunakan.
                </div>

                <div>
                    Perubahan pada pola ini dapat memengaruhi
                    penggunaan jadwal kerja berikutnya. Pastikan
                    konfigurasi waktu sudah sesuai.
                </div>
            </div>
        @endif

        <form
            method="POST"
            action="{{
                route(
                    'work-schedules.update',
                    $workSchedule
                )
            }}"
        >
            @csrf
            @method('PUT')

            @include('work-schedules._form', [
                'workSchedule' => $workSchedule,
            ])

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{
                        route(
                            'work-schedules.show',
                            $workSchedule
                        )
                    }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>
@endsection