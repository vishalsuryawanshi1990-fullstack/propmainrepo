import { useEffect, useState } from 'react'
import { motion } from 'framer-motion'

interface StatCardProps {
  label: string
  value: number
  prefix?: string
  accent?: boolean
}

/** Count-up animation on load, per 10-ui-ux-animation-guide.md's admin dashboard spec. */
export default function StatCard({ label, value, prefix, accent }: StatCardProps) {
  const [display, setDisplay] = useState(0)

  useEffect(() => {
    let raf: number
    const start = performance.now()
    const duration = 800

    const tick = (now: number) => {
      const progress = Math.min((now - start) / duration, 1)
      setDisplay(Math.round(value * (1 - (1 - progress) ** 3)))
      if (progress < 1) raf = requestAnimationFrame(tick)
    }

    raf = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(raf)
  }, [value])

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      className={`rounded-2xl border p-5 shadow-sm ${
        accent
          ? 'border-accent-500/30 bg-accent-400/10'
          : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900'
      }`}
    >
      <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">{label}</p>
      <p className="mt-1 text-3xl font-semibold text-neutral-900 dark:text-neutral-50">
        {prefix}
        {display.toLocaleString()}
      </p>
    </motion.div>
  )
}
