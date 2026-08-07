<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório de turma — {{ $class->name }}</title>
<style>
  body { font-family: sans-serif; font-size: 12px; color: #17473d; }
  h1 { font-size: 18px; margin-bottom: 0; }
  p.meta { color: #666; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
  th { background: #f0ede1; }
  tr:nth-child(even) { background: #fafafa; }
</style>
</head>
<body>
  <h1>Relatório de turma — {{ $class->name }}</h1>
  <p class="meta">Gerado em {{ now()->format('d/m/Y H:i') }}</p>
  <table>
    <thead>
      <tr>
        <th>Aluno</th>
        <th>RA</th>
        <th>XP</th>
        <th>Nível</th>
        <th>Missões concluídas</th>
        <th>Pontuação média</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $row)
        <tr>
          <td>{{ $row['name'] }}</td>
          <td>{{ $row['registration_number'] }}</td>
          <td>{{ $row['experience'] }}</td>
          <td>{{ $row['level'] ?? '—' }}</td>
          <td>{{ $row['completed_missions'] }}</td>
          <td>{{ $row['average_score'] }}</td>
        </tr>
      @empty
        <tr><td colspan="6">Nenhum aluno nesta turma.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
