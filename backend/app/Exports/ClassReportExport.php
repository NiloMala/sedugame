<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * $rows já vem pronto de TeacherReportController::classReportRows() — mesmos
 * dados usados no CSV, só reempacotados pro formato que o maatwebsite/excel espera.
 */
class ClassReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly string $className, private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row) => [
            $row['name'],
            $row['registration_number'],
            $row['experience'],
            $row['level'] ?? '—',
            $row['completed_missions'],
            $row['average_score'],
        ]);
    }

    public function headings(): array
    {
        return ['Aluno', 'RA', 'XP', 'Nível', 'Missões concluídas', 'Pontuação média'];
    }

    public function title(): string
    {
        // Nome de aba do Excel tem limite de 31 caracteres.
        return mb_substr($this->className, 0, 31);
    }
}
