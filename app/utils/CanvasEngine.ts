import type { Ref } from 'vue';
import { getSimilarity } from './similarity';
import { WaterEngine } from './WaterEngine';
import { calculateTrafficQuality, calculateCPCValue } from './cpc';

export const FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Raleway', 'PT Sans', 
  'Merriweather', 'Nunito', 'Playfair Display', 'Ubuntu', 'Rubik', 'Lora', 'Work Sans', 
  'Fira Sans', 'Quicksand', 'Anton', 'Inconsolata', 'Pacifico', 'Dancing Script', 'Josefin Sans', 
  'Righteous', 'Lobster', 'Caveat', 'Indie Flower', 'Shadows Into Light', 'Amatic SC', 
  'Comfortaa', 'Kalam', 'Satisfy', 'Courgette', 'Great Vibes', 'Permanent Marker', 
  'Sacramento', 'Cookie', 'Tangerine', 'Parisienne', 'Bangers', '"Press Start 2P"', 
  '"Fredoka One"', '"Abril Fatface"', '"Carter One"', '"Patua One"', '"Alfa Slab One"', 
  '"Special Elite"', 'Monoton', 'Creepster', '"Black Ops One"', 'Syncopate', 'Audiowide', 
  'Orbitron', 'Exo', 'Rajdhani', 'VT323', '"Share Tech Mono"', 'Cinzel', '"Bebas Neue"', 
  '"Fjalla One"'
];

export const AUTOMATED_TEXT = "Jump aboard the train of thought and let your flow glow...";

export interface Particle {
  x: number;
  y: number;
  vx: number;
  vy: number;
  baseVx: number;
  baseVy: number;
  size: number;
  baseSize: number;
  hue: number;
  baseHue: number;
}

export interface Ripple {
  x: number;
  y: number;
  radius: number;
  maxRadius: number;
  speed: number;
  strength: number;
}

export interface ThoughtNode {
  id?: number;
  type?: string;
  link?: string;
  x: number;
  y: number;
  text: string;
  alpha: number;
  vy: number;
  font: string;
  fontSize: number;
  passengers: number;
  width: number;
}

export interface FloatingVal {
  x: number;
  y: number;
  text: string;
  alpha: number;
  vy: number;
  hue: number;
}

export interface CanvasEngineConfig {
  canvas: HTMLCanvasElement;
  container: HTMLElement;
  waterBg: HTMLElement;
  userStore: any;
  currentLinkPrice: Ref<number>;
  knownThoughtsList: Ref<{ text: string; count: number }[]>;
  uiState: Ref<'landing' | 'transitioning' | 'flow' | 'dashboard'>;
  trafficQuality: Ref<number>;
  cpc: Ref<number>;
  clickCount: Ref<number>;
  mouseTravelDistance: Ref<number>;
}

export class CanvasEngine {
  private canvas: HTMLCanvasElement;
  private ctx: CanvasRenderingContext2D;
  private container: HTMLElement;
  private waterBg: HTMLElement;
  private userStore: any;
  private currentLinkPrice: Ref<number>;
  private knownThoughtsList: Ref<{ text: string; count: number }[]>;
  private uiState: Ref<'landing' | 'transitioning' | 'flow' | 'dashboard'>;
  private trafficQuality: Ref<number>;
  private cpc: Ref<number>;
  private clickCount: Ref<number>;
  private mouseTravelDistance: Ref<number>;

  private thoughts: ThoughtNode[] = [];
  private ripples: Ripple[] = [];
  private particles: Particle[] = [];
  private floatingValues: FloatingVal[] = [];

  private animationFrameId = 0;
  private waterEngine: WaterEngine | null = null;
  private w = 0;
  private h = 0;
  private mouseX = -1000;
  private mouseY = -1000;
  private lastMouseX = -1;
  private lastMouseY = -1;
  private lastClickTime = 0;

  constructor(config: CanvasEngineConfig) {
    this.canvas = config.canvas;
    const context = this.canvas.getContext('2d');
    if (!context) {
      throw new Error('Could not get 2D context from canvas');
    }
    this.ctx = context;
    this.container = config.container;
    this.waterBg = config.waterBg;
    this.userStore = config.userStore;
    this.currentLinkPrice = config.currentLinkPrice;
    this.knownThoughtsList = config.knownThoughtsList;
    this.uiState = config.uiState;
    this.trafficQuality = config.trafficQuality;
    this.cpc = config.cpc;
    this.clickCount = config.clickCount;
    this.mouseTravelDistance = config.mouseTravelDistance;
  }

