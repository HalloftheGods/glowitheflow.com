export function calculateTrafficQuality(currentQuality: number, timeSinceLastClick: number): number {
  const isRapidClick = timeSinceLastClick < 300;
  const isFastClick = timeSinceLastClick < 600;
  
  if (isRapidClick) {
    return Math.max(0, currentQuality - 25);
  } else if (isFastClick) {
    return Math.max(10, currentQuality - 10);
  } else {
    const recoveryAmount = Math.min(20, (timeSinceLastClick - 600) / 100);
    return Math.min(100, currentQuality + recoveryAmount);
  }
}

export function calculateCPCValue(
  baseCPC: number,
  quality: number,
  travel: number,
  linkPrice: number,
  clickCount: number
): number {
  const qualityMultiplier = quality / 100;
  const travelBonus = Math.min(0.5, travel / 10000);
  const engagementMultiplier = 1.0 + travelBonus;
  const linkPriceMultiplier = 0.8 + (linkPrice / 50);
  
  let depthMultiplier = 1.0;
  const isEarlySession = clickCount < 20;
  const isLateSession = clickCount > 50;
  if (isEarlySession) {
    depthMultiplier = 0.6 + (clickCount / 20) * 0.4;
  } else if (isLateSession) {
    depthMultiplier = Math.max(0.3, 1.0 - (clickCount - 50) * 0.01);
  }
  
  const finalCPC = baseCPC * qualityMultiplier * engagementMultiplier * linkPriceMultiplier * depthMultiplier;
  return Math.max(0.01, Math.min(5.00, finalCPC));
}
