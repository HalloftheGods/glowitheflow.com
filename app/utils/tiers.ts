export function getNetworkTier(lifetimeValue: number): string {
  if (lifetimeValue < 5.0) return 'Dew Drop';
  if (lifetimeValue < 20.0) return 'Trickle';
  if (lifetimeValue < 50.0) return 'Stream';
  if (lifetimeValue < 150.0) return 'River';
  return 'Ocean';
}
