// Formatadores pt-BR. Datas SEMPRE parseadas via new Date() (a API devolve ISO created_at).
const brlFmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const intFmt = new Intl.NumberFormat('pt-BR')
const dateBRFmt = new Intl.DateTimeFormat('pt-BR', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
})

const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']

function parse(iso: string | null | undefined): Date | null {
  if (!iso) return null
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? null : d
}

export function useFormatters() {
  const brl = (v: number | null | undefined) => brlFmt.format(v ?? 0)
  const int = (v: number | null | undefined) => intFmt.format(v ?? 0)
  const pct = (v: number | null | undefined) => `${(v ?? 0).toFixed(1).replace('.', ',')}%`

  /** DD/MM/AAAA */
  const dateBR = (iso: string | null | undefined) => {
    const d = parse(iso)
    return d ? dateBRFmt.format(d) : ''
  }

  /** "DD mmm" (ex.: 24 jun) */
  const dateShort = (iso: string | null | undefined) => {
    const d = parse(iso)
    return d ? `${String(d.getDate()).padStart(2, '0')} ${MESES[d.getMonth()]}` : ''
  }

  return { brl, int, pct, dateBR, dateShort }
}
