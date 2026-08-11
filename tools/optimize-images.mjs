/**
 * One-shot image optimizer for theme marketing assets.
 * Recompresses WebP/PNG and writes responsive -480w / -720w variants for heroes.
 */
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const imagesDir = path.resolve(__dirname, '../assets/images');
const logoDir = path.join(imagesDir, 'logo');

const HERO_BASES = [
	'sf-helmet-category.webp',
	'sf-safety-vest.webp',
	'sf-category-shoe.webp',
];

async function sizeOf(file) {
	try {
		const s = await fs.stat(file);
		return s.size;
	} catch {
		return 0;
	}
}

async function writeAtomic(file, buf) {
	const tmp = `${file}.${process.pid}.tmp`;
	await fs.writeFile(tmp, buf);
	await fs.copyFile(tmp, file);
	await fs.unlink(tmp).catch(() => {});
}

async function recompressWebp(file, { width = null, quality = 72 } = {}) {
	const before = await sizeOf(file);
	const input = await fs.readFile(file);
	let pipeline = sharp(input).rotate();
	if (width) {
		pipeline = pipeline.resize({ width, withoutEnlargement: true });
	}
	const buf = await pipeline.webp({ quality, effort: 6, smartSubsample: true }).toBuffer();
	// Keep original if recompress somehow grew.
	if (buf.length >= before && !width) {
		return { before, after: before, skipped: true };
	}
	await writeAtomic(file, buf);
	return { before, after: buf.length, skipped: false };
}

async function writeVariant(srcFile, outFile, width, quality = 70) {
	const input = await fs.readFile(srcFile);
	const buf = await sharp(input)
		.rotate()
		.resize({ width, withoutEnlargement: true })
		.webp({ quality, effort: 6, smartSubsample: true })
		.toBuffer();
	await writeAtomic(outFile, buf);
	return buf.length;
}

async function main() {
	const entries = await fs.readdir(imagesDir);
	const webps = entries.filter((n) => n.toLowerCase().endsWith('.webp'));

	console.log('Recompressing theme WebP assets…');
	for (const name of webps) {
		const file = path.join(imagesDir, name);
		const r = await recompressWebp(file, { quality: 72 });
		console.log(
			`${name}: ${(r.before / 1024).toFixed(1)}KB → ${(r.after / 1024).toFixed(1)}KB${r.skipped ? ' (kept)' : ''}`
		);
	}

	console.log('\nWriting hero responsive variants…');
	for (const name of HERO_BASES) {
		const src = path.join(imagesDir, name);
		const base = name.replace(/\.webp$/i, '');
		for (const w of [480, 720]) {
			const out = path.join(imagesDir, `${base}-${w}w.webp`);
			const bytes = await writeVariant(src, out, w, 70);
			console.log(`${path.basename(out)}: ${(bytes / 1024).toFixed(1)}KB`);
		}
	}

	const logoPng = path.join(logoDir, 'safe-store-bd.png');
	const logoWebp = path.join(logoDir, 'safe-store-bd.webp');
	if (await sizeOf(logoPng)) {
		const logoInput = await fs.readFile(logoPng);
		const buf = await sharp(logoInput)
			.resize({ width: 480, withoutEnlargement: true })
			.webp({ quality: 80, effort: 6 })
			.toBuffer();
		await writeAtomic(logoWebp, buf);
		console.log(`\nlogo webp: ${(buf.length / 1024).toFixed(1)}KB`);
	}
}

main().catch((err) => {
	console.error(err);
	process.exit(1);
});
