// favicon.svg から各サイズの PNG と、PNG を内包した favicon.ico を生成する。
// 実行: node scripts/gen-favicon.mjs
import sharp from "sharp";
import { readFile, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const publicDir = join(root, "public");
const svg = await readFile(join(publicDir, "favicon.svg"));

const pngAt = (size) =>
  sharp(svg, { density: 384 }).resize(size, size).png().toBuffer();

// PNG を 1 つの ICO ファイルにまとめる（各エントリは PNG データを内包）
function buildIco(images) {
  const count = images.length;
  const header = Buffer.alloc(6);
  header.writeUInt16LE(0, 0); // reserved
  header.writeUInt16LE(1, 2); // type: 1 = icon
  header.writeUInt16LE(count, 4);

  const dir = Buffer.alloc(16 * count);
  let offset = 6 + 16 * count;
  for (let i = 0; i < count; i++) {
    const { size, data } = images[i];
    const e = i * 16;
    dir.writeUInt8(size >= 256 ? 0 : size, e + 0); // width
    dir.writeUInt8(size >= 256 ? 0 : size, e + 1); // height
    dir.writeUInt8(0, e + 2); // palette
    dir.writeUInt8(0, e + 3); // reserved
    dir.writeUInt16LE(1, e + 4); // color planes
    dir.writeUInt16LE(32, e + 6); // bits per pixel
    dir.writeUInt32LE(data.length, e + 8); // size of data
    dir.writeUInt32LE(offset, e + 12); // offset
    offset += data.length;
  }

  return Buffer.concat([header, dir, ...images.map((x) => x.data)]);
}

// .ico 用（16/32/48）
const icoSizes = [16, 32, 48];
const icoImages = await Promise.all(
  icoSizes.map(async (size) => ({ size, data: await pngAt(size) })),
);
await writeFile(join(publicDir, "favicon.ico"), buildIco(icoImages));

// iOS ホーム画面用（180x180、背景はダークで塗りつぶし）
const apple = await sharp(svg, { density: 384 })
  .resize(180, 180)
  .flatten({ background: "#0a0e1a" })
  .png()
  .toBuffer();
await writeFile(join(publicDir, "apple-touch-icon.png"), apple);

// OGP / 汎用 PNG（512x512）
await writeFile(join(publicDir, "favicon-512.png"), await pngAt(512));

console.log("generated: favicon.ico, apple-touch-icon.png, favicon-512.png");
