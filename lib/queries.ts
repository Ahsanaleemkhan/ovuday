/* ─────────────────────────────────────────────────────────────
   OvuDay GraphQL Queries
   All SEO data comes from the custom ovudaySeo field exposed
   by the OvuDay WordPress plugin (class-graphql-extensions.php).
   ───────────────────────────────────────────────────────────── */

/** Reusable SEO fragment — used on every post/page query */
export const OVUDAY_SEO_FRAGMENT = `
  fragment OvuDaySeoFields on OvuDaySeo {
    title
    metaDescription
    focusKeyword
    canonical
    robotsString
    breadcrumbTitle
    readingTime
    seoScore
    schemaJson
    robots {
      noindex
      nofollow
      noarchive
      nosnippet
      noimageindex
    }
    og {
      title
      description
      image
      imageWidth
      imageHeight
      type
      siteName
      locale
    }
    twitter {
      card
      title
      description
      image
      site
    }
    schema {
      type
      reviewedBy
      authorName
      customJson
    }
    sitemap {
      exclude
      priority
      changefreq
    }
    redirect {
      url
      type
    }
    analysis {
      wordCount
      keywordDensity
      keywordInTitle
      keywordInDesc
      keywordInContent
      keywordInH1
      hasMetaDesc
      hasOgImage
      hasFeaturedImage
      titleLength
      descLength
    }
  }
`;

/* ── Posts listing ─────────────────────────────────────────── */

export const GET_POSTS = `
  ${OVUDAY_SEO_FRAGMENT}
  query GetPosts($first: Int = 12, $after: String) {
    posts(first: $first, after: $after, where: { status: PUBLISH }) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        slug
        title
        excerpt
        date
        modified
        featuredImage {
          node { sourceUrl altText mediaDetails { width height } }
        }
        categories {
          nodes { name slug }
        }
        tags {
          nodes { name slug }
        }
        author {
          node { name slug }
        }
        ovudaySeo {
          ...OvuDaySeoFields
        }
      }
    }
  }
`;

/* ── Single post ───────────────────────────────────────────── */

export const GET_POST = `
  ${OVUDAY_SEO_FRAGMENT}
  query GetPost($slug: ID!) {
    post(id: $slug, idType: SLUG) {
      id
      databaseId
      title
      content
      excerpt
      date
      modified
      slug
      featuredImage {
        node { sourceUrl altText mediaDetails { width height } }
      }
      categories {
        nodes { name slug }
      }
      tags {
        nodes { name slug }
      }
      author {
        node {
          name
          slug
          description
          avatar { url width height }
        }
      }
      ovudaySeo {
        ...OvuDaySeoFields
      }
    }
  }
`;

/* ── Single page ───────────────────────────────────────────── */

export const GET_PAGE = `
  ${OVUDAY_SEO_FRAGMENT}
  query GetPage($slug: ID!) {
    page(id: $slug, idType: URI) {
      id
      databaseId
      title
      content
      date
      modified
      slug
      featuredImage {
        node { sourceUrl altText mediaDetails { width height } }
      }
      author {
        node { name slug }
      }
      ovudaySeo {
        ...OvuDaySeoFields
      }
    }
  }
`;

/* ── Post slugs (for generateStaticParams) ─────────────────── */

export const GET_POST_SLUGS = `
  query GetPostSlugs {
    posts(first: 1000, where: { status: PUBLISH }) {
      nodes { slug modified }
    }
  }
`;

/* ── Page slugs ────────────────────────────────────────────── */

export const GET_PAGE_SLUGS = `
  query GetPageSlugs {
    pages(first: 200, where: { status: PUBLISH }) {
      nodes { slug uri }
    }
  }
`;

/* ── Categories ────────────────────────────────────────────── */

export const GET_CATEGORIES = `
  query GetCategories {
    categories(where: { hideEmpty: true }) {
      nodes {
        id
        databaseId
        name
        slug
        description
        count
      }
    }
  }
`;

/* ── Posts by category ─────────────────────────────────────── */

