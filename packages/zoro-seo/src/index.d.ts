/**
 * Type declarations for zoro-seo
 */

export interface ZoroOptions {
  /** Website base URL, e.g. 'https://mysite.com' */
  siteUrl?: string;
  /** Minimum internal inlinks to qualify as a Pillar (default: 4) */
  pillarThreshold?: number;
  /** Top percentile for dynamic pillar calculation (default: 0.15) */
  topPercentRatio?: number;
}

export interface ZoroRawPost {
  id: number | string;
  title: string;
  url: string;
  content?: string;
  categories?: string[];
  category?: string;
  publishDate?: string;
  postType?: 'post' | 'page' | 'service' | 'product' | string;
}

export interface ZoroLink {
  raw_url: string;
  normalized_url: string;
  anchor_text: string;
  is_nofollow: boolean;
  rel: string;
}

export interface ZoroInlink {
  source_id: number | string;
  source_title: string;
  source_url: string;
  anchor_text: string;
  is_nofollow: boolean;
}

export type ZoroRole = 'pillar' | 'cluster' | 'orphan' | 'dead_end';

export interface ZoroAnalyzedPost {
  id: number | string;
  title: string;
  url: string;
  normalized_url: string;
  post_type: string;
  post_type_label: string;
  word_count: number;
  categories: string[];
  publish_date: string;
  outlinks: ZoroLink[];
  total_outlinks_count: number;
  inlinks: ZoroInlink[];
  inlinks_count: number;
  unique_referring_count: number;
  anchor_distribution: Record<string, number>;
  role: ZoroRole;
  role_label: string;
  role_class: string;
}

export interface ZoroCategoryItem {
  id: number | string;
  title: string;
  url: string;
  post_type: string;
  post_type_label: string;
  inlinks_count: number;
  outlinks_count: number;
  word_count: number;
  role: ZoroRole;
}

export interface ZoroCategoryGroup {
  name: string;
  posts_count: number;
  total_inlinks: number;
  total_outlinks: number;
  orphans_count: number;
  posts: ZoroCategoryItem[];
  recommended_pillar: ZoroCategoryItem | null;
}

export interface ZoroSummary {
  total_posts: number;
  total_internal_links: number;
  avg_links_per_post: number;
  orphans_count: number;
  dead_ends_count: number;
  pillars_count: number;
  clusters_count: number;
  categories_count: number;
  post_types: Record<string, number>;
  scan_time: string;
}

export interface ZoroMatrixItem {
  source_id: number | string;
  source_title: string;
  source_url: string;
  target_id: number | string;
  target_title: string;
  target_url: string;
  anchor_text: string;
  is_nofollow: boolean;
}

export interface ZoroReport {
  summary: ZoroSummary;
  posts: ZoroAnalyzedPost[];
  categories: Record<string, ZoroCategoryGroup>;
  matrix: ZoroMatrixItem[];
}

export class ZoroScanner {
  siteUrl: string;
  siteHost: string;
  constructor(siteUrl?: string);
  setUrlMap(map: Map<string, number | string> | Record<string, number | string>): void;
  normalizeUrl(url: string): string;
  isInternalUrl(url: string): boolean;
  scanPost(post: ZoroRawPost): ZoroAnalyzedPost;
  extractLinks(content: string, currentPageUrl?: string): ZoroLink[];
  calculateWordCount(content: string): number;
}

export class ZoroAnalyzer {
  constructor(options?: { pillarThreshold?: number; topPercentRatio?: number });
  analyze(posts: ZoroRawPost[] | ZoroAnalyzedPost[]): ZoroReport;
  updateSinglePost(existingPosts: ZoroAnalyzedPost[], updatedPost: ZoroAnalyzedPost): ZoroReport;
  removeSinglePost(existingPosts: ZoroAnalyzedPost[], postId: number | string): ZoroReport;
}

export class ZoroExporter {
  static UTF8_BOM: string;
  generatePostsSummaryCsv(posts: ZoroAnalyzedPost[]): string;
  generateLinksMatrixCsv(matrix: ZoroMatrixItem[]): string;
  generateTreeHtml(report: ZoroReport): string;
}

export class ZoroSeo {
  siteUrl: string;
  scanner: ZoroScanner;
  analyzer: ZoroAnalyzer;
  exporter: ZoroExporter;

  constructor(options?: ZoroOptions);
  scan(post: ZoroRawPost): ZoroAnalyzedPost;
  analyze(posts: ZoroRawPost[]): ZoroReport;
  updatePost(existingPosts: ZoroAnalyzedPost[], updatedPost: ZoroRawPost | ZoroAnalyzedPost): ZoroReport;
  removePost(existingPosts: ZoroAnalyzedPost[], postId: number | string): ZoroReport;
  exportPostsCsv(posts: ZoroAnalyzedPost[]): string;
  exportMatrixCsv(matrix: ZoroMatrixItem[]): string;
  exportTreeHtml(report: ZoroReport): string;
}

export default ZoroSeo;