  public init() {
    this.w = this.canvas.width = window.innerWidth;
    this.h = this.canvas.height = window.innerHeight;

    this.waterEngine = new WaterEngine(this.waterBg);
    this.waterEngine.init();

    // Event listeners
    window.addEventListener('resize', this.handleResize);
    window.addEventListener('mousemove', this.handleMouseMove);
    document.addEventListener('mouseleave', this.handleMouseLeave);
    window.addEventListener('click', this.handleClick);
    window.addEventListener('touchstart', this.handleTouchStart, { passive: true });

    // Initialize particles
    const widthThreshold = window.innerWidth < 768;
    const maxParticles = widthThreshold ? 35 : 80;
    for (let i = 0; i < maxParticles; i++) {
      const vx = (Math.random() - 0.5) * 1.5;
      const vy = (Math.random() - 0.5) * 1.5;
      const size = Math.random() * 2 + 0.5;
      const hue = Math.random() * 40 + 170;
      this.particles.push({
        x: Math.random() * this.w,
        y: Math.random() * this.h,
        vx,
        vy,
        baseVx: vx,
        baseVy: vy,
        size,
        baseSize: size,
        hue,
        baseHue: hue
      });
    }

    // Start drawing loop
    this.draw();
  }

  public destroy() {
    if (this.waterEngine) {
      this.waterEngine.dispose();
    }
    window.removeEventListener('resize', this.handleResize);
    window.removeEventListener('mousemove', this.handleMouseMove);
    document.removeEventListener('mouseleave', this.handleMouseLeave);
    window.removeEventListener('click', this.handleClick);
    window.removeEventListener('touchstart', this.handleTouchStart);
    cancelAnimationFrame(this.animationFrameId);
  }

  private handleResize = () => {
    this.w = this.canvas.width = window.innerWidth;
    this.h = this.canvas.height = window.innerHeight;
  };

  private handleMouseMove = (e: MouseEvent) => {
    const hasValidCoords = e && typeof e.clientX === 'number' && typeof e.clientY === 'number';
    if (hasValidCoords) {
      this.mouseX = e.clientX;
      this.mouseY = e.clientY;
      const hasPreviousCoords = this.lastMouseX !== -1 && this.lastMouseY !== -1;
      if (hasPreviousCoords) {
        const dx = e.clientX - this.lastMouseX;
        const dy = e.clientY - this.lastMouseY;
        this.mouseTravelDistance.value += Math.sqrt(dx * dx + dy * dy);
      }
      this.lastMouseX = e.clientX;
      this.lastMouseY = e.clientY;
    }
  };

  private handleMouseLeave = () => {
    this.mouseX = -1000;
    this.mouseY = -1000;
    this.lastMouseX = -1;
    this.lastMouseY = -1;
  };

  private handleTouchStart = (e: TouchEvent) => {
    const hasTouch = e && e.touches && e.touches[0];
    if (hasTouch) {
      this.handleClick(e.touches[0] as unknown as MouseEvent);
    }
  };

