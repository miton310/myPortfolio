import { defineCollection, z } from "astro:content";
import { glob } from "astro/loaders";

const works = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/works" }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    category: z.string(),
    tags: z.array(z.string()).default([]),
    date: z.coerce.date(),
    url: z.string().url().optional(),
    featured: z.boolean().default(false),
  }),
});

const blog = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/blog" }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    category: z.enum(["技術記事", "学習ログ", "運用メモ"]),
    date: z.coerce.date(),
    tags: z.array(z.string()).default([]),
  }),
});

// 自分で運用しているサイト（自社メディア・サービス・継続運用案件など）
const sites = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sites" }),
  schema: z.object({
    title: z.string(),
    url: z.string().url(),
    description: z.string(),
    category: z.string(),
    role: z.string().default("企画・開発・運用"),
    status: z.enum(["運用中", "リニューアル中", "公開停止"]).default("運用中"),
    tags: z.array(z.string()).default([]),
    since: z.coerce.date().optional(),
    order: z.number().default(0),
  }),
});

export const collections = { works, blog, sites };
