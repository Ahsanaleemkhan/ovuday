"use client";

import { useState } from "react";
import { format, subDays } from "date-fns";
import {
  Calendar, RefreshCw, Moon, HelpCircle, AlertTriangle,
  ShieldCheck, Lightbulb, RotateCcw, Flower, Heart,
  CalendarDays, Activity, ArrowRight, Check,
} from "@/components/icons";
import {
  calculateOvulation, formatDate, formatShortDate,
  type OvulationResult, type CycleResult,
} from "@/lib/ovulation";
import { OvuDayCalculatorContent } from "@/lib/queries";

interface FormState { lmp: string; cycleLength: number; lutealPhase: number }

const defaultCalculatorContent: OvuDayCalculatorContent = {
  title: "Ovulation Calculator",
  subtitle: "Find your most fertile days",
  lmpLabel: "First day of your last period",
  lmpHelp: "Select the first day of your most recent menstrual period.",
  cycleLabel: "Cycle length",
  cycleHelp: "The number of days from the first day of one period to the first day of the next. Average is 28 days.",
  lutealLabel: "Luteal phase",
  lutealHelp: "The phase after ovulation until your next period. Average is 14 days.",
  calculateBtn: "Calculate My Fertile Window",
  resetBtn: "Start over",
  resultTitle: "Your Results",
  cycleOverviewLabel: "Your {cycleLength}-day cycle overview",
  fertileWindowTitle: "Fertile Window",
  fertileWindowLabel: "Fertile Window Start",
  ovulationDayLabel: "Ovulation Day",
  nextPeriodLabel: "Next Period",
  peakDayLabel: "Peak Day",
  tabCurrent: "This Cycle",
  tabNextCycles: "Next 3 Cycles",
  tipText: "The 2 days before ovulation and ovulation day itself are your most fertile.",
  privacyNote: "Your data never leaves your browser.",
  disclaimer: "This calculator provides estimates only. Consult a healthcare professional for medical advice.",
};

interface OvulationCalculatorProps {
  calculator?: OvuDayCalculatorContent;
}

/* ─── Icon wrapper ────────────────────────────────────────── */
function FieldIcon({ children }: { children: React.ReactNode }) {
  return (
    <span
      className="absolute left-3 top-1/2 -translate-y-1/2 flex items-center"
      style={{ color: "var(--color-primary)" }}
      aria-hidden="true"
    >
      {children}
    </span>
  );
}

/* ─── Tooltip ─────────────────────────────────────────────── */
function Tooltip({ text }: { text: string }) {
  return (
    <span className="relative group inline-flex ml-1.5 cursor-help align-middle">
      <HelpCircle
        size={14}
        style={{ color: "var(--color-muted)" }}
        aria-label="More info"
      />
      <span
        className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 rounded-xl px-3 py-2.5 text-xs leading-relaxed text-white opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 shadow-xl"
        style={{ background: "#1A1A2E" }}
        role="tooltip"
      >
        {text}
        <span
          className="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent"
          style={{ borderTopColor: "#1A1A2E" }}
        />
      </span>
    </span>
  );
}

