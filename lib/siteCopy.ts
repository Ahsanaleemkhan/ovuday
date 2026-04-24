import type { OvuDaySiteContent } from "@/lib/queries";

export type SiteCopyMap = Record<string, string>;

export function parseSiteCopy(siteCopyJson?: string | null): SiteCopyMap {
  if (!siteCopyJson) return {};

  try {
    const parsed = JSON.parse(siteCopyJson) as unknown;

    if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) {
      return {};
    }

    return Object.fromEntries(
      Object.entries(parsed).filter((entry): entry is [string, string] => {
        return typeof entry[0] === "string" && typeof entry[1] === "string";
      })
    );
  } catch {
    return {};
  }
}

export function t(copy: SiteCopyMap, key: string, fallback: string): string {
  const value = copy[key];
  if (!value) return fallback;
  return value;
}

export function getAdClient(siteContent: OvuDaySiteContent | null): string {
  return process.env.NEXT_PUBLIC_ADSENSE_CLIENT_ID || siteContent?.adsenseClient || "";
}
