import { ApolloClient, InMemoryCache, HttpLink } from "@apollo/client";
import {
  GET_GLOBAL_SEO,
  GET_SITE_CONTENT,
  OvuDayGlobalSeo,
  OvuDaySeo,
  OvuDaySiteContent,
  WPPost,
} from "./queries";

const WP_GRAPHQL_URL =
  process.env.NEXT_PUBLIC_WP_GRAPHQL_URL ?? "https://cms.ovuday.com/graphql";

/* ── Apollo Client (for client-side / SSR via Apollo) ────── */

export const apolloClient = new ApolloClient({
  link: new HttpLink({ uri: WP_GRAPHQL_URL, fetch }),
  cache: new InMemoryCache(),
  defaultOptions: {
    query: { fetchPolicy: "no-cache" },
  },
});

/* ── Native fetch wrapper (recommended for RSC / SSG) ─────── */

export async function fetchGraphQL<T>(
  query: string,
  variables?: Record<string, unknown>,
  revalidate: number | false = 3600
): Promise<T> {
  const res = await fetch(WP_GRAPHQL_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ query, variables }),
    next: revalidate === false ? { revalidate: 0 } : { revalidate },
  });

  if (!res.ok) {
    throw new Error(`GraphQL request failed: ${res.status} ${res.statusText}`);
  }

  const json = await res.json();

  if (json.errors?.length) {
    console.error("[GraphQL errors]", json.errors);
    throw new Error(json.errors[0].message);
  }

  return json.data as T;
}

/* ── Typed helpers ─────────────────────────────────────────── */

/** Fetch global SEO settings from WordPress. Cached 1 hour. */
export async function getGlobalSeo(): Promise<OvuDayGlobalSeo | null> {
  try {
    const data = await fetchGraphQL<{ ovudayGlobalSeo: OvuDayGlobalSeo }>(
      GET_GLOBAL_SEO,
      {},
      3600
    );
    return data.ovudayGlobalSeo ?? null;
  } catch {
    return null;
  }
}

/**
 * Build Next.js `Metadata` objects from ovudaySeo data.
 * Usage: export const generateMetadata = () => buildMetadata(post.ovudaySeo)
 */
export function buildMetadata(
  seo: OvuDaySeo | undefined,
  fallback?: { title?: string; description?: string; url?: string }
): Record<string, unknown> {
  if (!seo) {
    return {
      title: fallback?.title ?? "OvuDay",
      description: fallback?.description ?? "",
    };
  }

  const robots: Record<string, boolean> = {};
  if (seo.robots.noindex) robots.index = false;
  if (seo.robots.nofollow) robots.follow = false;
  if (seo.robots.noarchive) robots.archive = false;
  if (seo.robots.nosnippet) robots.snippet = false;
  if (seo.robots.noimageindex) robots.imageIndex = false;

  return {
    title: seo.title || fallback?.title || "OvuDay",
    description: seo.metaDescription || fallback?.description || "",
    alternates: {
      canonical: seo.canonical || fallback?.url || "",
    },
    robots: Object.keys(robots).length > 0 ? robots : undefined,
    openGraph: seo.og
      ? {
          title: seo.og.title || seo.title,
          description: seo.og.description || seo.metaDescription || "",
          url: seo.canonical,
          type: (seo.og.type as "article" | "website") ?? "article",
          siteName: seo.og.siteName,
          locale: seo.og.locale,
          images: seo.og.image
            ? [
                {
                  url: seo.og.image,
                  width: seo.og.imageWidth ?? 1200,
                  height: seo.og.imageHeight ?? 630,
                  alt: seo.og.title || seo.title,
                },
              ]
            : undefined,
        }
      : undefined,
    twitter: seo.twitter
      ? {
          card: seo.twitter.card as "summary_large_image" | "summary",
          title: seo.twitter.title || seo.title,
          description: seo.twitter.description || seo.metaDescription || "",
          images: seo.twitter.image ? [seo.twitter.image] : undefined,
          site: seo.twitter.site || undefined,
        }
      : undefined,
  };
}

/** Fetch all site content from the Content Builder. Cached 1 hour. */
export async function getSiteContent(): Promise<OvuDaySiteContent | null> {
  try {
    const data = await fetchGraphQL<{ ovudayContent: OvuDaySiteContent }>(
      GET_SITE_CONTENT,
      {},
      3600
    );
    return data.ovudayContent ?? null;
  } catch {
    return null;
  }
}

/**
 * Inject JSON-LD schema strings from WordPress into a page.
 * Returns an array of <script> tag contents (already rendered by plugin).
 *
 * Usage in RSC:
 *   const schemas = extractSchemas(post.ovudaySeo?.schemaJson)
 *   schemas.map((s, i) => <script key={i} dangerouslySetInnerHTML={{ __html: s }} />)
 */
export function extractSchemas(schemaJson: string | null | undefined): string[] {
  if (!schemaJson) return [];

  // The plugin outputs one or more <script type="application/ld+json">...</script> blocks
  const regex = /<script type="application\/ld\+json">([\s\S]*?)<\/script>/gi;
  const results: string[] = [];
  let match: RegExpExecArray | null;

  while ((match = regex.exec(schemaJson)) !== null) {
    results.push(match[1].trim());
  }

  return results;
}
