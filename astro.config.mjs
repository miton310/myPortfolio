// @ts-check
import { defineConfig } from 'astro/config';

import sitemap from '@astrojs/sitemap';

// https://astro.build/config
export default defineConfig({
  // 本番サイトのURL。サイトマップやcanonicalの絶対URL生成に使われる。
  site: 'https://dokkoi.jp',
  integrations: [
    sitemap({
      // mail.php は検索結果に出す必要がないため除外
      filter: (page) => !page.includes('/mail.php'),
    }),
  ],
});
