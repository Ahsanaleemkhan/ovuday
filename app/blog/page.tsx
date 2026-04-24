import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import AdSlot from "@/components/AdSlot";
import { fetchGraphQL, getSiteContent } from "@/lib/graphql";
import { GET_POSTS } from "@/lib/queries";
import { getAdClient, parseSiteCopy, t } from "@/lib/siteCopy";

export const metadata: Metadata = {
  title: "Ovulation & Fertility Blog — Expert Tips & Guides | OvuDay",
  description:
    "Read OvuDay's fertility blog for expert-written guides on ovulation, cycle tracking, trying to conceive, and reproductive health.",
  alternates: { canonical: "https://ovuday.com/blog" },
};

interface Post {
  id: string;
  slug: string;
  title: string;
  excerpt: string;
  date: string;
  featuredImage: { node: { sourceUrl: string; altText: string } } | null;
  categories: { nodes: { name: string; slug: string }[] };
  author: { node: { name: string } };
  ovudaySeo: { readingTime: number } | null;
}

interface PostsData {
  posts: { nodes: Post[]; pageInfo: { hasNextPage: boolean } };
}

const MONTHS = ["January","February","March","April","May","June","July","August","September","October","November","December"];
function formatDate(iso: string) {
  const d = new Date(iso);
  return `${MONTHS[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`;
}

function cleanExcerpt(html: string) {
  return html.replace(/<[^>]+>/g, "").trim();
}

function PostCard({
  post,
  showAuthor,
  showDate,
  showCategory,
  showReadingTime,
  readMoreText,
}: {
  post: Post;
  showAuthor: boolean;
  showDate: boolean;
  showCategory: boolean;
  showReadingTime: boolean;
  readMoreText: string;
}) {
  const category = post.categories.nodes[0];

  return (
    <article
      className="card group flex flex-col overflow-hidden p-0"
      aria-labelledby={`post-${post.id}`}
    >
      {post.featuredImage && (
        <Link href={`/blog/${post.slug}`} tabIndex={-1} aria-hidden="true">
          <div className="relative h-44 overflow-hidden bg-pink-50">
            <Image
              src={post.featuredImage.node.sourceUrl}
              alt={post.featuredImage.node.altText || post.title}
              fill
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
              className="object-cover transition-transform duration-300 group-hover:scale-105"
            />
          </div>
        </Link>
      )}
      <div className="flex flex-1 flex-col p-5">
        {showCategory && category && (
          <span className="badge mb-3 w-fit text-xs">{category.name}</span>
        )}

        <h2 id={`post-${post.id}`} className="text-base leading-snug">
          <Link
            href={`/blog/${post.slug}`}
            className="no-underline hover:underline"
            style={{ color: "var(--color-text)" }}
          >
            {post.title}
          </Link>
        </h2>

        <p
          className="mt-2 text-sm leading-relaxed line-clamp-3"
          style={{ color: "var(--color-muted)" }}
        >
          {cleanExcerpt(post.excerpt)}
        </p>

        <Link
          href={`/blog/${post.slug}`}
          className="mt-4 inline-flex w-fit items-center gap-1.5 text-sm font-semibold no-underline hover:underline"
          style={{ color: "var(--color-primary)" }}
        >
          {readMoreText}
        </Link>

        <div
          className="mt-4 flex items-center justify-between border-t pt-4 text-xs"
          style={{ borderColor: "var(--color-border)", color: "var(--color-muted)" }}
        >
          <span>
            {showAuthor ? post.author.node.name : "OvuDay"}
            {showReadingTime && post.ovudaySeo?.readingTime ? ` · ${post.ovudaySeo.readingTime} min read` : ""}
          </span>
          <span>{showDate ? formatDate(post.date) : ""}</span>
        </div>
      </div>
    </article>
  );
}

