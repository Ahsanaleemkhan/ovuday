import { addDays, format, startOfDay } from "date-fns";

export interface CycleResult {
  cycleNumber:       number;
  periodStart:       Date;
  ovulationDate:     Date;
  fertileStart:      Date;
  fertileEnd:        Date;
  nextPeriod:        Date;
}

export interface OvulationResult {
  currentCycle:      CycleResult;
  nextCycles:        CycleResult[];
  peakFertilityDay:  Date;
  daysUntilOvulation: number;
}

function buildCycle(lmp: Date, cycleLength: number, lutealPhase: number, cycleNumber: number): CycleResult {
  const periodStart    = addDays(lmp, cycleLength * cycleNumber);
  const ovulationDate  = addDays(periodStart, cycleLength - lutealPhase);
  const fertileStart   = addDays(ovulationDate, -5);
  const fertileEnd     = addDays(ovulationDate, 1);
  const nextPeriod     = addDays(periodStart, cycleLength);

  return { cycleNumber, periodStart, ovulationDate, fertileStart, fertileEnd, nextPeriod };
}

export function calculateOvulation(
  lmp: Date,
  cycleLength  = 28,
  lutealPhase  = 14
): OvulationResult {
  const base     = startOfDay(lmp);
  const today    = startOfDay(new Date());

  const currentCycle = buildCycle(base, cycleLength, lutealPhase, 0);
  const nextCycles   = [1, 2, 3].map((n) => buildCycle(base, cycleLength, lutealPhase, n));

  const diff = Math.round(
    (currentCycle.ovulationDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24)
  );

  return {
    currentCycle,
    nextCycles,
    peakFertilityDay:    currentCycle.ovulationDate,
    daysUntilOvulation:  diff,
  };
}

export function formatDate(date: Date): string {
  return format(date, "MMMM d, yyyy");
}

export function formatShortDate(date: Date): string {
  return format(date, "MMM d");
}

export function getDaysBetween(a: Date, b: Date): number {
  return Math.round((b.getTime() - a.getTime()) / (1000 * 60 * 60 * 24));
}

export function isInFertileWindow(date: Date, result: OvulationResult): boolean {
  const d = startOfDay(date).getTime();
  return (
    d >= result.currentCycle.fertileStart.getTime() &&
    d <= result.currentCycle.fertileEnd.getTime()
  );
}

export function isOvulationDay(date: Date, result: OvulationResult): boolean {
  return (
    startOfDay(date).getTime() ===
    startOfDay(result.currentCycle.ovulationDate).getTime()
  );
}