/* ─── Cycle Phase Bar ─────────────────────────────────────── */
function CyclePhaseBar({ cycleLength, lutealPhase, calculator }: { cycleLength: number; lutealPhase: number; calculator: OvuDayCalculatorContent }) {
  const periodDays     = 5;
  const follicularDays = cycleLength - lutealPhase - periodDays - 1;
  const fertileDays    = 6;

  const segments = [
    { label: "Period",      days: periodDays,     bg: "#FDA4AF", fg: "#9F1239" },
    { label: "Follicular",  days: Math.max(follicularDays, 0), bg: "#FDE68A", fg: "#92400E" },
    { label: "Fertile",     days: fertileDays,    bg: "#86EFAC", fg: "#14532D" },
    { label: "Luteal",      days: lutealPhase,    bg: "#C4B5FD", fg: "#4C1D95" },
  ];

  return (
    <div className="mb-5">
      <p className="mb-2 text-xs font-semibold flex items-center gap-1.5" style={{ color: "var(--color-muted)" }}>
        <Activity size={12} />
        {calculator.cycleOverviewLabel.replace('{cycleLength}', cycleLength.toString())}
      </p>
      <div className="flex h-8 overflow-hidden rounded-lg">
        {segments.map(({ label, days, bg, fg }) => (
          <div
            key={label}
            title={`${label}: ~${days} days`}
            className="flex items-center justify-center overflow-hidden text-xs font-semibold"
            style={{ width: `${(days / cycleLength) * 100}%`, background: bg, color: fg, minWidth: "2px" }}
          >
            {days >= 5 ? label : ""}
          </div>
        ))}
      </div>
      <div className="mt-1 flex justify-between text-xs" style={{ color: "var(--color-muted)" }}>
        <span>Day 1</span>
        <span>Day {cycleLength}</span>
      </div>
    </div>
  );
}

/* ─── Countdown Ring ──────────────────────────────────────── */
function CountdownRing({ days }: { days: number }) {
  const isPast  = days < 0;
  const isToday = days === 0;
  const abs     = Math.abs(days);

  return (
    <div
      className="flex flex-col items-center justify-center rounded-2xl p-5 text-center"
      style={{
        background: isToday
          ? "linear-gradient(135deg, var(--color-primary), #7C5CBF)"
          : isPast ? "#F3F4F6" : "var(--color-primary-bg)",
      }}
    >
      {isToday ? (
        <Flower size={40} style={{ color: "white" }} aria-hidden="true" />
      ) : (
        <span
          className="font-heading font-bold leading-none"
          style={{
            fontSize: "2.8rem",
            color: isPast ? "var(--color-muted)" : "var(--color-primary)",
          }}
        >
          {abs}
        </span>
      )}
      <span
        className="mt-1 text-xs font-semibold uppercase tracking-wide"
        style={{ color: isToday ? "rgba(255,255,255,0.85)" : "var(--color-muted)" }}
      >
        {isToday ? "Peak fertility — today!" : isPast ? `days since ovulation` : `days until ovulation`}
      </span>
    </div>
  );
}

/* ─── Date Pill ───────────────────────────────────────────── */
function DatePill({
  icon, label, date, accent,
}: {
  icon: React.ReactNode; label: string; date: Date; accent?: string;
}) {
  return (
    <div
      className="flex items-center gap-3 rounded-xl border p-3.5"
      style={{ borderColor: accent ? `${accent}30` : "var(--color-border)", background: accent ? `${accent}08` : "white" }}
    >
      <span
        className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
        style={{ background: accent ? `${accent}15` : "var(--color-primary-bg)", color: accent || "var(--color-primary)" }}
        aria-hidden="true"
      >
        {icon}
      </span>
      <div className="min-w-0">
        <p className="text-xs font-semibold uppercase tracking-wide" style={{ color: "var(--color-muted)" }}>{label}</p>
        <p className="truncate text-sm font-bold leading-tight" style={{ color: accent || "var(--color-text)" }}>
          {formatDate(date)}
        </p>
      </div>
    </div>
  );
}

