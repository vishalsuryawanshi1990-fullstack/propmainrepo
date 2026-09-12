'use client'

import { useMemo, useState } from 'react'

export default function EmiCalculator({ price }: { price: number }) {
  const [downPaymentPercent, setDownPaymentPercent] = useState(20)
  const [years, setYears] = useState(20)
  const [rate, setRate] = useState(8.5)

  const emi = useMemo(() => {
    const principal = price * (1 - downPaymentPercent / 100)
    const monthlyRate = rate / 12 / 100
    const months = years * 12
    if (monthlyRate === 0) return principal / months
    return (principal * monthlyRate * (1 + monthlyRate) ** months) / ((1 + monthlyRate) ** months - 1)
  }, [price, downPaymentPercent, years, rate])

  return (
    <div className="rounded-2xl border border-neutral-200 bg-white p-5">
      <h2 className="mb-4 font-semibold text-neutral-900">EMI Calculator</h2>

      <div className="space-y-4 text-sm">
        <div>
          <div className="mb-1 flex justify-between text-neutral-600">
            <span>Down payment</span>
            <span className="font-medium">{downPaymentPercent}%</span>
          </div>
          <input
            type="range"
            min={0}
            max={90}
            value={downPaymentPercent}
            onChange={(e) => setDownPaymentPercent(Number(e.target.value))}
            className="w-full accent-primary-600"
          />
        </div>
        <div>
          <div className="mb-1 flex justify-between text-neutral-600">
            <span>Loan tenure</span>
            <span className="font-medium">{years} years</span>
          </div>
          <input
            type="range"
            min={5}
            max={30}
            value={years}
            onChange={(e) => setYears(Number(e.target.value))}
            className="w-full accent-primary-600"
          />
        </div>
        <div>
          <div className="mb-1 flex justify-between text-neutral-600">
            <span>Interest rate</span>
            <span className="font-medium">{rate}%</span>
          </div>
          <input
            type="range"
            min={5}
            max={15}
            step={0.1}
            value={rate}
            onChange={(e) => setRate(Number(e.target.value))}
            className="w-full accent-primary-600"
          />
        </div>
      </div>

      <div className="mt-5 rounded-xl bg-primary-50 p-4 text-center">
        <p className="text-xs text-neutral-500">Estimated monthly payment</p>
        <p className="text-2xl font-bold text-primary-800">
          ₹{Math.round(emi).toLocaleString('en-IN')}
          <span className="text-sm font-normal text-neutral-500">/mo</span>
        </p>
      </div>
    </div>
  )
}