  private handleClick = (e: MouseEvent) => {
    const isLanding = this.uiState.value === 'landing';
    const isFlow = this.uiState.value === 'flow' || this.uiState.value === 'dashboard';
    
    const clientX = e && typeof e.clientX === 'number' && !isNaN(e.clientX) ? e.clientX : this.w / 2;
    const clientY = e && typeof e.clientY === 'number' && !isNaN(e.clientY) ? e.clientY : this.h / 2;

    if (e && e.target) {
      const target = e.target as HTMLElement;
      const isInteractiveTarget = target.closest('button, input, textarea, a, .price-slider-card, .flow-composer-card, .water-panel');
      if (isInteractiveTarget) return;
    }

    if (isLanding) {
      const maxDim = Math.max(this.w, this.h);
      const targetMaxRadius = maxDim * 1.5;
      
      this.ripples.push({
        x: clientX,
        y: clientY,
        radius: 0,
        maxRadius: targetMaxRadius,
        speed: 20,
        strength: 50
      });
      
      this.uiState.value = 'transitioning';
      
      const transitionToFlow = () => {
        this.uiState.value = 'flow';
        this.dropAutomatedThought();
      };
      
      setTimeout(transitionToFlow, 800);
    } else if (isFlow) {
      let clickedThought: ThoughtNode | null = null;
      for (const t of this.thoughts) {
        const halfW = t.width / 2;
        const x1 = t.x - halfW;
        const x2 = t.x + halfW;
        const y1 = t.y - t.fontSize;
        const y2 = t.y + t.fontSize * 0.2;
        const isClickInside = clientX >= x1 && clientX <= x2 && clientY >= y1 && clientY <= y2;
        if (isClickInside) {
          clickedThought = t;
          break;
        }
      }

      if (clickedThought) {
        if (clickedThought.id) {
          const handleBoostSuccess = (res: any) => {
            const isBoostSuccessful = res && res.success;
            if (isBoostSuccessful) {
              const earnedDriplets = res.earned_driplets || 15;
              this.floatingValues.push({
                x: clientX,
                y: clientY,
                text: `+${earnedDriplets}💧 (Boosted)`,
                alpha: 1,
                vy: -1.5,
                hue: 190
              });
              if (clickedThought) {
                clickedThought.passengers = res.new_glow_score || (clickedThought.passengers + 1);
              }
            }
          };

          const handleBoostError = (err: any) => {
            console.error('Failed to boost post:', err);
          };

          this.userStore.boostPost(clickedThought.id)
            .then(handleBoostSuccess)
            .catch(handleBoostError);
        }

        const isDrop = clickedThought.type === 'drop' && typeof clickedThought.link === 'string' && clickedThought.link.trim().length > 0;
        if (isDrop && clickedThought.link) {
          let targetUrl = clickedThought.link.trim();
          const hasProtocol = targetUrl.startsWith('http://') || targetUrl.startsWith('https://');
          if (!hasProtocol) {
            targetUrl = `https://${targetUrl}`;
          }
          window.open(targetUrl, '_blank');
        }

        this.ripples.push({
          x: clientX,
          y: clientY,
          radius: 0,
          maxRadius: 300,
          speed: 15,
          strength: 20
        });

        // Click gamification: Clicked a thought
        this.handleInteractionClick(clientX, clientY);
      } else {
        this.ripples.push({
          x: clientX,
          y: clientY,
          radius: 0,
          maxRadius: 200,
          speed: 5,
          strength: 10
        });

        // Click gamification: Clicked background
        this.handleInteractionClick(clientX, clientY);
      }
    }
  };

  private calculateCPC() {
    const baseCPC = 0.10;
    const timeSinceLastClick = Date.now() - this.lastClickTime;
    
    this.trafficQuality.value = calculateTrafficQuality(this.trafficQuality.value, timeSinceLastClick);
    
    this.cpc.value = calculateCPCValue(
      baseCPC,
      this.trafficQuality.value,
      this.mouseTravelDistance.value,
      this.currentLinkPrice.value,
      this.clickCount.value
    );
    
    this.mouseTravelDistance.value = 0;
  }

  private handleInteractionClick(clientX: number, clientY: number) {
    this.clickCount.value++;
    this.calculateCPC();
    
    const earnedValue = this.cpc.value;
    const earnedDriplets = Math.round(earnedValue * 100);
    
    this.userStore.earnDriplets(earnedDriplets);
    
    const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage;
    if (isLocalStorageAvailable) {
      window.localStorage.setItem('glow_click_count', String(this.clickCount.value));
    }
    
    this.lastClickTime = Date.now();
    
    const isLowQuality = this.trafficQuality.value < 40;
    const textHue = isLowQuality ? 0 : 190;
    const displayedValue = earnedValue.toFixed(2);
    const displayedText = isLowQuality 
      ? `+$${displayedValue} (+${earnedDriplets}💧) [FRAUD DETECTED]` 
      : `+$${displayedValue} (+${earnedDriplets}💧)`;

    this.floatingValues.push({
      x: clientX,
      y: clientY,
      text: displayedText,
      alpha: 1,
      vy: -1.5,
      hue: textHue
    });
  }

  public clearThoughts() {
    this.thoughts.length = 0;
  }

  public setFeed(feedPosts: any[]) {
    this.clearThoughts();
    const handleSpawn = (post: any) => this.spawnThought(post);
    feedPosts.forEach(handleSpawn);
  }

