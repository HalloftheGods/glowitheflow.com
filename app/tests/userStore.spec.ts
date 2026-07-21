import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useUserStore } from '../stores/user';

const mockGlowApi = vi.fn();

vi.mock('../composables/useGlowApi', () => {
  return {
    useGlowApi: vi.fn((request?: string, opts?: any) => {
      if (typeof request === 'string') {
        if (opts !== undefined) {
          return mockGlowApi(request, opts);
        }
        return mockGlowApi(request);
      }
      return mockGlowApi;
    })
  };
});

describe('User Store Unit Tests', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    mockGlowApi.mockReset();

    const localStore: Record<string, string> = {};
    const mockLocalStorage = {
      getItem: vi.fn((key: string) => localStore[key] || null),
      setItem: vi.fn((key: string, value: string) => {
        localStore[key] = value;
      }),
      removeItem: vi.fn((key: string) => {
        delete localStore[key];
      }),
      clear: vi.fn(() => {
        for (const k in localStore) {
          delete localStore[k];
        }
      }),
      length: 0,
      key: vi.fn((index: number) => Object.keys(localStore)[index] || null),
    };

    if (typeof window !== 'undefined') {
      Object.defineProperty(window, 'localStorage', {
        value: mockLocalStorage,
        writable: true,
        configurable: true,
      });
    } else {
      (global as any).window = { localStorage: mockLocalStorage } as any;
      (global as any).localStorage = mockLocalStorage;
    }
  });

  it('verifies store initialization with defaults', () => {
    const store = useUserStore();
    expect(store.isAuthenticated).toBe(false);
    expect(store.username).toBe('');
    expect(store.dropletBalance).toBe(0);
    expect(store.dripletBalance).toBe(0);
    expect(store.depthMultiplier).toBe(1.0);
    expect(store.lifetimeValue).toBe(0.0);
  });

  it('verifies computed balance formatting', () => {
    const store = useUserStore();
    
    // Check default formatting
    expect(store.formattedBalance).toBe('0.00 💧');

    // Set balances directly
    store.dropletBalance = 5;
    store.dripletBalance = 0;
    expect(store.formattedBalance).toBe('5.00 💧');

    store.dropletBalance = 10;
    store.dripletBalance = 15;
    expect(store.formattedBalance).toBe('10.15 💧');
  });

  it('verifies fetchUser updates the store state', async () => {
    const store = useUserStore();
    const mockUserResponse = {
      username: 'test_user',
      droplet_balance: 25,
      driplet_balance: 50,
      depth_multiplier: 1.25,
    };
    mockGlowApi.mockResolvedValueOnce(mockUserResponse);

    await store.fetchUser();

    expect(mockGlowApi).toHaveBeenCalledWith('/glow/v1/user');
    expect(store.isAuthenticated).toBe(true);
    expect(store.username).toBe('test_user');
    expect(store.dropletBalance).toBe(25);
    expect(store.dripletBalance).toBe(50);
    expect(store.depthMultiplier).toBe(1.25);
    expect(store.lifetimeValue).toBe(25.5); // 25 + 50/100
  });

  it('verifies submitPost updates droplet and driplet balances', async () => {
    const store = useUserStore();
    store.dropletBalance = 50;
    store.dripletBalance = 0;

    const mockSubmitResponse = {
      success: true,
      post_id: 101,
      balance: {
        user_id: 1,
        droplet_balance: 40,
        driplet_balance: 0,
        depth_multiplier: 1.0,
      },
    };
    mockGlowApi.mockResolvedValueOnce(mockSubmitResponse);

    const extra = { link: 'https://example.com', tributary: 't/dev' };
    const res = await store.submitPost('drop', 'check this out', extra);

    expect(mockGlowApi).toHaveBeenCalledWith('/glow/v1/submit', {
      method: 'POST',
      body: {
        type: 'drop',
        content: 'check this out',
        link: 'https://example.com',
        tributary: 't/dev',
      },
    });
    expect(res.success).toBe(true);
    expect(store.dropletBalance).toBe(40);
    expect(store.dripletBalance).toBe(0);
  });

  it('verifies boostPost updates rewards and depth multiplier', async () => {
    const store = useUserStore();
    store.dropletBalance = 10;
    store.dripletBalance = 10;
    store.lifetimeValue = 10.10;

    const mockBoostResponse = {
      success: true,
      earned_driplets: 15,
      user_balance: {
        user_id: 1,
        droplet_balance: 10,
        driplet_balance: 25,
        depth_multiplier: 1.1,
      },
      new_glow_score: 5,
    };
    mockGlowApi.mockResolvedValueOnce(mockBoostResponse);

    const res = await store.boostPost(42);

    expect(mockGlowApi).toHaveBeenCalledWith('/glow/v1/boost', {
      method: 'POST',
      body: {
        post_id: 42,
      },
    });
    expect(res.success).toBe(true);
    expect(store.dropletBalance).toBe(10);
    expect(store.dripletBalance).toBe(25);
    expect(store.depthMultiplier).toBe(1.1);
    expect(store.lifetimeValue).toBeCloseTo(10.25, 2); // 10.10 + 15/100
  });

  it('verifies earnDriplets updates balance, handles rollover, updates lifetimeValue, and syncs to localStorage', () => {
    const store = useUserStore();
    store.dropletBalance = 5;
    store.dripletBalance = 40;
    store.lifetimeValue = 5.40;

    store.earnDriplets(25);

    expect(store.dropletBalance).toBe(5);
    expect(store.dripletBalance).toBe(65);
    expect(store.lifetimeValue).toBeCloseTo(5.65, 2);
    expect(window.localStorage.setItem).toHaveBeenCalledWith('glow_driplet_balance', '65');
    expect(window.localStorage.setItem).toHaveBeenCalledWith('glow_droplet_balance', '5');
    expect(window.localStorage.setItem).toHaveBeenCalledWith('glow_lifetime_value', '5.6500');

    // Trigger rollover
    store.earnDriplets(50);

    expect(store.dropletBalance).toBe(6);
    expect(store.dripletBalance).toBe(15);
    expect(store.lifetimeValue).toBeCloseTo(6.15, 2);
    expect(window.localStorage.setItem).toHaveBeenCalledWith('glow_driplet_balance', '15');
    expect(window.localStorage.setItem).toHaveBeenCalledWith('glow_droplet_balance', '6');
    expect(window.localStorage.setItem).toHaveBeenCalledWith('glow_lifetime_value', '6.1500');
  });
});
