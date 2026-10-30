// The one place that decides which public base URL a build uses.
// The github.io project site; there is no custom domain and no CNAME.
import { readFileSync } from 'node:fs';
export const CONFIG = JSON.parse(readFileSync(new URL('../site.config.json', import.meta.url), 'utf8'));
export const BASE = CONFIG.base.replace(/\/$/, '');