export default async function BlogPage() {
  const siteContent = await getSiteContent();
  const blogCfg = siteContent?.blog;
  const copy = parseSiteCopy(siteContent?.siteCopyJson);

  const adsEnabled = siteContent?.adsEnabled ?? false;
  const adClient = getAdClient(siteContent);
  const adLabel = siteContent?.adLabel || "Advertisement";

  let posts: Post[] = [];

  try {
    const data = await fetchGraphQL<PostsData>(GET_POSTS, {
      first: blogCfg?.postsPerPage || 12,
    }, 600);
    posts = data.posts.nodes;
  } catch {
    // Keep empty state for crawl-safe behavior when CMS is unavailable.
  }

  const listingBadge = blogCfg?.listingBadge || t(copy, "blog.badge", "Fertility Blog");
  const listingTitle = blogCfg?.listingTitle || t(copy, "blog.title", "Ovulation & Fertility Guides");
  const listingSubtitle =
    blogCfg?.listingSubtitle ||
    t(copy, "blog.subtitle", "Expert-written articles to help you understand your cycle and fertility.");
  const noPostsText =
    blogCfg?.noPostsText || t(copy, "blog.emptyState", "No articles available yet. Please check back soon.");
  const readMoreText = blogCfg?.readMoreText || t(copy, "blog.readMore", "Read article");

  const showCategory = blogCfg?.showCategory ?? true;
  const showAuthor = blogCfg?.showAuthor ?? true;
  const showDate = blogCfg?.showDate ?? true;
  const showReadingTime = blogCfg?.showReadingTime ?? true;

  const inlineEvery = Math.max(blogCfg?.listingInlineEvery || 4, 2);
  const topAdSlot = blogCfg?.listingTopAdSlot || "";
  const inlineAdSlot = blogCfg?.listingInlineAdSlot || "";

  const featuredPost = (blogCfg?.featuredPost ?? true) && posts.length > 0 ? posts[0] : null;
  const remainingPosts = featuredPost ? posts.slice(1) : posts;

  return (
    <div className="py-14">
      <section
        className="py-12 text-center"
        style={{ background: "linear-gradient(135deg, #FFF0F5 0%, #FFF8FB 100%)" }}
        aria-labelledby="blog-heading"
      >
        <div className="container-main">
          <div className="badge mx-auto mb-4 w-fit">{listingBadge}</div>
          <h1 id="blog-heading">{listingTitle}</h1>
          <p className="mx-auto mt-3 max-w-xl text-base" style={{ color: "#4B5563" }}>
            {listingSubtitle}
          </p>
        </div>
      </section>

      <div className="container-main mt-8">
        <AdSlot
          enabled={adsEnabled}
          clientId={adClient}
          slot={topAdSlot}
          label={adLabel}
          className="mx-auto max-w-3xl"
        />
      </div>

      {featuredPost && (
        <section className="container-main mt-10" aria-labelledby="featured-post-heading">
          <p
            id="featured-post-heading"
            className="mb-4 text-sm font-semibold uppercase tracking-wider"
            style={{ color: "var(--color-primary)" }}
          >
            Featured Article
          </p>
          <article className="card overflow-hidden p-0 lg:grid lg:grid-cols-[1.3fr_1fr]">
            <Link href={`/blog/${featuredPost.slug}`} className="relative min-h-[250px] bg-pink-50" aria-label={featuredPost.title}>
              {featuredPost.featuredImage && (
                <Image
                  src={featuredPost.featuredImage.node.sourceUrl}
                  alt={featuredPost.featuredImage.node.altText || featuredPost.title}
                  fill
                  sizes="(max-width: 1024px) 100vw, 65vw"
                  className="object-cover"
                />
              )}
            </Link>

            <div className="p-6">
              {showCategory && featuredPost.categories.nodes[0] && (
                <span className="badge mb-3 inline-flex">{featuredPost.categories.nodes[0].name}</span>
              )}
              <h2 className="text-xl leading-snug">
                <Link href={`/blog/${featuredPost.slug}`} className="no-underline hover:underline" style={{ color: "var(--color-text)" }}>
                  {featuredPost.title}
                </Link>
              </h2>
              <p className="mt-3 text-sm leading-relaxed" style={{ color: "var(--color-muted)" }}>
                {cleanExcerpt(featuredPost.excerpt)}
              </p>
              <div className="mt-5 flex flex-wrap items-center gap-2 text-xs" style={{ color: "var(--color-muted)" }}>
                {showAuthor && <span>{featuredPost.author.node.name}</span>}
                {showDate && <span>· {formatDate(featuredPost.date)}</span>}
                {showReadingTime && featuredPost.ovudaySeo?.readingTime ? (
                  <span>· {featuredPost.ovudaySeo.readingTime} min read</span>
                ) : null}
              </div>
              <Link
                href={`/blog/${featuredPost.slug}`}
                className="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold no-underline hover:underline"
                style={{ color: "var(--color-primary)" }}
              >
                {readMoreText}
              </Link>
            </div>
          </article>
        </section>
      )}

      <div className="container-main mt-12">
        {remainingPosts.length === 0 && !featuredPost ? (
          <p className="text-center text-sm" style={{ color: "var(--color-muted)" }}>
            {noPostsText}
          </p>
        ) : (
          <div className={`grid gap-6 ${
              remainingPosts.length === 1
                ? "sm:grid-cols-1 max-w-lg mx-auto"
                : remainingPosts.length === 2
                ? "sm:grid-cols-2 max-w-3xl mx-auto"
                : "sm:grid-cols-2 lg:grid-cols-3"
            }`}>
            {remainingPosts.map((post, index) => {
              const shouldInjectAd =
                adsEnabled &&
                Boolean(inlineAdSlot) &&
                index > 0 &&
                (index + 1) % inlineEvery === 0;

              return (
                <div key={post.id} className="contents">
                  <PostCard
                    post={post}
                    showAuthor={showAuthor}
                    showDate={showDate}
                    showCategory={showCategory}
                    showReadingTime={showReadingTime}
                    readMoreText={readMoreText}
                  />
                  {shouldInjectAd ? (
                    <AdSlot
                      enabled={adsEnabled}
                      clientId={adClient}
                      slot={inlineAdSlot}
                      label={adLabel}
                      className="sm:col-span-2 lg:col-span-3"
                    />
                  ) : null}
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
