@extends('layouts.app')

@section('title', 'Susun Roster Mingguan')

@push('styles')
    <style>
        .weekly-roster-item {
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius-md);
            background: var(--neutral-0);
        }

        .weekly-roster-item + .weekly-roster-item {
            margin-top: var(--space-3);
        }
    </style>
@endpush

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Susun Roster Mingguan
            </h1>

            <p class="page-description">
                Buat draft roster per cabang untuk periode
                Senin sampai Minggu.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('weekly-rosters.index') }}"
                class="btn btn-outline-secondary"
            >
                <i
                    class="bi bi-arrow-left me-2"
                    aria-hidden="true"
                ></i>

                Kembali ke Daftar
            </a>
        </div>
    </header>

    @php
        $activeEmployeeCount = $branches->sum(
            static fn ($branch): int =>
                $branch->employees->count()
        );

        $canCreateRoster =
            $branches->isNotEmpty()
            && $activeEmployeeCount > 0
            && $workSchedules->isNotEmpty();
    @endphp

    @if ($canCreateRoster)
        <div
            class="alert alert-info"
            id="weekly-roster-reference-summary"
            role="status"
        >
            <div class="fw-semibold mb-1">
                Referensi penyusunan roster siap
            </div>

            <div class="small">
                Tersedia {{ $branches->count() }} cabang aktif,
                {{ $activeEmployeeCount }} karyawan aktif, dan
                {{ $workSchedules->count() }} pola kerja aktif.
                Setiap tanggal item wajib berada dalam minggu
                Senin sampai Minggu yang dipilih.
            </div>
        </div>
    @else
        <div
            class="alert alert-danger"
            id="weekly-roster-reference-summary"
            role="alert"
        >
            <div class="fw-semibold mb-1">
                Referensi roster belum lengkap
            </div>

            <div class="small">
                Penyusunan roster membutuhkan minimal satu
                cabang aktif, satu karyawan aktif pada cabang
                tersebut, dan satu pola kerja aktif.
            </div>
        </div>
    @endif
    <div
        id="weekly-roster-alert"
        class="alert d-none"
        role="alert"
        aria-live="polite"
    ></div>

    <section class="content-card p-3 p-md-4">
        <form
            id="weekly-roster-form"
            action="{{ route('weekly-rosters.store') }}"
            method="POST"
            novalidate
        >
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-6">
                    <label
                        for="branch_id"
                        class="form-label"
                    >
                        Cabang
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        id="branch_id"
                        name="branch_id"
                        class="form-select"
                        required
                    >
                        <option value="">
                            Pilih cabang
                        </option>

                        @foreach ($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                            >
                                {{ $branch->code }}
                                ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="form-text">
                        Hanya cabang aktif yang tersedia.
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label
                        for="week_start_date"
                        class="form-label"
                    >
                        Awal minggu
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="date"
                        id="week_start_date"
                        name="week_start_date"
                        value="{{ $weekStartDate }}"
                        class="form-control"
                        required
                    >

                    <div class="form-text">
                        Tanggal wajib berada pada hari Senin.
                    </div>
                </div>
            </div>

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-between
                    align-items-sm-center gap-2 mb-3"
            >
                <div>
                    <h2 class="h5 fw-bold mb-1">
                        Item Roster
                    </h2>

                    <p class="small text-secondary mb-0">
                        Tambahkan satu item untuk setiap
                        karyawan dan tanggal jadwal.
                    </p>
                </div>

                <button
                    type="button"
                    id="add-weekly-roster-item"
                    class="btn btn-outline-primary"
                    @disabled(! $canCreateRoster)
                >
                    <i
                        class="bi bi-plus-lg me-2"
                        aria-hidden="true"
                    ></i>

                    Tambah Item
                </button>
            </div>

            <div id="weekly-roster-items"></div>

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2 mt-4"
            >
                <a
                    href="{{ route('weekly-rosters.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    id="save-weekly-roster"
                    class="btn btn-primary"
                    @disabled(! $canCreateRoster)
                >
                    Simpan sebagai Draft
                </button>
            </div>
        </form>
    </section>

    <template id="weekly-roster-item-template">
        <article
            class="weekly-roster-item p-3"
            data-roster-item
        >
            <div
                class="d-flex justify-content-between
                    align-items-center gap-2 mb-3"
            >
                <h3 class="h6 fw-bold mb-0">
                    Item
                    <span data-item-number></span>
                </h3>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    data-remove-item
                >
                    Hapus
                </button>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    <label class="form-label">
                        Karyawan
                    </label>

                    <select
                        name="items[__INDEX__][employee_id]"
                        class="form-select"
                        data-employee-select
                        required
                    >
                        <option value="">
                            Pilih karyawan
                        </option>

                        @foreach ($branches as $branch)
                            @foreach ($branch->employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    data-branch-id="{{ $branch->id }}"
                                >
                                    {{
                                        $employee
                                            ->employee_number
                                    }}
                                    ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â {{ $employee->full_name }}
                                    ({{ $employee->position }})
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        name="items[__INDEX__][schedule_date]"
                        class="form-control"
                        data-schedule-date
                        required
                    >
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="items[__INDEX__][schedule_status]"
                        class="form-select"
                        data-status-select
                        required
                    >
                        <option value="work">
                            Kerja
                        </option>

                        <option value="off">
                            Libur
                        </option>

                        <option value="permit">
                            Izin
                        </option>

                        <option value="sick">
                            Sakit
                        </option>
                    </select>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">
                        Pola jadwal kerja
                    </label>

                    <select
                        name="items[__INDEX__][work_schedule_id]"
                        class="form-select"
                        data-work-schedule-select
                    >
                        <option value="">
                            Pilih pola jadwal
                        </option>

                        @foreach (
                            $workSchedules
                            as $workSchedule
                        )
                            <option
                                value="{{ $workSchedule->id }}"
                            >
                                {{ $workSchedule->name }}
                                ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â
                                {{
                                    substr(
                                        (string) $workSchedule
                                            ->check_in_time,
                                        0,
                                        5
                                    )
                                }}
                                sampai
                                {{
                                    substr(
                                        (string) $workSchedule
                                            ->check_out_time,
                                        0,
                                        5
                                    )
                                }}
                            </option>
                        @endforeach
                    </select>

                    <div class="form-text">
                        Wajib untuk status Kerja dan harus
                        kosong untuk status nonkerja.
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">
                        Catatan
                    </label>

                    <input
                        type="text"
                        name="items[__INDEX__][notes]"
                        class="form-control"
                        maxlength="1000"
                        placeholder="Opsional"
                    >
                </div>
            </div>
        </article>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById(
                'weekly-roster-form'
            );

            const branchSelect = document.getElementById(
                'branch_id'
            );

            const weekStartInput = document.getElementById(
                'week_start_date'
            );

            const itemsContainer = document.getElementById(
                'weekly-roster-items'
            );

            const template = document.getElementById(
                'weekly-roster-item-template'
            );

            const addButton = document.getElementById(
                'add-weekly-roster-item'
            );

            const submitButton = document.getElementById(
                'save-weekly-roster'
            );

            const alertBox = document.getElementById(
                'weekly-roster-alert'
            );

            let itemIndex = 0;

            const showAlert = function (message, type) {
                alertBox.className =
                    'alert alert-' + type;

                alertBox.textContent = message;
            };

            const clearAlert = function () {
                alertBox.className = 'alert d-none';
                alertBox.textContent = '';
            };

            const filterEmployees = function (item) {
                const employeeSelect =
                    item.querySelector(
                        '[data-employee-select]'
                    );

                const selectedBranch =
                    branchSelect.value;

                Array.from(
                    employeeSelect.options
                ).forEach(function (option) {
                    if (option.value === '') {
                        option.hidden = false;

                        return;
                    }

                    option.hidden =
                        selectedBranch === ''
                        || option.dataset.branchId
                            !== selectedBranch;
                });

                const selectedOption =
                    employeeSelect
                        .selectedOptions[0];

                if (
                    selectedOption
                    && selectedOption.value !== ''
                    && selectedOption.hidden
                ) {
                    employeeSelect.value = '';
                }
            };

            const updateWorkScheduleState =
                function (item) {
                    const statusSelect =
                        item.querySelector(
                            '[data-status-select]'
                        );

                    const workScheduleSelect =
                        item.querySelector(
                            '[data-work-schedule-select]'
                        );

                    const isWork =
                        statusSelect.value === 'work';

                    workScheduleSelect.required = isWork;
                    workScheduleSelect.disabled = ! isWork;

                    if (! isWork) {
                        workScheduleSelect.value = '';
                    }
                };

            const renumberItems = function () {
                Array.from(
                    itemsContainer.querySelectorAll(
                        '[data-roster-item]'
                    )
                ).forEach(function (item, index) {
                    const number =
                        item.querySelector(
                            '[data-item-number]'
                        );

                    number.textContent =
                        String(index + 1);
                });
            };

            const addItem = function () {
                const html =
                    template.innerHTML.replaceAll(
                        '__INDEX__',
                        String(itemIndex)
                    );

                itemIndex += 1;

                itemsContainer.insertAdjacentHTML(
                    'beforeend',
                    html
                );

                const item =
                    itemsContainer.lastElementChild;

                const scheduleDate =
                    item.querySelector(
                        '[data-schedule-date]'
                    );

                scheduleDate.value =
                    weekStartInput.value;

                filterEmployees(item);
                updateWorkScheduleState(item);

                item.querySelector(
                    '[data-status-select]'
                ).addEventListener(
                    'change',
                    function () {
                        updateWorkScheduleState(item);
                    }
                );

                item.querySelector(
                    '[data-remove-item]'
                ).addEventListener(
                    'click',
                    function () {
                        item.remove();

                        if (
                            itemsContainer.children
                                .length === 0
                        ) {
                            addItem();
                        }

                        renumberItems();
                    }
                );

                renumberItems();
            };

            branchSelect.addEventListener(
                'change',
                function () {
                    Array.from(
                        itemsContainer.querySelectorAll(
                            '[data-roster-item]'
                        )
                    ).forEach(filterEmployees);
                }
            );

            weekStartInput.addEventListener(
                'change',
                function () {
                    const firstDate =
                        itemsContainer.querySelector(
                            '[data-schedule-date]'
                        );

                    if (
                        firstDate
                        && firstDate.value === ''
                    ) {
                        firstDate.value =
                            weekStartInput.value;
                    }
                }
            );

            addButton.addEventListener(
                'click',
                addItem
            );

            form.addEventListener(
                'submit',
                async function (event) {
                    event.preventDefault();
                    clearAlert();

                    submitButton.disabled = true;
                    submitButton.textContent =
                        'Menyimpan...';

                    try {
                        const response = await fetch(
                            form.action,
                            {
                                method: 'POST',
                                headers: {
                                    Accept:
                                        'application/json',
                                },
                                body: new FormData(form),
                            }
                        );

                        const payload =
                            await response.json();

                        if (! response.ok) {
                            const errors =
                                payload.errors ?? {};

                            const messages =
                                Object.values(errors)
                                    .flat();

                            throw new Error(
                                messages[0]
                                ?? payload.message
                                ?? 'Roster gagal disimpan.'
                            );
                        }

                        window.location.href =
                            '{{ url('/weekly-rosters') }}'
                            + '/'
                            + payload.data.id;
                    } catch (error) {
                        showAlert(
                            error.message
                            ?? 'Roster gagal disimpan.',
                            'danger'
                        );

                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth',
                        });
                    } finally {
                        submitButton.disabled = false;
                        submitButton.textContent =
                            'Simpan sebagai Draft';
                    }
                }
            );

            addItem();
        });
    </script>
@endpush
