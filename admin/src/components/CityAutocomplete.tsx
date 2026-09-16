import { useMemo, useState } from 'react'

interface City {
  id: number
  name: string
  state: string | null
  country: string | null
}

interface CityAutocompleteProps {
  cities: City[] | undefined
  value: string
  onChange: (cityId: string) => void
  inputClassName: string
}

/** A plain <select> can't reasonably hold 34k+ world cities — this filters as you type instead. */
export default function CityAutocomplete({ cities, value, onChange, inputClassName }: CityAutocompleteProps) {
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)

  const selected = cities?.find((c) => String(c.id) === value)

  const matches = useMemo(() => {
    if (!cities || query.trim().length < 2) return []
    const q = query.toLowerCase()
    return cities.filter((c) => c.name.toLowerCase().includes(q) || c.country?.toLowerCase().includes(q)).slice(0, 20)
  }, [cities, query])

  const label = (c: City) => `${c.name}${c.state ? `, ${c.state}` : ''}${c.country ? `, ${c.country}` : ''}`

  return (
    <div className="relative">
      <input
        required
        placeholder="Type to search a city…"
        value={open ? query : selected ? label(selected) : ''}
        onFocus={() => {
          setQuery('')
          setOpen(true)
        }}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        onChange={(e) => setQuery(e.target.value)}
        className={inputClassName}
      />
      {open && matches.length > 0 && (
        <ul className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-neutral-300 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
          {matches.map((c) => (
            <li key={c.id}>
              <button
                type="button"
                onMouseDown={(e) => e.preventDefault()}
                onClick={() => {
                  onChange(String(c.id))
                  setOpen(false)
                }}
                className="block w-full px-3 py-1.5 text-left text-sm hover:bg-neutral-100 dark:hover:bg-neutral-700"
              >
                {label(c)}
              </button>
            </li>
          ))}
        </ul>
      )}
      {open && query.trim().length >= 2 && matches.length === 0 && (
        <p className="absolute z-10 mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-400 shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
          No matching city.
        </p>
      )}
    </div>
  )
}
