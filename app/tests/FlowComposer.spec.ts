import { mount } from '@vue/test-utils';
import { createTestingPinia } from '@pinia/testing';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import FlowComposer from '../components/FlowComposer.vue';
import { useUserStore } from '../stores/user';

vi.stubGlobal('useGlowApi', vi.fn());

describe('FlowComposer Component Unit Tests', () => {
  let pinia: any;

  beforeEach(() => {
    pinia = createTestingPinia({
      createSpy: vi.fn,
      initialState: {
        user: {
          dropletBalance: 5,
          dripletBalance: 0,
          isAuthenticated: true,
        },
      },
    });
  });

  it('verifies initial component rendering', () => {
    const wrapper = mount(FlowComposer, {
      props: {
        knownThoughts: [],
        currentLinkPrice: 10,
      },
      global: {
        plugins: [pinia],
      },
    });

    const input = wrapper.find('input[type="text"]');
    expect(input.exists()).toBe(true);

    const button = wrapper.find('button');
    expect(button.exists()).toBe(true);
    expect(button.element.disabled).toBe(true); // Empty content disables button

    // Dropdown and textarea should not exist initially
    expect(wrapper.find('#tributary-select').exists()).toBe(false);
    expect(wrapper.find('#body-text').exists()).toBe(false);
  });

  it('verifies insufficient balance state gating and button disabled trigger', async () => {
    const wrapper = mount(FlowComposer, {
      props: {
        knownThoughts: [],
        currentLinkPrice: 10,
      },
      global: {
        plugins: [pinia],
      },
    });

    const input = wrapper.find('input[type="text"]');
    // Type a link
    await input.setValue('https://hallofthegods.com');

    // Category selection dropdown and optional body text area must display
    expect(wrapper.find('#tributary-select').exists()).toBe(true);
    expect(wrapper.find('#body-text').exists()).toBe(true);

    // Button should be disabled since droplet balance (5) is less than link price (10)
    const button = wrapper.find('button');
    expect(button.element.disabled).toBe(true);
    expect(wrapper.text()).toContain('Insufficient Droplets');
  });

  it('verifies sufficient balance allows button to be enabled', async () => {
    const wrapper = mount(FlowComposer, {
      props: {
        knownThoughts: [],
        currentLinkPrice: 10,
      },
      global: {
        plugins: [pinia],
      },
    });

    // Update store state directly to have sufficient balance
    const userStore = useUserStore();
    userStore.dropletBalance = 15;

    const input = wrapper.find('input[type="text"]');
    await input.setValue('https://hallofthegods.com');

    // Dropdown and textarea must display
    expect(wrapper.find('#tributary-select').exists()).toBe(true);
    expect(wrapper.find('#body-text').exists()).toBe(true);

    // Button should be enabled
    const button = wrapper.find('button');
    expect(button.element.disabled).toBe(false);
    expect(wrapper.text()).toContain('Add Link to Stream');
  });
});
