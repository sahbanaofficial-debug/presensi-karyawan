<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CloseAttendanceSessionRequest extends FormRequest
{
    /**
     * HRD dan admin operasional dapat menutup sesi.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return in_array(
            $user->role,
            [
                'hrd',
                'admin',
            ],
            true
        );
    }

    /**
     * Penutupan sesi tidak menerima data dari formulir.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Memastikan hanya sesi aktif yang dapat ditutup.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                $attendanceSession =
                    $this->routeAttendanceSession();

                if ($attendanceSession === null) {
                    return;
                }

                if (! $attendanceSession->isActive()) {
                    $validator->errors()->add(
                        'attendance_session',
                        'Sesi presensi sudah tidak aktif dan tidak dapat ditutup kembali.'
                    );
                }
            }
        );
    }

    /**
     * Mengambil sesi dari parameter route
     * {attendance_session}.
     */
    private function routeAttendanceSession(): ?AttendanceSession
    {
        $attendanceSession = $this->route(
            'attendance_session'
        );

        if (
            $attendanceSession
            instanceof AttendanceSession
        ) {
            return $attendanceSession;
        }

        if (
            is_numeric($attendanceSession)
            && (int) $attendanceSession > 0
        ) {
            return AttendanceSession::query()->find(
                (int) $attendanceSession
            );
        }

        return null;
    }
}
