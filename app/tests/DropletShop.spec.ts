import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import DropletShop from '../components/DropletShop.vue';

const mockGlowApi = vi.fn();

vi.mock('../composables/useGlowApi', () => {
  const glowApiHandler = (request?: string, opts?: any) => {
    const isRequestString = typeof request === 'string';
    if (isRequestString) {
      const hasOptions = opts !== undefined;
      if (hasOptions) {
        return mockGlowApi(request, opts);
      }
      return mockGlowApi(request);
    }
    return mockGlowApi;
  };
  return {
    useGlowApi: vi.fn(glowApiHandler)
  };
});

describe('DropletShop Component Unit Tests', () => {
  const originalLocation = window.location;

  beforeEach(() => {
    mockGlowApi.mockReset();

    // Mock window.location
    delete (window as any).location;
    window.location = {
      origin: 'http://localhost:3000',
      href: '',
    } as any;
  });

  afterEach(() => {
    window.location = originalLocation;
  });

  it('renders correctly with packages and inputs', () => {
    const wrapper = mount(DropletShop);

    // Verify pack details
    expect(wrapper.text()).toContain('Starter Pack');
    expect(wrapper.text()).toContain('Pro Pack');
    expect(wrapper.text()).toContain('Whale Pack');

    // Coupon input should exist
    const couponInput = wrapper.find('#coupon-input');
    expect(couponInput.exists()).toBe(true);

    // Checkout button should exist
    const button = wrapper.find('button');
    expect(button.exists()).toBe(true);
    expect(button.text()).toBe('Proceed to Checkout');
  });

  it('updates selected package state on click', async () => {
    const wrapper = mount(DropletShop);

    // Find all package cards (they are the elements with .cursor-pointer)
    const packCards = wrapper.findAll('.cursor-pointer');
    expect(packCards.length).toBe(3);

    // Default selected should be Starter Pack (highlighted with border-cyan-400)
    expect(packCards[0].classes()).toContain('border-cyan-400');
    expect(packCards[1].classes()).not.toContain('border-cyan-400');

    // Click Pro Pack
    await packCards[1].trigger('click');
    expect(packCards[0].classes()).not.toContain('border-cyan-400');
    expect(packCards[1].classes()).toContain('border-cyan-400');

    // Click Whale Pack
    await packCards[2].trigger('click');
    expect(packCards[1].classes()).not.toContain('border-cyan-400');
    expect(packCards[2].classes()).toContain('border-cyan-400');
  });

  it('updates coupon text input', async () => {
    const wrapper = mount(DropletShop);
    const couponInput = wrapper.find('#coupon-input');

    await couponInput.setValue('save20');
    expect((couponInput.element as HTMLInputElement).value).toBe('save20');
  });

  it('triggers useGlowApi with correct parameters on checkout', async () => {
    const wrapper = mount(DropletShop);

    // Mock API success response
    mockGlowApi.mockResolvedValueOnce({
      checkout_url: 'https://checkout.stripe.com/pay/cs_test_123',
    });

    // Select Pro Pack
    const packCards = wrapper.findAll('.cursor-pointer');
    await packCards[1].trigger('click'); // Pro Pack

    // Set coupon
    const couponInput = wrapper.find('#coupon-input');
    await couponInput.setValue('  myfreedrips  '); // Should trim and uppercase to MYFREEDRIPS

    // Click checkout button
    const button = wrapper.find('button');
    await button.trigger('click');

    expect(mockGlowApi).toHaveBeenCalledWith('/glow/v1/stripe/checkout', {
      method: 'POST',
      body: {
        pack_id: 'pro',
        coupon: 'MYFREEDRIPS',
        success_url: 'http://localhost:3000/?success=true',
        cancel_url: 'http://localhost:3000/?cancel=true',
      },
    });

    expect(window.location.href).toBe('https://checkout.stripe.com/pay/cs_test_123');
  });

  it('displays user-friendly error message on failed checkout', async () => {
    const wrapper = mount(DropletShop);

    // Mock API failure response
    const apiError = new Error('Invalid coupon code');
    (apiError as any).data = { message: 'Invalid coupon code' };
    mockGlowApi.mockRejectedValueOnce(apiError);

    // Click checkout button
    const button = wrapper.find('button');
    await button.trigger('click');

    // Wait for async operations to complete
    await vi.dynamicImportSettled();

    // Verify error is rendered
    expect(wrapper.text()).toContain('Checkout error: Invalid coupon code');
  });
});
