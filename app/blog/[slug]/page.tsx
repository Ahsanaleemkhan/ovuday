import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import AdSlot from "@/components/AdSlot";
import { fetchGraphQL, buildMetadata, extractSchemas, getSiteContent } from "@/lib/graphql";
import { GET_POST, GET_POST_SLUGS, GET_POSTS, WPPost } from "@/lib/queries";
import { getAdClient, parseSiteCopy, t } from "@/lib/siteCopy";

interface Props { params: { slug: string } }

/* ── Static params ─────────────────────────────────────────── */
export async function generateStaticParams() {
  try {
    const data = await fetchGraphQL<{ posts: { nodes: { slug: string; modified?: string }[] } }>(GET_POST_SLUGS);
    return data.posts.nodes.map(({ slug }) => ({ slug }));
  } catch { return []; }
}

/* ── Metadata ──────────────────────────────────────────────── */
export async function generateMetadata({ params }: Props): Promise<Metadata> {
  try {
    const data = await fetchGraphQL<{ post: WPPost }>(GET_POST, { slug: params.slug });
    const post = data.post;
    if (!post) return {};
    return buildMetadata(post.ovudaySeo, {
      title:       post.title,
      description: post.excerpt?.replace(/<[^>]+>/g, "") ?? "",
      url:         `https://ovuday.com/blog/${post.slug}`,
    }) as Metadata;
  } catch { return {}; }
}

/* ── Helpers ───────────────────────────────────────────────── */
const MONTHS = ["January","February","March","April","May","June","July","August","September","October","November","December"];
function formatDate(iso: string) {
  const d = new Date(iso);
  return `${MONTHS[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`;
}

function cleanExcerpt(html: string | undefined) {
  if (!html) return "";
  return html.replace(/<[^>]+>/g, "").trim();
}