export const GET_POSTS_BY_CATEGORY = `
  ${OVUDAY_SEO_FRAGMENT}
  query GetPostsByCategory($slug: String!, $first: Int = 12, $after: String) {
    posts(
      first: $first
      after: $after
      where: { status: PUBLISH, categoryName: $slug }
    ) {
      pageInfo {
        hasNextPage
        endCursor
      }
      nodes {
        id
        databaseId
        slug
        title
        excerpt
        date
        featuredImage {
          node { sourceUrl altText mediaDetails { width height } }
        }
        categories {
          nodes { name slug }
        }
        author {
          node { name slug }
        }
        ovudaySeo {
          ...OvuDaySeoFields
        }
      }
    }
  }
`;

/* ── Global SEO settings (home meta, org schema, GA, etc.) ── */

export const GET_GLOBAL_SEO = `
  query GetGlobalSeo {
    ovudayGlobalSeo {
      siteName
      titleSeparator
      homeTitle
      homeDescription
      homeOgImage
      organizationName
      organizationLogo
      twitterUsername
      facebookUrl
      instagramUrl
      googleSiteVerify
      bingSiteVerify
      googleAnalytics
      googleTagManager
      noindexDate
      noindexAuthor
      noindexSearch
      noindex404
      breadcrumbsEnable
      breadcrumbsHome
      breadcrumbsSep
      sitemapEnable
      sitemapPosts
      sitemapPages
      sitemapCats
      allowedOrigins
    }
  }
`;

/* ── Site Content (Content Builder) ───────────────────────── */

export const GET_SITE_CONTENT = `
  query GetSiteContent {
    ovudayContent {
      hero {
        badgeText headline headlineAccent subheadline
        ctaPrimaryText ctaPrimaryUrl ctaSecondaryText ctaSecondaryUrl
        bgGradientFrom bgGradientTo
      }
      navigation { label url }
      trust { icon text color }
      stats { value suffix label }
      features { icon title description color }
      featuresSectionTitle featuresSectionSubtitle
      steps { number icon title description }
      stepsSectionTitle stepsSectionSubtitle
      faq { question answer category }
      faqSectionTitle faqSectionSubtitle
      calculator {
        title subtitle
        lmpLabel lmpHelp cycleLabel cycleHelp lutealLabel lutealHelp
        calculateBtn resetBtn resultTitle cycleOverviewLabel fertileWindowTitle
        fertileWindowLabel ovulationDayLabel nextPeriodLabel peakDayLabel
        tabCurrent tabNextCycles tipText privacyNote disclaimer
      }
      cta { title subtitle btnText btnUrl bgColor textColor }
      blog {
        listingBadge listingTitle listingSubtitle postsPerPage layout excerptLength
        showAuthor showDate showCategory showReadingTime showTags featuredPost
        noPostsText readMoreText
        detailShowAuthorBox detailShowRelated detailRelatedCount detailRelatedTitle
        detailShowBreadcrumb detailShowShare detailShowToc detailTocTitle
        detailMedicalDisclaimer detailShareLabel detailBackToBlogText
        listingTopAdSlot listingInlineAdSlot listingInlineEvery
        detailTopAdSlot detailInlineAdSlot detailSidebarAdSlot detailBottomAdSlot
      }
      footer {
        logoText tagline copyright disclaimer
        socialTwitter socialFacebook socialInstagram socialPinterest
        newsletterEnable newsletterTitle newsletterPlaceholder newsletterBtn
        links { title items { label url } }
      }
      aboutPage {
        badge title intro storyTitle storyP1 storyP2
        valuesTitle values { icon title description }
        disclaimer ctaPrimaryText ctaPrimaryUrl ctaSecondaryText ctaSecondaryUrl
      }
      howPage {
        badge title intro
        phasesTitle phasesSubtitle
        phases { name days description color textColor }
        formulaTitle formulaExample
        fertileTitle fertileExplanation
        limitationsTitle limitations
        ctaText ctaBtnText ctaBtnUrl
      }
      contactPage {
        badge title intro successMessage responseTime
        formEmail formSubject subjects
      }
      privacyPage { title sections { heading body } }
      termsPage { title sections { heading body } }
      adsEnabled adsenseClient adLabel sponsoredLabel siteCopyJson
    }
  }
`;

/* ── TypeScript interfaces ─────────────────────────────────── */

export interface OvuDayHero {
  badgeText: string;
  headline: string;
  headlineAccent: string;
  subheadline: string;
  ctaPrimaryText: string;
  ctaPrimaryUrl: string;
  ctaSecondaryText: string;
  ctaSecondaryUrl: string;
  bgGradientFrom: string;
  bgGradientTo: string;
}

