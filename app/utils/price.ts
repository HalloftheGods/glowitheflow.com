export function calculateAdjustedPrice(currentPrice: number, vote: number): number {
  return Math.round((currentPrice * 9 + vote) / 10);
}
