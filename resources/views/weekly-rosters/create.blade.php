@extends('layouts.app')

@section('title', 'Susun Roster Mingguan')

@push('styles')
    <style>
        .weekly-roster-matrix {
            min-width: 74rem;
        }

        .weekly-roster-matrix th,
        .weekly-roster-matrix td {
            min-width: 9rem;
            padding: var(--space-2);
            vertical-align: middle;
        }

        .weekly-roster-matrix .roster-employee-column {
            position: sticky;
            z-index: 2;
            left: 0;
            min-width: 15rem;
            background: var(--neutral-0);
            box-shadow: 0.25rem 0 0.5rem rgba(37, 42, 47, 0.04);
        }

        .weekly-roster-matrix thead .roster-employee-column {
            z-index: 3;
            background: var(--neutral-50);
        }

        .roster-day-label {
            display: block;
            color: var(--neutral-800);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: none;
        }

        .roster-day-date {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-500);
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0;
            text-transform: none;
        }

        .roster-employee-number {
            color: var(--brand-700);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        .roster-employee-name {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-900);
            font-size: 0.8125rem;
            font-weight: 800;
        }

        .roster-employee-position {
            display: block;
            margin-top: 0.125rem;
            color: var(--neutral-600);
            font-size: 0.6875rem;
            font-weight: 500;
        }

        .roster-choice {
            min-width: 8rem;
            min-height: 2.5rem;
            padding-right: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .roster-choice-white {
            border-color: var(--neutral-300);
            color: var(--neutral-700);
            background-color: var(--neutral-0);
        }

        .roster-choice-green {
            border-color: #9fd3b8;
            color: var(--success-700);
            background-color: var(--success-50);
        }

        .roster-choice-yellow {
            border-color: #ead08d;
            color: var(--warning-700);
            background-color: var(--warning-50);
        }

        .roster-choice-orange {
            border-color: var(--brand-300);
            color: var(--brand-700);
            background-color: var(--brand-50);
        }

        .roster-choice-red {
            border-color: #e5aaa0;
            color: var(--danger-700);
            background-color: var(--danger-50);
        }

        .roster-legend {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .roster-legend-item {
            display: inline-flex;
            min-height: 2rem;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border: 1px solid;
            border-radius: var(--radius-pill);
            font-size: 0.6875rem;
            font-weight: 800;
        }

        @media (max-width: 575.98px) {
            .weekly-roster-matrix .roster-employee-column {
                min-width: 12rem;
            }
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
                Pilih cabang dan awal minggu, kemudian tentukan
                jadwal setiap karyawan melalui matriks Senin
                sampai Minggu.
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

        $selectedBranchId = old('branch_id');

        if (
            $selectedBranchId === null
            && $branches->count() === 1
        ) {
            $selectedBranchId = (string) $branches->first()->id;
        }

        $branchEmployees = $branches->mapWithKeys(
            static fn ($branch): array => [
                (string) $branch->id =>
                    $branch->employees
                        ->map(
                            static fn ($employee): array => [
                                'id' => $employee->id,
                                'employee_number' =>
                                    $employee->employee_number,
                                'full_name' =>
                                    $employee->full_name,
                                'position' =>
                                    $employee->position,
                            ]
                        )
                        ->values(),
            ]
        );

        $scheduleChoices = $workSchedules
            ->map(
                static function ($workSchedule): array {
                    $normalizedName = mb_strtolower(
                        trim((string) $workSchedule->name)
                    );

                    $color = match ($normalizedName) {
                        'pulang sore' => 'green',
                        'masuk siang' => 'yellow',
                        'jadwal penuh' => 'orange',
                        default => 'white',
                    };

                    return [
                        'value' =>
                            'work:' . $workSchedule->id,
                        'label' =>
                            (string) $workSchedule->name,
                        'color' => $color,
                    ];
                }
            )
            ->values();
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
                Sel kosong tidak akan disimpan sebagai item roster.
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
                                @selected(
                                    (string) $selectedBranchId
                                    === (string) $branch->id
                                )
                            >
                                {{ $branch->code }}
                                - {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="form-text">
                        Karyawan aktif dimuat otomatis berdasarkan
                        cabang yang dipilih.
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
                        value="{{ old(
                            'week_start_date',
                            $weekStartDate
                        ) }}"
                        class="form-control"
                        required
                    >

                    <div class="form-text">
                        Tanggal wajib berada pada hari Senin.
                        Tanggal Selasa sampai Minggu dibuat otomatis.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <h2 class="h5 fw-bold mb-1">
                    Matriks Roster
                </h2>

                <p class="small text-secondary mb-3">
                    Pilih satu jadwal atau status pada setiap sel.
                    Biarkan sel tetap kosong bila belum ditetapkan.
                </p>

                <div
                    class="roster-legend"
                    aria-label="Legenda warna roster"
                >
                    @foreach (
                        $scheduleChoices
                        as $scheduleChoice
                    )
                        <span
                            class="roster-legend-item
                                roster-choice-{{
                                    $scheduleChoice['color']
                                }}"
                        >
                            {{ $scheduleChoice['label'] }}
                        </span>
                    @endforeach

                    <span
                        class="roster-legend-item
                            roster-choice-red"
                    >
                        Libur
                    </span>

                    <span
                        class="roster-legend-item
                            roster-choice-red"
                    >
                        Cuti
                    </span>

                    <span
                        class="roster-legend-item
                            roster-choice-yellow"
                    >
                        Izin
                    </span>

                    <span
                        class="roster-legend-item
                            roster-choice-red"
                    >
                        Sakit
                    </span>
                </div>
            </div>

            <div
                id="weekly-roster-matrix-empty"
                class="empty-state py-5"
                role="status"
            >
                <span class="empty-state-icon">
                    <i
                        class="bi bi-calendar3-week"
                        aria-hidden="true"
                    ></i>
                </span>

                <div class="fw-bold">
                    Pilih cabang untuk memuat karyawan
                </div>

                <div class="small mt-1">
                    Matriks akan menampilkan tujuh hari untuk
                    seluruh karyawan aktif pada cabang tersebut.
                </div>
            </div>

            <div
                id="weekly-roster-matrix-wrapper"
                class="table-responsive d-none"
            >
                <table
                    id="weekly-roster-matrix"
                    class="table table-bordered
                        weekly-roster-matrix"
                >
                    <thead>
                        <tr>
                            <th
                                scope="col"
                                class="roster-employee-column"
                            >
                                Karyawan
                            </th>

                            @foreach (
                                [
                                    'Senin',
                                    'Selasa',
                                    'Rabu',
                                    'Kamis',
                                    'Jumat',
                                    'Sabtu',
                                    'Minggu',
                                ] as $dayIndex => $dayName
                            )
                                <th
                                    scope="col"
                                    data-week-day="{{ $dayIndex }}"
                                >
                                    <span class="roster-day-label">
                                        {{ $dayName }}
                                    </span>

                                    <span
                                        class="roster-day-date"
                                        data-week-date
                                    >
                                        -
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody data-matrix-body></tbody>
                </table>
            </div>

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

            const matrixWrapper = document.getElementById(
                'weekly-roster-matrix-wrapper'
            );

            const matrixEmpty = document.getElementById(
                'weekly-roster-matrix-empty'
            );

            const matrixBody = document.querySelector(
                '[data-matrix-body]'
            );

            const submitButton = document.getElementById(
                'save-weekly-roster'
            );

            const alertBox = document.getElementById(
                'weekly-roster-alert'
            );

            const branchEmployees =
                {{ Illuminate\Support\Js::from(
                    $branchEmployees
                ) }};

            const scheduleChoices =
                {{ Illuminate\Support\Js::from(
                    $scheduleChoices
                ) }};

            const nonWorkChoices = [
                {
                    value: 'off',
                    label: 'Libur',
                    color: 'red',
                },
                {
                    value: 'leave',
                    label: 'Cuti',
                    color: 'red',
                },
                {
                    value: 'permit',
                    label: 'Izin',
                    color: 'yellow',
                },
                {
                    value: 'sick',
                    label: 'Sakit',
                    color: 'red',
                },
            ];

            const choiceClasses = [
                'roster-choice-white',
                'roster-choice-green',
                'roster-choice-yellow',
                'roster-choice-orange',
                'roster-choice-red',
            ];

            const showAlert = function (message, type) {
                alertBox.className =
                    'alert alert-' + type;

                alertBox.textContent = message;
            };

            const clearAlert = function () {
                alertBox.className = 'alert d-none';
                alertBox.textContent = '';
            };

            const parseDate = function (value) {
                if (value === '') {
                    return null;
                }

                const date = new Date(value + 'T00:00:00');

                return Number.isNaN(date.getTime())
                    ? null
                    : date;
            };

            const addDays = function (date, days) {
                const result = new Date(date.getTime());

                result.setDate(result.getDate() + days);

                return result;
            };

            const formatInputDate = function (date) {
                const year = String(date.getFullYear());
                const month = String(
                    date.getMonth() + 1
                ).padStart(2, '0');
                const day = String(date.getDate())
                    .padStart(2, '0');

                return year + '-' + month + '-' + day;
            };

            const formatDisplayDate = function (date) {
                return new Intl.DateTimeFormat(
                    'id-ID',
                    {
                        day: '2-digit',
                        month: 'short',
                    }
                ).format(date);
            };

            const updateDateHeaders = function () {
                const weekStart = parseDate(
                    weekStartInput.value
                );

                document.querySelectorAll(
                    '[data-week-day]'
                ).forEach(function (header) {
                    const dateLabel = header.querySelector(
                        '[data-week-date]'
                    );

                    if (! weekStart) {
                        dateLabel.textContent = '-';

                        return;
                    }

                    const date = addDays(
                        weekStart,
                        Number(header.dataset.weekDay)
                    );

                    dateLabel.textContent =
                        formatDisplayDate(date);
                });
            };

            const applyChoiceColor = function (select) {
                select.classList.remove(...choiceClasses);

                const option = select.selectedOptions[0];
                const color = option?.dataset.color ?? 'white';

                select.classList.add(
                    'roster-choice-' + color
                );
            };

            const appendOption = function (
                select,
                value,
                label,
                color
            ) {
                const option = document.createElement(
                    'option'
                );

                option.value = value;
                option.textContent = label;
                option.dataset.color = color;

                select.appendChild(option);
            };

            const createChoiceSelect = function (
                employeeId,
                dayIndex
            ) {
                const select = document.createElement(
                    'select'
                );

                select.className =
                    'form-select roster-choice '
                    + 'roster-choice-white';
                select.dataset.rosterChoice = '';
                select.dataset.employeeId =
                    String(employeeId);
                select.dataset.weekDay = String(dayIndex);
                select.setAttribute(
                    'aria-label',
                    'Pilih jadwal atau status'
                );

                appendOption(
                    select,
                    '',
                    'Belum dipilih',
                    'white'
                );

                scheduleChoices.forEach(function (choice) {
                    appendOption(
                        select,
                        choice.value,
                        choice.label,
                        choice.color
                    );
                });

                nonWorkChoices.forEach(function (choice) {
                    appendOption(
                        select,
                        choice.value,
                        choice.label,
                        choice.color
                    );
                });

                select.addEventListener(
                    'change',
                    function () {
                        applyChoiceColor(select);
                        clearAlert();
                    }
                );

                return select;
            };

            const createEmployeeCell = function (employee) {
                const cell = document.createElement('th');

                cell.scope = 'row';
                cell.className = 'roster-employee-column';

                const number = document.createElement('span');
                number.className = 'roster-employee-number';
                number.textContent = employee.employee_number;

                const name = document.createElement('span');
                name.className = 'roster-employee-name';
                name.textContent = employee.full_name;

                const position = document.createElement('span');
                position.className =
                    'roster-employee-position';
                position.textContent =
                    employee.position ?? '-';

                cell.append(number, name, position);

                return cell;
            };

            const renderMatrix = function () {
                matrixBody.replaceChildren();
                updateDateHeaders();

                const employees =
                    branchEmployees[branchSelect.value]
                    ?? [];

                if (branchSelect.value === '') {
                    matrixWrapper.classList.add('d-none');
                    matrixEmpty.classList.remove('d-none');
                    matrixEmpty.querySelector(
                        '.fw-bold'
                    ).textContent =
                        'Pilih cabang untuk memuat karyawan';

                    return;
                }

                if (employees.length === 0) {
                    matrixWrapper.classList.add('d-none');
                    matrixEmpty.classList.remove('d-none');
                    matrixEmpty.querySelector(
                        '.fw-bold'
                    ).textContent =
                        'Tidak ada karyawan aktif pada cabang ini';

                    return;
                }

                employees.forEach(function (employee) {
                    const row = document.createElement('tr');

                    row.appendChild(
                        createEmployeeCell(employee)
                    );

                    for (
                        let dayIndex = 0;
                        dayIndex < 7;
                        dayIndex += 1
                    ) {
                        const cell = document.createElement('td');

                        cell.appendChild(
                            createChoiceSelect(
                                employee.id,
                                dayIndex
                            )
                        );

                        row.appendChild(cell);
                    }

                    matrixBody.appendChild(row);
                });

                matrixEmpty.classList.add('d-none');
                matrixWrapper.classList.remove('d-none');
            };

            branchSelect.addEventListener(
                'change',
                function () {
                    clearAlert();
                    renderMatrix();
                }
            );

            weekStartInput.addEventListener(
                'change',
                function () {
                    clearAlert();
                    updateDateHeaders();
                }
            );

            form.addEventListener(
                'submit',
                async function (event) {
                    event.preventDefault();
                    clearAlert();

                    if (! form.reportValidity()) {
                        return;
                    }

                    const weekStart = parseDate(
                        weekStartInput.value
                    );

                    if (! weekStart || weekStart.getDay() !== 1) {
                        showAlert(
                            'Awal minggu wajib berada pada hari Senin.',
                            'danger'
                        );

                        weekStartInput.focus();

                        return;
                    }

                    const selectedCells = Array.from(
                        matrixBody.querySelectorAll(
                            '[data-roster-choice]'
                        )
                    ).filter(function (select) {
                        return select.value !== '';
                    });

                    if (selectedCells.length === 0) {
                        showAlert(
                            'Pilih minimal satu jadwal atau status roster.',
                            'danger'
                        );

                        return;
                    }

                    const formData = new FormData(form);

                    selectedCells.forEach(
                        function (select, index) {
                            const prefix =
                                'items[' + index + ']';
                            const selectedValue =
                                select.value;
                            const isWork =
                                selectedValue.startsWith(
                                    'work:'
                                );
                            const scheduleDate = addDays(
                                weekStart,
                                Number(select.dataset.weekDay)
                            );

                            formData.append(
                                prefix + '[employee_id]',
                                select.dataset.employeeId
                            );
                            formData.append(
                                prefix + '[schedule_date]',
                                formatInputDate(scheduleDate)
                            );
                            formData.append(
                                prefix + '[schedule_status]',
                                isWork
                                    ? 'work'
                                    : selectedValue
                            );

                            if (isWork) {
                                formData.append(
                                    prefix
                                        + '[work_schedule_id]',
                                    selectedValue.split(':')[1]
                                );
                            }
                        }
                    );

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
                                body: formData,
                            }
                        );

                        const payload = await response.json();

                        if (! response.ok) {
                            const errors = payload.errors ?? {};
                            const messages = Object.values(errors)
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

            renderMatrix();
        });
    </script>
@endpush
