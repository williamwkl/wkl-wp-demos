// Renders one bag packshot per coffee (SVG illustration, our own artwork) to 800x800 WebP in theme/assets/img.
// usage: node demos/store/art/packshots.mjs
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
const coffees = [
  ['house-blend-bag', 'Ashgrove', 'House Blend', 'Medium roast', '#8E2F3F', '#F2C9A0'],
  ['morning-fog-bag', 'Morning', 'Fog', 'Colombia, washed', '#5E7F8C', '#E3EEF0'],
  ['summit-bag', 'Summit', '', 'Ethiopia, natural', '#C27A2C', '#FBE7C8'],
  ['hollow-oak-bag', 'Hollow', 'Oak', 'Espresso', '#3A2A20', '#E7D3BC'],
  ['night-market-bag', 'Night', 'Market', 'Dark roast', '#1F1813', '#C9B79C'],
  ['ember-decaf-bag', 'Ember', 'Decaf', 'Sugarcane decaf', '#3B5A3E', '#DCEAD9'],
];
const svg = ([, l1, l2, sub, c, tint]) => `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
<rect width="800" height="800" fill="${tint}"/>
<ellipse cx="400" cy="700" rx="230" ry="26" fill="#1F1813" opacity=".14"/>
<path d="M240 170 L560 170 L590 690 Q400 712 210 690 Z" fill="#C9A77A"/>
<path d="M240 170 L560 170 L566 214 L234 214 Z" fill="#B38F62"/>
<path d="M234 214 L566 214" stroke="#9C7A50" stroke-width="3"/>
<g stroke="#9C7A50" stroke-width="2" opacity=".5"><path d="M300 230 L285 680"/><path d="M500 230 L515 680"/></g>
<rect x="268" y="300" width="264" height="300" rx="10" fill="${c}"/>
<rect x="280" y="312" width="240" height="276" rx="6" fill="none" stroke="#F7F1E6" stroke-opacity=".55" stroke-width="2"/>
<g transform="translate(385 334)"><path d="M15 3c-1 4-5 6-5 11a5 5 0 0 0 10 0c0-5-4-7-5-11Z" fill="none" stroke="#F7F1E6" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 25h12M11 28h8" stroke="#F2C9A0" stroke-width="1.8" stroke-linecap="round"/></g>
<text x="400" y="${l2 ? 430 : 460}" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-size="46" fill="#F7F1E6">${l1}</text>
${l2 ? `<text x="400" y="482" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-style="italic" font-size="46" fill="#F7F1E6">${l2}</text>` : ''}
<text x="400" y="536" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="17" letter-spacing="2" fill="#F7F1E6" opacity=".9">${sub.toUpperCase()}</text>
<text x="400" y="570" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="14" letter-spacing="1.5" fill="#F7F1E6" opacity=".75">LANTERN HILL ROASTERS</text>
<rect x="300" y="626" width="200" height="34" rx="4" fill="#F7F1E6" opacity=".85"/>
<text x="400" y="649" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="15" fill="#3A2A20">ROASTED THU, OCT 29</text>
</svg>`;
const out = path.resolve('demos/store/theme/assets/img');
const tmp = mkdtempSync(path.join(tmpdir(), 'pack-'));
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 800, height: 800 } });
for (const c of coffees) {
  await p.setContent(`<body style="margin:0">${svg(c)}</body>`);
  const png = path.join(tmp, c[0] + '.png');
  await p.screenshot({ path: png, clip: { x: 0, y: 0, width: 800, height: 800 } });
  execFileSync('cwebp', ['-quiet', '-q', '78', png, '-o', path.join(out, c[0] + '-800.webp')]);
}
await b.close(); console.log('packshots', coffees.length);
