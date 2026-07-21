export function useGlowApi(request: string, opts?: any): Promise<any>;
export function useGlowApi(): (urlStr: string, fetchOpts?: any) => Promise<any>;
export function useGlowApi(request?: string, opts?: any) {
  const config = useRuntimeConfig();
  const baseUrl = config.public?.apiBaseUrl || '';
  const nonce = typeof window !== 'undefined' ? (window as any).wpApiSettings?.nonce : undefined;

  const fetcher = (urlStr: string, fetchOpts?: any) => {
    const headers: Record<string, string> = { ...fetchOpts?.headers };
    if (nonce) {
      headers['X-WP-Nonce'] = nonce;
    }
    const finalUrl = urlStr.startsWith('http') ? urlStr : `${baseUrl}${urlStr}`;
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
