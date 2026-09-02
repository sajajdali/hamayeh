<?php

namespace App\Exports;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RegistrationsExport implements FromQuery, WithHeadings, WithMapping
{
    /** @return Builder<Registration> */
    public function query(): Builder
    {
        return Registration::query()->with('blogger')->latest();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['کد بلیط', 'نام', 'بلاگر', 'پایه', 'رشته', 'مدرسه', 'شهر', 'معدل', 'همراه', 'شماره همراه', 'وضعیت', 'تاریخ'];
    }

    /** @return array<int, string|null> */
    public function map(mixed $row): array
    {
        /** @var Registration $row */
        return [
            $row->ticket_code,
            $row->full_name,
            $row->blogger?->name,
            $row->grade->label(),
            $row->field->label(),
            $row->school,
            $row->city,
            $row->gpa,
            $row->guardian_name,
            $row->guardian_phone,
            $row->status->label(),
            $row->created_at->toIso8601String(),
        ];
    }
}
