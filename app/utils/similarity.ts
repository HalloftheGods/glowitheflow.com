/**
 * Computes a similarity score between two strings (0.0 to 1.0).
 * Uses case-insensitive token overlap (Jaccard Index) combined with substring matching.
 */
export function getSimilarity(s1: string, s2: string): number {
  const clean = (str: string) =>
    str
      .toLowerCase()
      .replace(/[^\w\s]/g, '')
      .split(/\s+/)
      .filter(Boolean);

  const words1 = clean(s1);
  const words2 = clean(s2);

  if (words1.length === 0 || words2.length === 0) return 0;

  const str1 = words1.join(' ');
  const str2 = words2.join(' ');

  if (str1 === str2) return 1.0;

  // Substring checks
  if (str2.includes(str1) || str1.includes(str2)) {
    const ratio = Math.min(str1.length, str2.length) / Math.max(str1.length, str2.length);
    return 0.5 + ratio * 0.4;
  }

  const set1 = new Set(words1);
  const set2 = new Set(words2);

  const intersection = new Set([...set1].filter((x) => set2.has(x)));
  const union = new Set([...set1, ...set2]);

  return intersection.size / union.size;
}
