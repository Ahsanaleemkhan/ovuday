import type { MetadataRoute } from "next";
import { fetchGraphQL } from "@/lib/graphql";
import { GET_POST_SLUGS, GET_CATEGORIES } from "@/lib/queries";

const BASE = "https://ovuday.com";
const STATIC_LAST_MODIFIED = process.env.NEXT_PUBLIC_SITE_UPDATED_AT
  ? new Date(process.env.NEXT_PUBLIC_SITE_UPDATED_AT)
  : new Date("2026-04-24T00:00:00.000Z");

function safeDate(input?: string | null) {
  if (!input) return STATIC_LAST_MODIFIED;
  const parsed = new Date(input);
  return Number.isNaN(parsed.getTime()) ? STATIC_LAST_MODIFIED : parsed;
}

const staticRoutes: MetadataRoute.Sitemap = [
  { url: BASE,                     lastModified: STATIC_LAST_MODIFIED, changeFrequency: "weekly",  priority: 1.0 },
  { url: `${BASE}/how-it-works`,   lastModified: STATIC_LAST_MODIFIED, changeFrequency: "monthly", priority: 0.8 },
  { url: `${BASE}/faq`,            lastModified: STATIC_LAST_MODIFIED, changeFrequency: "monthly", priority: 0.8 },
  { url: `${BASE}/blog`,           lastModified: STATIC_LAST_MODIFIED, changeFrequency: "daily",   priority: 0.9 },
  { url: `${BASE}/about`,          lastModified: STATIC_LAST_MODIFIED, changeFrequency: "monthly", priority: 0.5 },
  { url: `${BASE}/contact`,        lastModified: STATIC_LAST_MODIFIED, changeFrequency: "monthly", priority: 0.4 },
  { url: `${BASE}/privacy-policy`, lastModified: STATIC_LAST_MODIFIED, changeFrequency: "yearly",  priority: 0.2 },
  { url: `${BASE}/terms`,          lastModified: STATIC_LAST_MODIFIED, changeFrequency: "yearly",  priority: 0.2 },
];

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  let postRoutes:     MetadataRoute.Sitemap = [];
  let categoryRoutes: MetadataRoute.Sitemap = [];

  // Fetch blog posts (with modified dates for accurate lastModified)
  try {
    const data = await fetchGraphQL<{ posts: { nodes: { slug: string; modified?: string | null }[] } }>(
      GET_POST_SLUGS,
      undefined,
      false  // don't cache the sitemap fetch itself
    );
    postRoutes = data.posts.nodes.map(({ slug, modified }) => ({
      url:             `${BASE}/blog/${slug}`,
      lastModified:    safeDate(modified),
      changeFrequency: "monthly" as const,
      priority:        0.7,
    }));
  } catch {
    // WordPress not reachable — sitemap degrades to static routes only
  }

  // Fetch category archive pages
  try {
    const data = await fetchGraphQL<{
      categories: { nodes: { slug: string; count: number }[] }
    }>(GET_CATEGORIES, undefined, false);

    categoryRoutes = data.categories.nodes
      .filter(c => c.count > 0) // only include categories that have published posts
      .map(({ slug }) => ({
        url:             `${BASE}/blog/category/${slug}`,
        lastModified:    STATIC_LAST_MODIFIED,
        changeFrequency: "weekly" as const,
        priority:        0.6,
      }));
  } catch {
    // non-fatal — category routes simply won't appear in sitemap
  }

  return [...staticRoutes, ...categoryRoutes, ...postRoutes];
}