  public spawnThought(post: any) {
    const query = post.content || post.link || '';
    const isDrop = post.type === 'drop';
    const displayVal = isDrop ? (post.link || query) : query;
    if (!displayVal) return;

    const voteCount = post.passenger_count || 1;

    const maxVal = Math.max(1, voteCount);
    const logVal = Math.log10(maxVal);
    const denominator = 1 + logVal * 0.8;
    const rawSpeedScale = 1 / denominator;
    const speedScale = Math.max(0.05, rawSpeedScale);

    const randomFont = FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter';
    const randomSize = 24 + Math.random() * 24;
    const randomVy = (-0.5 - Math.random() * 0.5) * speedScale;
    const estimatedWidth = displayVal.length * randomSize * 0.5;

    const safeBuffer = 40;
    const halfW = estimatedWidth / 2;
    const minX = halfW + safeBuffer;
    const maxX = this.w - halfW - safeBuffer;
    const randomX = minX < maxX ? Math.random() * (maxX - minX) + minX : this.w / 2;

    const randomYOffset = Math.random() * 300;

    this.thoughts.push({
      id: Number(post.id),
      type: post.type,
      link: post.link,
      x: randomX,
      y: this.h + 50 + randomYOffset,
      text: displayVal,
      alpha: 0,
      vy: randomVy,
      font: randomFont,
      fontSize: randomSize,
      passengers: voteCount,
      width: estimatedWidth
    });
  }

  public handleFlowSubmit(payload: any) {
    let type = 'thought';
    let content = '';
    let link: string | undefined;
    let response: any;

    const isPayloadString = typeof payload === 'string';
    if (isPayloadString) {
      content = payload;
    } else if (payload && typeof payload === 'object') {
      type = payload.type || 'thought';
      content = payload.content || '';
      link = payload.link;
      response = payload.response;
    }

    const query = content.trim();
    const isEmpty = !query;
    if (isEmpty) return;

    const normalizedQuery = query.toLowerCase();
    const findMatchingThought = (thought: any) => {
      const isMatch = thought && typeof thought.text === 'string' && thought.text.toLowerCase() === normalizedQuery;
      return isMatch;
    };
    const existingIndex = this.knownThoughtsList.value.findIndex(findMatchingThought);

    let voteCount = 1;
    const hasExisting = existingIndex >= 0;
    if (hasExisting) {
      const existing = this.knownThoughtsList.value[existingIndex];
      if (existing) {
        const currentCount = typeof existing.count === 'number' && !isNaN(existing.count) ? existing.count : 1;
        existing.count = currentCount + 1;
        voteCount = existing.count;
      }
    } else {
      this.knownThoughtsList.value.push({ text: query, count: 1 });
    }

    const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage;
    if (isLocalStorageAvailable) {
      window.localStorage.setItem('glow_known_thoughts', JSON.stringify(this.knownThoughtsList.value));
    }

    const isHighVolume = voteCount > 10;
    const scale = isHighVolume
      ? Math.min(2.5, 1 + Math.log10(voteCount) * 0.5)
      : 1 + (voteCount - 1) * 0.3;

    const maxVal = Math.max(1, voteCount);
    const logVal = Math.log10(maxVal);
    const denominator = 1 + logVal * 0.8;
    const rawSpeedScale = 1 / denominator;
    const speedScale = Math.max(0.05, rawSpeedScale);

    const randomFont = FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter';
    const randomSize = (24 + Math.random() * 24) * scale;
    const randomVy = (-0.5 - Math.random() * 0.5) * speedScale;
    const estimatedWidth = query.length * randomSize * 0.5;

    const safeBuffer = 40;
    const halfW = estimatedWidth / 2;
    const minX = halfW + safeBuffer;
    const maxX = this.w - halfW - safeBuffer;
    const randomX = minX < maxX ? Math.random() * (maxX - minX) + minX : this.w / 2;

    this.thoughts.push({
      id: response?.post_id ? Number(response.post_id) : undefined,
      type,
      link,
      x: randomX,
      y: this.h + 50,
      text: query,
      alpha: 0,
      vy: randomVy,
      font: randomFont,
      fontSize: randomSize,
      passengers: voteCount,
      width: estimatedWidth
    });
  }