/* ─── Fertile Window Bar ──────────────────────────────────── */
function FertileBar({ start, end, peak, calculator }: { start: Date; end: Date; peak: Date; calculator: OvuDayCalculatorContent }) {
  const days = ["-5", "-4", "-3", "-2", "-1", "Peak"];
  return (
    <div>
      <p className="mb-2 text-xs font-semibold uppercase tracking-wide flex items-center gap-1.5" style={{ color: "var(--color-muted)" }}>
        <Heart size={12} />
        {calculator.fertileWindowTitle}
      </p>
      <div className="grid grid-cols-6 gap-1">
        {days.map((d, i) => {
          const isPeak = i === 5;
          return (
            <div
              key={d}
              className="flex flex-col items-center justify-center rounded-lg py-2.5 text-xs font-bold"
              style={{
                background: isPeak ? "var(--color-primary)" : "#BBF7D0",
                color: isPeak ? "white" : "#065F46",
              }}
            >
              {isPeak
                ? <Flower size={14} aria-label="Ovulation day" />
                : <span>{d}</span>
              }
            </div>
          );
        })}
      </div>
      <div className="mt-1 flex justify-between text-xs" style={{ color: "var(--color-muted)" }}>
        <span>{formatShortDate(start)}</span>
        <span className="font-semibold" style={{ color: "var(--color-primary)" }}>Peak: {formatShortDate(peak)}</span>
        <span>{formatShortDate(end)}</span>
      </div>
    </div>
  );
}

/* ─── Future Cycle Row ────────────────────────────────────── */
function FutureCycleRow({ cycle, num }: { cycle: CycleResult; num: number }) {
  return (
    <div className="grid grid-cols-2 gap-2 rounded-xl border p-3 sm:grid-cols-4" style={{ borderColor: "var(--color-border)" }}>
      {[
        { label: `Cycle ${num}`,    value: formatShortDate(cycle.periodStart),    color: "#DC2626" },
        { label: "Fertile Window",  value: `${formatShortDate(cycle.fertileStart)} – ${formatShortDate(cycle.fertileEnd)}`, color: "#059669" },
        { label: "Ovulation",       value: formatShortDate(cycle.ovulationDate),  color: "var(--color-primary)" },
        { label: "Next Period",     value: formatShortDate(cycle.nextPeriod),     color: "var(--color-text)" },
      ].map(({ label, value, color }) => (
        <div key={label}>
          <p className="mb-0.5 text-xs font-semibold uppercase tracking-wide" style={{ color: "var(--color-muted)" }}>{label}</p>
          <p className="text-sm font-bold" style={{ color }}>{value}</p>
        </div>
      ))}
    </div>
  );
}

/* ─── Results ─────────────────────────────────────────────── */
function Results({ result, onReset, calculator }: { result: OvulationResult; onReset: () => void; calculator: OvuDayCalculatorContent }) {
  const { currentCycle, nextCycles, daysUntilOvulation } = result;
  const [tab, setTab] = useState<"current" | "future">("current");

  return (
    <div className="mt-6 animate-slide-up space-y-4">
      {/* Tabs */}
      <div className="flex overflow-hidden rounded-xl border" style={{ borderColor: "var(--color-border)" }}>
        {(["current", "future"] as const).map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className="flex-1 py-2.5 text-sm font-semibold transition-all"
            style={{
              background: tab === t ? "var(--color-primary)" : "white",
              color:      tab === t ? "white" : "var(--color-muted)",
            }}
            aria-pressed={tab === t}
          >
            {t === "current" ? calculator.tabCurrent : calculator.tabNextCycles}
          </button>
        ))}
      </div>

      {tab === "current" ? (
        <div className="space-y-4">
          <CountdownRing days={daysUntilOvulation} />

          <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <DatePill icon={<Flower size={16} />}       label={calculator.ovulationDayLabel}  date={currentCycle.ovulationDate} accent="#E8476E" />
            <DatePill icon={<Heart size={16} />}        label={calculator.fertileWindowLabel} date={currentCycle.fertileStart}  accent="#059669" />
            <DatePill icon={<CalendarDays size={16} />} label={calculator.nextPeriodLabel}    date={currentCycle.periodStart}   accent="#DC2626" />
            <DatePill icon={<Calendar size={16} />}     label={calculator.peakDayLabel}       date={currentCycle.nextPeriod}    accent="#7C5CBF" />
          </div>

          <div className="rounded-xl border p-4" style={{ borderColor: "#BBF7D0", background: "#F0FDF4" }}>
            <FertileBar start={currentCycle.fertileStart} end={currentCycle.fertileEnd} peak={currentCycle.ovulationDate} calculator={calculator} />
          </div>

          <div
            className="flex items-start gap-3 rounded-xl border-l-4 px-4 py-3 text-sm"
            style={{ borderColor: "var(--color-primary)", background: "var(--color-primary-bg)", color: "#7A0A3D" }}
          >
            <Lightbulb size={16} className="mt-0.5 shrink-0" aria-hidden="true" />
            <span><strong>Best time to try:</strong> {calculator.tipText}</span>
          </div>
        </div>
      ) : (
        <div className="space-y-3">
          {nextCycles.map((cycle, i) => <FutureCycleRow key={i} cycle={cycle} num={i + 2} />)}
          <p className="text-center text-xs" style={{ color: "var(--color-muted)" }}>
            Forecasts are estimates based on your cycle length.
          </p>
        </div>
      )}

      <button
        onClick={onReset}
        className="flex w-full items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-semibold transition-colors hover:bg-pink-50"
        style={{ borderColor: "var(--color-border)", color: "var(--color-muted)" }}
      >
        <RotateCcw size={14} aria-hidden="true" />
        {calculator.resetBtn}
      </button>

      <p className="text-center text-xs" style={{ color: "var(--color-muted)" }}>
        {calculator.disclaimer}
      </p>
    </div>
  );
}