/** Decode common HTML entities WordPress injects (smart quotes, etc.) */
function decodeEntities(str: string): string {
  return str
    .replace(/&#8220;/g, '"').replace(/&#8221;/g, '"')   // smart double quotes
    .replace(/&#8216;/g, "'").replace(/&#8217;/g, "'")    // smart single quotes
    .replace(/&#8211;/g, '–').replace(/&#8212;/g, '—')    // en-dash, em-dash
    .replace(/&#038;/g, '&').replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"').replace(/&#039;/g, "'")
    .replace(/&nbsp;/g, ' ');
}

/** Extract h2/h3 headings from HTML for Table of Contents */
function extractHeadings(html: string): { id: string; text: string; level: number }[] {
  const regex = /<h([23])[^>]*id="([^"]*)"[^>]*>(.*?)<\/h[23]>/gi;
  const results: { id: string; text: string; level: number }[] = [];
  let match: RegExpExecArray | null;
  while ((match = regex.exec(html)) !== null) {
    const rawText = match[3].replace(/<[^>]+>/g, "");
    results.push({ level: parseInt(match[1]), id: match[2], text: decodeEntities(rawText) });
  }
  return results;
}

/** Inject id="" into h2/h3 tags so TOC anchor links work */
function addHeadingIds(html: string): string {
  let counter = 0;
  return html.replace(/<h([23])([^>]*)>(.*?)<\/h[23]>/gi, (_, level, attrs, inner) => {
    const text = inner.replace(/<[^>]+>/g, "");
    const id = `heading-${++counter}-${text.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "")}`;
    if (attrs.includes('id=')) return `<h${level}${attrs}>${inner}</h${level}>`;
    return `<h${level}${attrs} id="${id}">${inner}</h${level}>`;
  });
}

/* ── Page ──────────────────────────────────────────────────── */
export default async function BlogPostPage({ params }: Props) {
  let post: WPPost | null = null;
  try {
    const data = await fetchGraphQL<{ post: WPPost }>(GET_POST, { slug: params.slug });
    post = data.post;
  } catch { notFound(); }

  if (!post) notFound();

  // Fetch blog settings from WordPress
  const siteContent = await getSiteContent();
  const blogCfg = siteContent?.blog;
  const seo = post.ovudaySeo;
  const copy = parseSiteCopy(siteContent?.siteCopyJson);

  const adsEnabled = siteContent?.adsEnabled ?? false;
  const adClient = getAdClient(siteContent);
  const adLabel = siteContent?.adLabel || "Advertisement";

  const topAdSlot = blogCfg?.detailTopAdSlot || "";
  const inlineAdSlot = blogCfg?.detailInlineAdSlot || "";
  const sidebarAdSlot = blogCfg?.detailSidebarAdSlot || "";
  const bottomAdSlot = blogCfg?.detailBottomAdSlot || "";

  const shareLabel = blogCfg?.detailShareLabel || t(copy, "blog.detail.shareLabel", "Share this article:");
  const backToBlogText =
    blogCfg?.detailBackToBlogText || t(copy, "blog.detail.backToBlogText", "Back to Blog");
  const adDisclosure = t(
    copy,
    "blog.adDisclosure",
    "Some pages may include advertising placements to keep OvuDay free."
  );

  // Process content: add IDs to headings
  const processedContent = addHeadingIds(post.content ?? "");
  const headings = extractHeadings(processedContent);

  // Fetch related posts for sidebar
  interface RelatedPost {
    id: string;
    slug: string;
    title: string;
    date: string;
    featuredImage: { node: { sourceUrl: string; altText: string } } | null;
    categories: { nodes: { name: string; slug: string }[] };
  }
  let relatedPosts: RelatedPost[] = [];
  let allCategories: { name: string; slug: string; count?: number }[] = [];
  try {
    const relatedData = await fetchGraphQL<{
      posts: { nodes: RelatedPost[] };
    }>(GET_POSTS, { first: 6 }, 300);
    relatedPosts = relatedData.posts.nodes.filter((p) => p.slug !== params.slug).slice(0, 3);
    // Extract unique categories from all posts
    const catMap = new Map<string, { name: string; slug: string; count: number }>();
    relatedData.posts.nodes.forEach((p) => {
      p.categories?.nodes?.forEach((c) => {
        const existing = catMap.get(c.slug);
        catMap.set(c.slug, { name: c.name, slug: c.slug, count: (existing?.count ?? 0) + 1 });
      });
    });
    allCategories = Array.from(catMap.values());
  } catch { /* sidebar degrades gracefully */ }

  // Schema
  const pluginSchemas = extractSchemas(seo?.schemaJson);
  const fallbackSchema = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline:      post.title,
    description:   seo?.metaDescription ?? "",
    url:           seo?.canonical ?? `https://ovuday.com/blog/${post.slug}`,
    datePublished: post.date,
    dateModified:  post.modified ?? post.date,
    image:         post.featuredImage?.node.sourceUrl,
    author: { "@type": "Person", name: seo?.schema?.authorName || post.author?.node.name || "OvuDay" },
    publisher: { "@type": "Organization", name: "OvuDay", url: "https://ovuday.com" },
    ...(seo?.schema?.reviewedBy ? { reviewedBy: { "@type": "Person", name: seo.schema.reviewedBy } } : {}),
  };

  const breadcrumbSchema = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      { "@type": "ListItem", position: 1, name: "Home", item: "https://ovuday.com" },
      { "@type": "ListItem", position: 2, name: "Blog", item: "https://ovuday.com/blog" },
      { "@type": "ListItem", position: 3, name: seo?.breadcrumbTitle || post.title },
    ],
  };

  const shareUrl   = encodeURIComponent(seo?.canonical ?? `https://ovuday.com/blog/${post.slug}`);
  const shareTitle = encodeURIComponent(post.title);
  const excerptText = cleanExcerpt(post.excerpt);

  return (
    <>
      {/* JSON-LD */}
      {pluginSchemas.length > 0
        ? pluginSchemas.map((s, i) => (
            <script key={i} type="application/ld+json" dangerouslySetInnerHTML={{ __html: s }} />
          ))
        : <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(fallbackSchema) }} />
      }
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbSchema) }} />

      <article className="py-14">
        <div className="container-main">
          <div className="mx-auto max-w-6xl">
            <AdSlot
              enabled={adsEnabled}
              clientId={adClient}
              slot={topAdSlot}
              label={adLabel}
              className="mb-8"
            />

            {adsEnabled ? (
              <p className="mb-8 text-center text-xs" style={{ color: "var(--color-muted)" }}>
                {adDisclosure}
              </p>
            ) : null}

            <div className={`grid grid-cols-1 gap-12 ${headings.length > 0 ? "lg:grid-cols-[1fr_280px]" : "lg:grid-cols-[1fr_260px]"}`}>
              <div>
              {(blogCfg?.detailShowBreadcrumb ?? true) && (
                <nav aria-label="Breadcrumb" className="mb-8">
                  <ol className="flex flex-wrap gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
                    <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
                    <li aria-hidden="true">/</li>
                    <li><Link href="/blog" style={{ color: "var(--color-primary)" }}>Blog</Link></li>
                    {post.categories?.nodes[0] && (
                      <>
                        <li aria-hidden="true">/</li>
                        <li>
                          <Link href={`/blog/category/${post.categories.nodes[0].slug}`}
                                style={{ color: "var(--color-primary)" }}>
                            {post.categories.nodes[0].name}
                          </Link>
                        </li>
                      </>
                    )}
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" className="truncate max-w-xs">
                      {seo?.breadcrumbTitle || post.title}
                    </li>
                  </ol>
                </nav>
              )}

              {(blogCfg?.showCategory ?? true) && post.categories?.nodes[0] && (
                <Link href={`/blog/category/${post.categories.nodes[0].slug}`}
                      className="badge mb-4 inline-block no-underline">
                  {post.categories.nodes[0].name}
                </Link>
              )}

              <h1 className="mb-4">{post.title}</h1>
              {excerptText ? (
                <p className="mb-5 text-base leading-relaxed" style={{ color: "var(--color-muted)" }}>
                  {excerptText}
                </p>
              ) : null}

              <div className="mb-8 flex flex-wrap items-center gap-4 text-sm"
                   style={{ color: "var(--color-muted)" }}>
                {(blogCfg?.showAuthor ?? true) && post.author && (
                  <span>By <strong style={{ color: "var(--color-text)" }}>
                    {seo?.schema?.authorName || post.author.node.name}
                  </strong></span>
                )}
                {(blogCfg?.showDate ?? true) && (
                  <span>
                    <time dateTime={post.date}>{formatDate(post.date)}</time>
                    {post.modified && post.modified !== post.date && (
                      <> · Updated <time dateTime={post.modified}>{formatDate(post.modified)}</time></>
                    )}
                  </span>
                )}
                {(blogCfg?.showReadingTime ?? true) && seo && seo.readingTime > 0 && (
                  <span>{seo.readingTime} min read</span>
                )}
                {seo?.schema?.reviewedBy && (
                  <span className="rounded-full px-2 py-0.5 text-xs font-medium"
                        style={{ background: "var(--color-surface)", color: "var(--color-primary)", border: "1px solid var(--color-border)" }}>
                    ✓ Reviewed by {seo.schema.reviewedBy}
                  </span>
                )}
              </div>

              {post.featuredImage && (
                <div className="relative mb-10 h-64 overflow-hidden rounded-xl sm:h-80 lg:h-96">
                  <Image
                    src={post.featuredImage.node.sourceUrl}
                    alt={post.featuredImage.node.altText || post.title}
                    fill sizes="(max-width: 768px) 100vw, 760px"
                    className="object-cover" priority
                  />
                </div>
              )}

              <div className="prose-content"
                   dangerouslySetInnerHTML={{ __html: processedContent }} />

              <AdSlot
                enabled={adsEnabled}
                clientId={adClient}
                slot={inlineAdSlot}
                label={adLabel}
                className="mt-10"
              />

              {(blogCfg?.showTags ?? true) && post.tags?.nodes && post.tags.nodes.length > 0 && (
                <div className="mt-8 flex flex-wrap gap-2">
                  {post.tags.nodes.map(tag => (
                    <span key={tag.slug} className="rounded-full px-3 py-1 text-xs"
                          style={{ background: "var(--color-surface)", color: "var(--color-muted)", border: "1px solid var(--color-border)" }}>
                      #{tag.name}
                    </span>
                  ))}
                </div>
              )}

              {blogCfg?.detailMedicalDisclaimer && (
                <div className="mt-10 rounded-xl border-l-4 p-5"
                     style={{ borderColor: "var(--color-primary)", background: "var(--color-surface)" }}>
                  <p className="text-xs leading-relaxed" style={{ color: "var(--color-muted)" }}>
                    ⚕️ <strong>Medical Disclaimer:</strong> {blogCfg.detailMedicalDisclaimer}
                  </p>
                </div>
              )}

              {(blogCfg?.detailShowShare ?? true) && (
                <div className="mt-8 flex flex-wrap items-center gap-3">
                  <span className="text-sm font-semibold" style={{ color: "var(--color-muted)" }}>
                    {shareLabel}
                  </span>
                  <a href={`https://twitter.com/intent/tweet?text=${shareTitle}&url=${shareUrl}`}
                     target="_blank" rel="noopener noreferrer"
                     className="rounded-lg px-4 py-2 text-sm font-medium text-white"
                     style={{ background: "#1d9bf0" }}>
                    𝕏 Twitter
                  </a>
                  <a href={`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`}
                     target="_blank" rel="noopener noreferrer"
                     className="rounded-lg px-4 py-2 text-sm font-medium text-white"
                     style={{ background: "#1877f2" }}>
                    Facebook
                  </a>
                  <a href={`https://pinterest.com/pin/create/button/?url=${shareUrl}&description=${shareTitle}`}
                     target="_blank" rel="noopener noreferrer"
                     className="rounded-lg px-4 py-2 text-sm font-medium text-white"
                     style={{ background: "#e60023" }}>
                    Pinterest
                  </a>
                  <a href={`https://wa.me/?text=${shareTitle}%20${shareUrl}`}
                     target="_blank" rel="noopener noreferrer"
                     className="rounded-lg px-4 py-2 text-sm font-medium text-white"
                     style={{ background: "#25d366" }}>
                    WhatsApp
                  </a>
                </div>
              )}

              <AdSlot
                enabled={adsEnabled}
                clientId={adClient}
                slot={bottomAdSlot}
                label={adLabel}
                className="mt-8"
              />

              <div className="divider" />

              {(blogCfg?.detailShowAuthorBox ?? true) && post.author && (
                <div className="rounded-xl border p-5 mb-10"
                     style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}>
                  <div className="flex items-start gap-4">
                    {post.author.node.avatar?.url && (
                      <Image src={post.author.node.avatar.url} alt={post.author.node.name}
                             width={56} height={56} className="rounded-full shrink-0" />
                    )}
                    <div>
                      <p className="font-bold" style={{ color: "var(--color-text)" }}>
                        {post.author.node.name}
                      </p>
                      <p className="mt-1 text-sm" style={{ color: "var(--color-muted)" }}>
                        {post.author.node.description || "OvuDay contributor"}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              <Link href="/blog" className="btn-secondary no-underline">← {backToBlogText}</Link>
              </div>

              <aside className="hidden lg:block">
                <div className="sticky top-24 space-y-5">
                  {/* ── Table of Contents ── */}
                  {headings.length > 0 && (
                    <div className="rounded-xl border p-5"
                         style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}>
                      <p className="mb-3 flex items-center gap-2 text-sm font-bold" style={{ color: "var(--color-text)" }}>
                        <span>📑</span> {blogCfg?.detailTocTitle || "In This Article"}
                      </p>
                      <nav aria-label="Table of contents">
                        <ul className="space-y-2">
                          {headings.map((h) => (
                            <li key={h.id} style={{ paddingLeft: h.level === 3 ? "1rem" : "0" }}>
                              <a
                                href={`#${h.id}`}
                                className="block text-sm leading-snug no-underline transition-colors hover:underline"
                                style={{ color: "var(--color-muted)" }}
                              >
                                {h.text}
                              </a>
                            </li>
                          ))}
                        </ul>
                      </nav>
                    </div>
                  )}

                  {/* ── Calculator CTA Widget ── */}
                  <div className="rounded-xl p-5 text-center"
                       style={{ background: "linear-gradient(135deg, #FFF0F5 0%, #FFE4ED 100%)", border: "1px solid #FECDD3" }}>
                    <p className="text-2xl mb-2">🌸</p>
                    <p className="text-sm font-bold mb-1" style={{ color: "var(--color-text)" }}>
                      Free Ovulation Calculator
                    </p>
                    <p className="text-xs mb-3" style={{ color: "var(--color-muted)" }}>
                      Find your most fertile days — private, science-backed, and completely free.
                    </p>
                    <Link
                      href="/"
                      className="inline-block rounded-lg px-4 py-2 text-sm font-semibold text-white no-underline transition-transform hover:scale-105"
                      style={{ background: "var(--color-primary)" }}
                    >
                      Calculate Now →
                    </Link>
                  </div>

                  {/* ── Sidebar Ad Slot ── */}
                  <AdSlot
                    enabled={adsEnabled}
                    clientId={adClient}
                    slot={sidebarAdSlot}
                    label={adLabel}
                  />

                  {/* ── Related Posts ── */}
                  {relatedPosts.length > 0 && (
                    <div className="rounded-xl border p-5"
                         style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}>
                      <p className="mb-4 flex items-center gap-2 text-sm font-bold" style={{ color: "var(--color-text)" }}>
                        <span>📚</span> {blogCfg?.detailRelatedTitle || "Related Articles"}
                      </p>
                      <ul className="space-y-4">
                        {relatedPosts.map((rp) => (
                          <li key={rp.id}>
                            <Link
                              href={`/blog/${rp.slug}`}
                              className="group flex gap-3 no-underline"
                            >
                              {rp.featuredImage && (
                                <div className="relative h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg">
                                  <Image
                                    src={rp.featuredImage.node.sourceUrl}
                                    alt={rp.featuredImage.node.altText || rp.title}
                                    fill
                                    sizes="56px"
                                    className="object-cover"
                                  />
                                </div>
                              )}
                              <div className="min-w-0">
                                <p className="text-sm font-medium leading-snug group-hover:underline"
                                   style={{ color: "var(--color-text)" }}>
                                  {rp.title}
                                </p>
                                <p className="mt-1 text-xs" style={{ color: "var(--color-muted)" }}>
                                  {formatDate(rp.date)}
                                </p>
                              </div>
                            </Link>
                          </li>
                        ))}
                      </ul>
                      <Link
                        href="/blog"
                        className="mt-4 inline-block text-xs font-semibold no-underline hover:underline"
                        style={{ color: "var(--color-primary)" }}
                      >
                        View all articles →
                      </Link>
                    </div>
                  )}

                  {/* ── Categories ── */}
                  {allCategories.length > 0 && (
                    <div className="rounded-xl border p-5"
                         style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}>
                      <p className="mb-3 flex items-center gap-2 text-sm font-bold" style={{ color: "var(--color-text)" }}>
                        <span>🏷️</span> Categories
                      </p>
                      <div className="flex flex-wrap gap-2">
                        {allCategories.map((cat) => (
                          <Link
                            key={cat.slug}
                            href={`/blog/category/${cat.slug}`}
                            className="rounded-full px-3 py-1 text-xs font-medium no-underline transition-colors hover:opacity-80"
                            style={{ background: "var(--color-primary-light, #FFF0F5)", color: "var(--color-primary)", border: "1px solid var(--color-border)" }}
                          >
                            {cat.name}
                          </Link>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* ── Newsletter / Engagement Widget ── */}
                  <div className="rounded-xl border p-5"
                       style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}>
                    <p className="mb-2 flex items-center gap-2 text-sm font-bold" style={{ color: "var(--color-text)" }}>
                      <span>💡</span> Did You Know?
                    </p>
                    <p className="text-xs leading-relaxed" style={{ color: "var(--color-muted)" }}>
                      Only about 13% of women have a consistent 28-day cycle. Your cycle length can vary — and that is perfectly normal. Understanding your unique pattern is the first step.
                    </p>
                    <Link
                      href="/how-it-works"
                      className="mt-3 inline-block text-xs font-semibold no-underline hover:underline"
                      style={{ color: "var(--color-primary)" }}
                    >
                      Learn how ovulation works →
                    </Link>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </article>
    </>
  );
}