  public dropAutomatedThought() {
    const normalizedAutoText = AUTOMATED_TEXT.toLowerCase();
    const findMatchingAutoThought = (thought: any) => {
      const isMatch = thought && typeof thought.text === 'string' && thought.text.toLowerCase() === normalizedAutoText;
      return isMatch;
    };
    const autoIndex = this.knownThoughtsList.value.findIndex(findMatchingAutoThought);

    const hasAutoIndex = autoIndex >= 0;
    const foundThought = hasAutoIndex ? this.knownThoughtsList.value[autoIndex] : null;
    const hasValidCount = foundThought && typeof foundThought.count === 'number' && !isNaN(foundThought.count);
    const voteCount = hasValidCount ? foundThought.count : 1;

    const maxVal = Math.max(1, voteCount);
    const logVal = Math.log10(maxVal);
    const denominator = 1 + logVal * 0.8;
    const rawSpeedScale = 1 / denominator;
    const speedScale = Math.max(0.05, rawSpeedScale);

    const randomScale = 1.0;
    const randomFont = FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter';
    const randomSize = (24 + Math.random() * 24) * randomScale;
    const randomVy = (-0.5 - Math.random() * 0.5) * speedScale;
    const estimatedWidth = AUTOMATED_TEXT.length * randomSize * 0.5;

    const safeBuffer = 40;
    const halfW = estimatedWidth / 2;
    const minX = halfW + safeBuffer;
    const maxX = this.w - halfW - safeBuffer;
    const randomX = minX < maxX ? Math.random() * (maxX - minX) + minX : this.w / 2;

    this.thoughts.push({
      x: randomX,
      y: this.h + 50,
      text: AUTOMATED_TEXT,
      alpha: 0,
      vy: randomVy,
      font: randomFont,
      fontSize: randomSize,
      passengers: voteCount,
      width: estimatedWidth
    });
  }

  public resetToLanding() {
    this.uiState.value = 'landing';
    this.ripples.push({
      x: this.w / 2,
      y: this.h / 2,
      radius: 0,
      maxRadius: this.w,
      speed: 15,
      strength: 10
    });
  }

  private mapToWaterThought = (t: ThoughtNode) => {
    return { x: t.x, y: t.y, width: t.width, passengers: t.passengers };
  };

  private mapToWaterRipple = (r: Ripple) => {
    return { x: r.x, y: r.y, radius: r.radius, maxRadius: r.maxRadius, strength: r.strength };
  };

