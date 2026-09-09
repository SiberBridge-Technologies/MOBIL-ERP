export function normalizeDate(input:string): string | null {
  const m = input.trim().match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
  if (!m) return null;
  const d = new Date(Number(m[3]), Number(m[2])-1, Number(m[1]));
  if (d.getFullYear() !== Number(m[3]) || d.getMonth() !== Number(m[2])-1 || d.getDate() !== Number(m[1])) return null;
  return m[3]+'-'+m[2]+'-'+m[1];
}