export interface OvuDayNavLink {
  label: string;
  url: string;
}

export interface OvuDayTrustBadge {
  icon: string;
  text: string;
  color: string;
}

export interface OvuDayStatItem {
  value: string;
  suffix: string;
  label: string;
}

export interface OvuDayFeatureItem {
  icon: string;
  title: string;
  description: string;
  color: string;
}

export interface OvuDayStepItem {
  number: string;
  icon: string;
  title: string;
  description: string;
}

export interface OvuDayFaqItem {
  question: string;
  answer: string;
  category: string;
}

export interface OvuDayCalculatorContent {
  title: string;
  subtitle: string;
  lmpLabel: string;
  lmpHelp: string;
  cycleLabel: string;
  cycleHelp: string;
  lutealLabel: string;
  lutealHelp: string;
  calculateBtn: string;
  resetBtn: string;
  resultTitle: string;
  cycleOverviewLabel: string;
  fertileWindowTitle: string;
  fertileWindowLabel: string;
  ovulationDayLabel: string;
  nextPeriodLabel: string;
  peakDayLabel: string;
  tabCurrent: string;
  tabNextCycles: string;
  tipText: string;
  privacyNote: string;
  disclaimer: string;
}

export interface OvuDayCta {
  title: string;
  subtitle: string;
  btnText: string;
  btnUrl: string;
  bgColor: string;
  textColor: string;
}

export interface OvuDayBlogSettings {
  listingBadge: string;
  listingTitle: string;
  listingSubtitle: string;
  postsPerPage: number;
  layout: "grid" | "list" | "masonry";
  showAuthor: boolean;
  showDate: boolean;
  showCategory: boolean;
  showReadingTime: boolean;
  showTags: boolean;
  excerptLength: number;
  featuredPost: boolean;
  noPostsText: string;
  readMoreText: string;
  detailShowAuthorBox: boolean;
  detailShowRelated: boolean;
  detailRelatedCount: number;
  detailRelatedTitle: string;
  detailShowBreadcrumb: boolean;
  detailShowShare: boolean;
  detailShowToc: boolean;
  detailTocTitle: string;
  detailMedicalDisclaimer: string;
  detailShareLabel: string;
  detailBackToBlogText: string;
  listingTopAdSlot: string;
  listingInlineAdSlot: string;
  listingInlineEvery: number;
  detailTopAdSlot: string;
  detailInlineAdSlot: string;
  detailSidebarAdSlot: string;
  detailBottomAdSlot: string;
}

export interface OvuDayFooterLink { label: string; url: string }
export interface OvuDayFooterColumn { title: string; items: OvuDayFooterLink[] }

export interface OvuDayFooterContent {
  logoText: string;
  tagline: string;
  copyright: string;
  disclaimer: string;
  socialTwitter: string;
  socialFacebook: string;
  socialInstagram: string;
  socialPinterest: string;
  newsletterEnable: boolean;
  newsletterTitle: string;
  newsletterPlaceholder: string;
  newsletterBtn: string;
  links: OvuDayFooterColumn[];
}

export interface OvuDayAboutValue { icon: string; title: string; description: string }
export interface OvuDayAboutPage {
  badge: string; title: string; intro: string;
  storyTitle: string; storyP1: string; storyP2: string;
  valuesTitle: string; values: OvuDayAboutValue[];
  disclaimer: string;
  ctaPrimaryText: string; ctaPrimaryUrl: string;
  ctaSecondaryText: string; ctaSecondaryUrl: string;
}

export interface OvuDayCyclePhase {
  name: string; days: string; description: string; color: string; textColor: string;
}
export interface OvuDayHowPage {
  badge: string; title: string; intro: string;
  phasesTitle: string; phasesSubtitle: string; phases: OvuDayCyclePhase[];
  formulaTitle: string; formulaExample: string;
  fertileTitle: string; fertileExplanation: string;
  limitationsTitle: string; limitations: string[];
  ctaText: string; ctaBtnText: string; ctaBtnUrl: string;
}

export interface OvuDayContactPage {
  badge: string; title: string; intro: string;
  successMessage: string; responseTime: string;
  formEmail: string; formSubject: string; subjects: string[];
}

export interface OvuDayLegalSection { heading: string; body: string }
export interface OvuDayLegalPage { title: string; sections: OvuDayLegalSection[] }