  private draw = () => {
    this.ctx.clearRect(0, 0, this.w, this.h);

    const isDashboard = this.uiState.value === 'dashboard';
    if (this.waterEngine) {
      this.waterEngine.setDashboardState(isDashboard);
    }
    
    // Draw ripples
    for (let i = this.ripples.length - 1; i >= 0; i--) {
      const r = this.ripples[i]!;
      r.radius += r.speed;
      
      const alpha = 1 - (r.radius / r.maxRadius);
      const isRippleExpired = alpha <= 0;
      if (isRippleExpired) {
        this.ripples.splice(i, 1);
        continue;
      }
      
      this.ctx.beginPath();
      this.ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
      this.ctx.strokeStyle = `hsla(190, 100%, 60%, ${alpha * 0.3})`;
      this.ctx.lineWidth = 1.5 + (r.strength / 10);
      this.ctx.stroke();
    }
    
    // Draw particles
    for (let i = 0; i < this.particles.length; i++) {
      const p = this.particles[i]!;
      
      p.vx += (p.baseVx - p.vx) * 0.05;
      p.vy += (p.baseVy - p.vy) * 0.05;
      p.size += (p.baseSize - p.size) * 0.1;
      p.hue += (p.baseHue - p.hue) * 0.1;
      
      let pAlpha = 0.8;
      
      const mdx = p.x - this.mouseX;
      const mdy = p.y - this.mouseY;
      const mdist = Math.sqrt(mdx * mdx + mdy * mdy);
      const isCloseToMouse = mdist < 150;
      
      if (isCloseToMouse) {
        const force = (150 - mdist) / 150;
        const angle = Math.atan2(mdy, mdx);
        p.vx += Math.cos(angle) * force * 0.5;
        p.vy += Math.sin(angle) * force * 0.5;
        p.size = Math.max(p.size, p.baseSize + force * 1.5);
        p.hue = Math.min(220, p.hue + force * 20);
        pAlpha = 1;
      }

      const isFlowState = this.uiState.value === 'flow';
      if (isFlowState) {
        const cx = this.w / 2;
        const cy = this.h / 2;
        const cdx = p.x - cx;
        const cdy = (p.y - cy) * 3; 
        const cdistSq = cdx * cdx + cdy * cdy;
        const isNearCenter = cdistSq < 160000;
        
        if (isNearCenter) {
          const cdist = Math.sqrt(cdistSq);
          const force = (400 - cdist) / 400;
          const angle = Math.atan2(cdy / 3, cdx); 
          
          p.vx += Math.cos(angle) * force * 2.0;
          p.vy += Math.sin(angle) * force * 2.0;
          p.hue = Math.min(220, p.hue + force * 15);
        }
      } else if (isDashboard) {
        const cx = this.w / 2;
        const cy = this.h / 2;
        const cdx = p.x - cx;
        const cdy = p.y - cy; 
        const cdistSq = cdx * cdx + cdy * cdy;
        const isNearDashboard = cdistSq < 120000; // ~346px radius
        
        if (isNearDashboard) {
          const cdist = Math.sqrt(cdistSq);
          const force = (350 - cdist) / 350;
          const angle = Math.atan2(cdy, cdx); 
          
          p.vx += Math.cos(angle) * force * 4.0;
          p.vy += Math.sin(angle) * force * 4.0;
          p.hue = Math.min(260, p.hue + force * 40);
        }
      }

      for (const t of this.thoughts) {
        const halfW = t.width / 2;
        const x1 = t.x - halfW;
        const x2 = t.x + halfW;
        const targetY = t.y - t.fontSize / 3;
        
        const closestX = Math.max(x1, Math.min(p.x, x2));
        const tdx = p.x - closestX;
        const tdy = (p.y - targetY) * 2; 
        const tDistSq = tdx * tdx + tdy * tdy;
        const isNearThought = tDistSq < 40000;
        
        if (isNearThought) {
          const tDist = Math.sqrt(tDistSq);
          const force = (200 - tDist) / 200;
          const angle = Math.atan2(tdy / 2, tdx);
          
          p.vx += Math.cos(angle) * force * 1.5;
          p.vy += Math.sin(angle) * force * 1.5;
          p.hue = Math.min(250, p.hue + force * 30);
          pAlpha = Math.min(1, pAlpha + force);
        }
      }
      
      for (const r of this.ripples) {
        const dx = p.x - r.x;
        const dy = p.y - r.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const distToRing = Math.abs(dist - r.radius);
        const isNearRippleRing = distToRing < 40;
        
        if (isNearRippleRing) {
          const force = r.strength * (1 - r.radius / r.maxRadius) * (1 - distToRing / 40);
          const angle = Math.atan2(dy, dx);
          
          p.vx += Math.cos(angle) * force * 0.2;
          p.vy += Math.sin(angle) * force * 0.2;
          
          p.size = Math.max(p.size, p.baseSize + force * 0.8);
          p.hue = 220;
          pAlpha = 1;
        }
      }
      
      p.x += p.vx;
      p.y += p.vy;
      
      const isXOutOfBounds = p.x < 0 || p.x > this.w;
      if (isXOutOfBounds) { 
        p.vx *= -1; 
        p.baseVx *= -1; 
        p.x = Math.max(0, Math.min(this.w, p.x)); 
      }
      const isYOutOfBounds = p.y < 0 || p.y > this.h;
      if (isYOutOfBounds) { 
        p.vy *= -1; 
        p.baseVy *= -1; 
        p.y = Math.max(0, Math.min(this.h, p.y)); 
      }
      
      this.ctx.beginPath();
      this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      this.ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha})`;
      this.ctx.fill();
      
      const hasGlow = p.size > p.baseSize;
      if (hasGlow) {
        this.ctx.beginPath();
        this.ctx.arc(p.x, p.y, p.size * 2.5, 0, Math.PI * 2);
        this.ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha * 0.2})`;
        this.ctx.fill();
      }
      
      for (let j = i + 1; j < this.particles.length; j++) {
        const p2 = this.particles[j]!;
        const dx = p.x - p2.x;
        const dy = p.y - p2.y;
        const distSq = dx * dx + dy * dy;
        const isConnected = distSq < 14400;
        
        if (isConnected) {
          const dist = Math.sqrt(distSq);
          this.ctx.beginPath();
          this.ctx.moveTo(p.x, p.y);
          this.ctx.lineTo(p2.x, p2.y);
          this.ctx.strokeStyle = `hsla(${p.hue}, 100%, 60%, ${0.6 - dist / 200})`;
          this.ctx.lineWidth = 0.5;
          this.ctx.stroke();
        }
      }
    }

    this.ctx.textAlign = 'center';
    const animTime = Date.now() * 0.003;
    
    // Draw thoughts
    for (let i = this.thoughts.length - 1; i >= 0; i--) {
      const t = this.thoughts[i]!;
      t.y += t.vy;
      t.alpha = Math.min(1, t.alpha + 0.015);
      
      this.ctx.font = `300 ${t.fontSize}px ${t.font}, sans-serif`;
      const textWidth = this.ctx.measureText(t.text).width;
      t.width = textWidth;
      
      const safeBuffer = 40;
      const halfW = textWidth / 2;
      const minX = halfW + safeBuffer;
      const maxX = this.w - halfW - safeBuffer;
      
      if (minX > maxX) {
        t.x = this.w / 2;
      } else {
        t.x = Math.max(minX, Math.min(maxX, t.x));
      }
      
      const shift = Math.sin(animTime * 0.3 + i) * (textWidth * 0.5);
      const grad = this.ctx.createLinearGradient(
        t.x - textWidth / 2 + shift,
        t.y - t.fontSize * 0.3,
        t.x + textWidth / 2 + shift,
        t.y + t.fontSize * 0.3
      );
      
      grad.addColorStop(0, `rgba(255, 255, 255, ${t.alpha})`);
      grad.addColorStop(0.5, `rgba(0, 255, 255, ${t.alpha})`);
      grad.addColorStop(1, `rgba(37, 99, 235, ${t.alpha})`);
      
      this.ctx.fillStyle = grad;
      
      const hasPassengersGlow = t.passengers > 1;
      if (hasPassengersGlow) {
        this.ctx.shadowColor = 'rgba(0, 255, 255, 0.8)';
        this.ctx.shadowBlur = Math.min(50, t.passengers * 10);
      }

      let currentX = t.x - textWidth / 2;
      for (let j = 0; j < t.text.length; j++) {
        const char = t.text[j]!;
        const charWidth = this.ctx.measureText(char).width;
        const waveOffset = Math.sin(animTime * 1.5 + j * 0.25) * 4;
        this.ctx.fillText(char, currentX + charWidth / 2, t.y + waveOffset);
        currentX += charWidth;
      }
      
      if (hasPassengersGlow) {
        this.ctx.font = `600 ${Math.max(10, t.fontSize * 0.4)}px ${t.font}, sans-serif`;
        this.ctx.textAlign = 'left';
        this.ctx.fillStyle = `rgba(255, 255, 255, ${t.alpha * 0.9})`;
        const lastCharWaveOffset = Math.sin(animTime * 1.5 + (t.text.length - 1) * 0.25) * 4;
        this.ctx.fillText(`${t.passengers}`, t.x + (textWidth / 2) + 4, t.y - (t.fontSize * 0.4) + lastCharWaveOffset);
        this.ctx.textAlign = 'center';
      }
      
      this.ctx.shadowBlur = 0;
      
      const isThoughtOffScreen = t.y < -50;
      if (isThoughtOffScreen) {
        this.thoughts.splice(i, 1);
      }
    }
    
    // Draw CPC floating values
    for (let i = this.floatingValues.length - 1; i >= 0; i--) {
      const f = this.floatingValues[i]!;
      f.y += f.vy;
      f.alpha -= 0.015;
      
      const isExpired = f.alpha <= 0;
      if (isExpired) {
        this.floatingValues.splice(i, 1);
        continue;
      }
      
      this.ctx.font = 'bold 16px "Fira Code", monospace';
      this.ctx.textAlign = 'center';
      this.ctx.shadowColor = `hsla(${f.hue}, 100%, 60%, ${f.alpha})`;
      this.ctx.shadowBlur = 10;
      this.ctx.fillStyle = `hsla(${f.hue}, 100%, 60%, ${f.alpha})`;
      this.ctx.fillText(f.text, f.x, f.y);
    }
    this.ctx.shadowBlur = 0;

    if (this.waterEngine) {
      this.waterEngine.updateThoughts(this.thoughts.map(this.mapToWaterThought));
      this.waterEngine.updateRipples(this.ripples.map(this.mapToWaterRipple));
    }
    
    this.animationFrameId = requestAnimationFrame(this.draw);
  };
}
