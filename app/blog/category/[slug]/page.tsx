import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { notFound } from "next/navigation";
import AdSlot from "@/components/AdSlot";
import { fetchGraphQL, getSiteContent } from "@/lib/graphql";
import { getAdClient, parseSiteCopy, t } from "@/lib/siteCopy";

interface Props { params: { slug: string } }

const GET_POSTS_BY_CATEGORY = `
  query GetPostsByCategory($slug: ID!, $first: Int = 12) {
    category(id: $slug, idType: SLUG) {
      name
      description
      count
      posts(first: $first, where: { status: PUBLISH }) {
        nodes {
          id
          slug
          title
          excerpt
          date
          featuredImage {
            node { sourceUrl altText }
          }
          author { node { name } }
          ovudaySeo { readingTime }
        }
      }
    }
  }
`;

interface CategoryData {
  category: {
    name:        string;
    description: string;
    count:       number;
    posts: {
      nodes: {
        id:            string;
        slug:          string;
        title:         string;
        excerpt:       string;
        date:          string;
        featuredImage: { node: { sourceUrl: string; altText: string } } | null;
        author:        { node: { name: string } };
        ovudaySeo:     { readingTime: number } | null;
      }[];
    };
  } | null;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  try {
    const data = await fetchGraphQL<CategoryData>(GET_POSTS_BY_CATEGORY, {
      slug: params.slug,
    });
    const cat = data.category;
    if (!cat) return {};
    return {
      title:       `${cat.name} Articles — OvuDay Blog`,
      description: cat.description || `Read all ${cat.name} articles on OvuDay.`,
      alternates:  { canonical: `https://ovuday.com/blog/category/${params.slug}` },
    };
  } catch {
    return {};
  }
}

const MONTHS_SHORT = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
function formatDate(iso: string) {
  const d = new Date(iso);
  return `${MONTHS_SHORT[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`;
}

export default async function CategoryPage({ params }: Props) {
  let category: CategoryData["category"] = null;
  const siteContent = await getSiteContent();
  const blogCfg = siteContent?.blog;
  const copy = parseSiteCopy(siteContent?.siteCopyJson);
  const adsEnabled = siteContent?.adsEnabled ?? false;
  const adClient = getAdClient(siteContent);
  const adLabel = siteContent?.adLabel || "Advertisement";

  try {
    const data = await fetchGraphQL<CategoryData>(GET_POSTS_BY_CATEGORY, {
      slug: params.slug,
    });
    category = data.category;
  } catch {
    notFound();
  }

  if (!category) notFound();

  const posts = category.posts.nodes;
  const readMoreText = blogCfg?.readMoreText || t(copy, "blog.readMore", "Read article");
  const listingTitle = blogCfg?.listingTitle || t(copy, "blog.title", "Ovulation & Fertility Guides");
  const topAdSlot = blogCfg?.listingTopAdSlot || "";

  return (
    <div className="py-14">
      {/* Header */}
      <section
        className="py-12 text-center"
        style={{ background: "linear-gradient(135deg, #FFF0F5 0%, #FFF8FB 100%)" }}
        aria-labelledby="cat-heading"
      >
        <div className="container-main">
          <nav aria-label="Breadcrumb" className="mb-6">
            <ol className="flex justify-center gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
              <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
              <li aria-hidden="true">/</li>
              <li><Link href="/blog" style={{ color: "var(--color-primary)" }}>Blog</Link></li>
              <li aria-hidden="true">/</li>
              <li aria-current="page">{category.name}</li>
            </ol>
          </nav>
          <div className="badge mx-auto mb-4 w-fit">Category</div>
          <h1 id="cat-heading">{category.name} in {listingTitle}</h1>
          {category.description && (
            <p className="mx-auto mt-3 max-w-xl text-base" style={{ color: "#4B5563" }}>
              {category.description}
            </p>
          )}
          <p className="mt-3 text-sm" style={{ color: "var(--color-muted)" }}>
            {category.count} article{category.count !== 1 ? "s" : ""}
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

      {/* Posts */}
      <div className="container-main mt-12">
        {posts.length === 0 ? (
          <p className="text-center text-base" style={{ color: "var(--color-muted)" }}>
            No articles in this category yet.{" "}
            <Link href="/blog">Browse all articles →</Link>
          </p>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {posts.map((post) => (
              <article
                key={post.id}
                className="card group flex flex-col overflow-hidden p-0"
                aria-labelledby={`cat-post-${post.id}`}
              >
                {post.featuredImage && (
                  <Link href={`/blog/${post.slug}`} tabIndex={-1} aria-hidden="true">
                    <div className="relative h-44 overflow-hidden bg-pink-50">
                      <Image
                        src={post.featuredImage.node.sourceUrl}
                        alt={post.featuredImage.node.altText || post.title}
                        fill
                        sizes="(max-width: 640px) 100vw, 33vw"
                        className="object-cover transition-transform duration-300 group-hover:scale-105"
                      />
                    </div>
                  </Link>
                )}
                <div className="flex flex-1 flex-col p-5">
                  <h2 id={`cat-post-${post.id}`} className="text-base leading-snug">
                    <Link
                      href={`/blog/${post.slug}`}
                      className="no-underline hover:underline"
                      style={{ color: "var(--color-text)" }}
                    >
                      {post.title}
                    </Link>
                  </h2>
                  <p
                    className="mt-2 text-sm leading-relaxed line-clamp-2"
                    style={{ color: "var(--color-muted)" }}
                    dangerouslySetInnerHTML={{ __html: post.excerpt }}
                  />
                  <Link
                    href={`/blog/${post.slug}`}
                    className="mt-3 inline-flex w-fit items-center gap-1.5 text-sm font-semibold no-underline hover:underline"
                    style={{ color: "var(--color-primary)" }}
                  >
                    {readMoreText}
                  </Link>
                  <div
                    className="mt-4 flex items-center justify-between border-t pt-3 text-xs"
                    style={{ borderColor: "var(--color-border)", color: "var(--color-muted)" }}
                  >
                    <span>
                      {post.author.node.name}
                      {blogCfg?.showReadingTime && post.ovudaySeo?.readingTime
                        ? ` · ${post.ovudaySeo.readingTime} min read`
                        : ""}
                    </span>
                    <span>{formatDate(post.date)}</span>
                  </div>
                </div>
              </article>
            ))}
          </div>
        )}

        <div className="mt-12 text-center">
          <Link href="/blog" className="btn-secondary no-underline">
            ← All Articles
          </Link>
        </div>
      </div>
    </div>
  );
}