export interface OvuDaySiteContent {
  hero: OvuDayHero;
  navigation: OvuDayNavLink[];
  trust: OvuDayTrustBadge[];
  stats: OvuDayStatItem[];
  features: OvuDayFeatureItem[];
  featuresSectionTitle: string;
  featuresSectionSubtitle: string;
  steps: OvuDayStepItem[];
  stepsSectionTitle: string;
  stepsSectionSubtitle: string;
  faq: OvuDayFaqItem[];
  faqSectionTitle: string;
  faqSectionSubtitle: string;
  calculator: OvuDayCalculatorContent;
  cta: OvuDayCta;
  blog: OvuDayBlogSettings;
  footer: OvuDayFooterContent;
  aboutPage: OvuDayAboutPage;
  howPage: OvuDayHowPage;
  contactPage: OvuDayContactPage;
  privacyPage: OvuDayLegalPage;
  termsPage: OvuDayLegalPage;
  adsEnabled: boolean;
  adsenseClient: string;
  adLabel: string;
  sponsoredLabel: string;
  siteCopyJson: string;
}

/* ── TypeScript interfaces ─────────────────────────────────── */

export interface OvuDayRobots {
  noindex: boolean;
  nofollow: boolean;
  noarchive: boolean;
  nosnippet: boolean;
  noimageindex: boolean;
}

export interface OvuDayOG {
  title: string | null;
  description: string | null;
  image: string | null;
  imageWidth: number | null;
  imageHeight: number | null;
  type: string;
  siteName: string;
  locale: string;
}

export interface OvuDayTwitter {
  card: string;
  title: string | null;
  description: string | null;
  image: string | null;
  site: string | null;
}

export interface OvuDaySchema {
  type: string;
  reviewedBy: string | null;
  authorName: string | null;
  customJson: string | null;
}

export interface OvuDaySitemap {
  exclude: boolean;
  priority: string;
  changefreq: string;
}

export interface OvuDayRedirect {
  url: string | null;
  type: number;
}

export interface OvuDayAnalysis {
  wordCount: number;
  keywordDensity: number;
  keywordInTitle: boolean;
  keywordInDesc: boolean;
  keywordInContent: boolean;
  keywordInH1: boolean;
  hasMetaDesc: boolean;
  hasOgImage: boolean;
  hasFeaturedImage: boolean;
  titleLength: number;
  descLength: number;
}

export interface OvuDaySeo {
  title: string;
  metaDescription: string | null;
  focusKeyword: string | null;
  canonical: string;
  robotsString: string;
  breadcrumbTitle: string | null;
  readingTime: number;
  seoScore: number;
  schemaJson: string | null;
  robots: OvuDayRobots;
  og: OvuDayOG;
  twitter: OvuDayTwitter;
  schema: OvuDaySchema;
  sitemap: OvuDaySitemap;
  redirect: OvuDayRedirect;
  analysis: OvuDayAnalysis;
}

export interface OvuDayGlobalSeo {
  siteName: string;
  titleSeparator: string;
  homeTitle: string;
  homeDescription: string;
  homeOgImage: string;
  organizationName: string;
  organizationLogo: string;
  twitterUsername: string;
  facebookUrl: string;
  instagramUrl: string;
  googleSiteVerify: string;
  bingSiteVerify: string;
  googleAnalytics: string;
  googleTagManager: string;
  noindexDate: boolean;
  noindexAuthor: boolean;
  noindexSearch: boolean;
  noindex404: boolean;
  breadcrumbsEnable: boolean;
  breadcrumbsHome: string;
  breadcrumbsSep: string;
  sitemapEnable: boolean;
  sitemapPosts: boolean;
  sitemapPages: boolean;
  sitemapCats: boolean;
  allowedOrigins: string;
}

export interface WPPost {
  id: string;
  databaseId: number;
  slug: string;
  title: string;
  content?: string;
  excerpt?: string;
  date: string;
  modified?: string;
  featuredImage?: {
    node: {
      sourceUrl: string;
      altText: string;
      mediaDetails?: {
        width?: number;
        height?: number;
      };
    };
  };
  categories?: { nodes: { name: string; slug: string }[] };
  tags?: { nodes: { name: string; slug: string }[] };
  author?: {
    node: {
      name: string;
      slug?: string;
      description?: string;
      avatar?: { url: string; width?: number; height?: number };
    };
  };
  ovudaySeo?: OvuDaySeo;
}