/* ─── Main Calculator ─────────────────────────────────────── */
export default function OvulationCalculator({ calculator: calculatorProp }: OvulationCalculatorProps) {
  const calculator = calculatorProp || defaultCalculatorContent;
  const defaultLmp = format(subDays(new Date(), 14), "yyyy-MM-dd");
  const [form, setForm]       = useState<FormState>({ lmp: defaultLmp, cycleLength: 28, lutealPhase: 14 });
  const [result, setResult]   = useState<OvulationResult | null>(null);
  const [error, setError]     = useState<string>("");
  const [loading, setLoading] = useState(false);

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    const { name, value } = e.target;
    setForm((p) => ({ ...p, [name]: name === "lmp" ? value : Number(value) }));
    setError(""); setResult(null);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault(); setError("");
    const lmpDate = new Date(form.lmp);
    if (isNaN(lmpDate.getTime()))              { setError("Please enter a valid date for your last period."); return; }
    if (lmpDate > new Date())                  { setError("Last period date cannot be in the future."); return; }
    if (form.cycleLength < 21 || form.cycleLength > 45) { setError("Cycle length must be between 21–45 days."); return; }
    if (form.lutealPhase < 10 || form.lutealPhase > 16) { setError("Luteal phase must be between 10–16 days."); return; }
    setLoading(true);
    setTimeout(() => { setResult(calculateOvulation(lmpDate, form.cycleLength, form.lutealPhase)); setLoading(false); }, 400);
  }

  function handleReset() { setResult(null); setError(""); setForm({ lmp: defaultLmp, cycleLength: 28, lutealPhase: 14 }); }

  return (
    <div
      className="rounded-2xl bg-white p-5 sm:p-6"
      style={{ boxShadow: "0 8px 40px 0 rgb(232 71 110 / 0.12), 0 1px 3px 0 rgb(0 0 0 / 0.06)" }}
      aria-label="Ovulation Calculator"
    >
      {/* Header */}
      <div className="mb-5 flex items-center gap-3">
        <span
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
          style={{ background: "var(--color-primary-bg)", color: "var(--color-primary)" }}
          aria-hidden="true"
        >
          <Flower size={20} />
        </span>
        <div>
          <h2 className="text-base font-bold" style={{ color: "var(--color-text)", fontSize: "1rem" }}>
            {calculator.title}
          </h2>
          <p className="text-xs" style={{ color: "var(--color-muted)" }}>{calculator.subtitle}</p>
        </div>
      </div>

      {!result && <CyclePhaseBar cycleLength={form.cycleLength} lutealPhase={form.lutealPhase} calculator={calculator} />}

      {!result && (
        <form onSubmit={handleSubmit} noValidate className="space-y-4">

          {/* LMP */}
          <div>
            <label htmlFor="lmp" className="form-label flex items-center gap-1.5">
              <Calendar size={14} style={{ color: "var(--color-primary)" }} aria-hidden="true" />
              {calculator.lmpLabel}
              <span style={{ color: "var(--color-primary)" }} aria-hidden="true">*</span>
            </label>
            <div className="relative">
              <input
                id="lmp" name="lmp" type="date"
                className="form-input pl-10"
                value={form.lmp}
                onChange={handleChange}
                max={format(new Date(), "yyyy-MM-dd")}
                required aria-required="true"
              />
              <FieldIcon><Calendar size={15} /></FieldIcon>
            </div>
            <p className="mt-1 text-xs" style={{ color: "var(--color-muted)" }}>{calculator.lmpHelp}</p>
          </div>

          {/* Cycle + Luteal */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label htmlFor="cycleLength" className="form-label flex items-center gap-1">
                <RefreshCw size={13} style={{ color: "var(--color-primary)" }} aria-hidden="true" />
                {calculator.cycleLabel}
                <Tooltip text={calculator.cycleHelp} />
              </label>
              <div className="relative">
                <select
                  id="cycleLength" name="cycleLength"
                  className="form-input pl-9"
                  value={form.cycleLength}
                  onChange={handleChange}
                >
                  {Array.from({ length: 25 }, (_, i) => i + 21).map((d) => (
                    <option key={d} value={d}>{d} days{d === 28 ? " (avg)" : ""}</option>
                  ))}
                </select>
                <FieldIcon><RefreshCw size={14} /></FieldIcon>
              </div>
            </div>

            <div>
              <label htmlFor="lutealPhase" className="form-label flex items-center gap-1">
                <Moon size={13} style={{ color: "var(--color-primary)" }} aria-hidden="true" />
                {calculator.lutealLabel}
                <Tooltip text={calculator.lutealHelp} />
              </label>
              <div className="relative">
                <select
                  id="lutealPhase" name="lutealPhase"
                  className="form-input pl-9"
                  value={form.lutealPhase}
                  onChange={handleChange}
                >
                  {Array.from({ length: 7 }, (_, i) => i + 10).map((d) => (
                    <option key={d} value={d}>{d} days{d === 14 ? " (avg)" : ""}</option>
                  ))}
                </select>
                <FieldIcon><Moon size={14} /></FieldIcon>
              </div>
            </div>
          </div>

          {/* Error */}
          {error && (
            <div
              role="alert"
              className="flex items-start gap-2.5 rounded-xl px-4 py-3 text-sm"
              style={{ background: "#FEE2E2", color: "#991B1B" }}
            >
              <AlertTriangle size={16} className="mt-0.5 shrink-0" aria-hidden="true" />
              {error}
            </div>
          )}

          {/* Submit */}
          <button
            type="submit"
            className="btn-primary w-full py-3.5 text-base gap-2"
            disabled={loading}
            aria-busy={loading}
          >
            {loading ? (
              <>
                <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" aria-hidden="true" />
                Calculating your cycle…
              </>
            ) : (
              <>
                <Flower size={18} aria-hidden="true" />
                {calculator.calculateBtn}
              </>
            )}
          </button>

          {/* Privacy note */}
          <p className="flex items-center justify-center gap-1.5 text-center text-xs" style={{ color: "var(--color-muted)" }}>
            <ShieldCheck size={13} aria-hidden="true" />
            {calculator.privacyNote}
          </p>
        </form>
      )}

      {result && <Results result={result} onReset={handleReset} calculator={calculator} />}
    </div>
  );
}
