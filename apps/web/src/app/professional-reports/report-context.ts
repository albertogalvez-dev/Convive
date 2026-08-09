export function reportContextLabel(context: string): string {
  const labels: Record<string, string> = {
    in_person: 'En el centro',
    digital: 'Entorno digital',
    mixed: 'En el centro y online',
    unknown: 'Contexto sin concretar',
  };

  return labels[context] ?? context;
}
