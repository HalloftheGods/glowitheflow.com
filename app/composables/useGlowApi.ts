export function useGlowApi(request: string, opts?: any): Promise<any>;
export function useGlowApi(): (urlStr: string, fetchOpts?: any) => Promise<any>;
export function useGlowApi(request?: string, opts?: any) {
  const config = useRuntimeConfig();
  const wpSettings = typeof window !== 'undefined' ? (window as any).wpApiSettings : undefined;

  let rawBase = wpSettings?.root || config.public?.apiBaseUrl || '/wp-json';
  if (rawBase.endsWith('/')) {
    rawBase = rawBase.slice(0, -1);
  }

  const nonce = wpSettings?.nonce;

  const fetcher = (urlStr: string, fetchOpts?: any) => {
    const headers: Record<string, string> = { ...fetchOpts?.headers };
    if (nonce) {
      headers['X-WP-Nonce'] = nonce;
    }

    let finalUrl: string;
    if (urlStr.startsWith('http://') || urlStr.startsWith('https://')) {
      finalUrl = urlStr;
    } else {
      const cleanPath = urlStr.startsWith('/') ? urlStr : `/${urlStr}`;
      finalUrl = `${rawBase}${cleanPath}`;
    }

    return $fetch(finalUrl, {
      credentials: 'include',
      ...fetchOpts,
      headers,
    });
  };

  if (typeof request === 'string') {
    return fetcher(request, opts);
  }

  return fetcher;
}
