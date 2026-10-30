// The one place that decides which public base URL a build uses.
// domainLive=false: currentBase (github.io project site), no CNAME. domainLive=true: publicBase + CNAME.
import { readFileSync } from 'node:fs';
export const CONFIG = JSON.parse(readFileSync(new URL('../site.config.json', import.meta.url), 'utf8'));
export const BASE = (CONFIG.domainLive ? CONFIG.publicBase : CONFIG.currentBase).replace(/\/$/, '');
