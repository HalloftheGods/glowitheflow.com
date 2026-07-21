import * as THREE from 'three';

export interface WaterThought {
  x: number;
  y: number;
  width: number;
  passengers: number;
}

export interface WaterRipple {
  x: number;
  y: number;
  radius: number;
  maxRadius: number;
  strength: number;
}

export class WaterEngine {
  private container: HTMLElement;
  private scene: THREE.Scene;
  private camera: THREE.PerspectiveCamera;
  private renderer: THREE.WebGLRenderer;
  private animationId?: number;
  private clock: THREE.Clock;
  private geometry: THREE.PlaneGeometry;
  private mesh: THREE.Mesh;
  private tempVec = new THREE.Vector3();
  private thoughts: WaterThought[] = [];
  private ripples: WaterRipple[] = [];
  private isDashboardOpen: boolean = false;
  private boundResizeHandler = () => this.onResize();

  constructor(container: HTMLElement) {
    this.container = container;
    this.clock = new THREE.Clock();

    // Scene setup
    this.scene = new THREE.Scene();
    this.scene.background = new THREE.Color(0x020510);
    this.scene.fog = new THREE.FogExp2(0x020510, 0.005); // Reduced density to keep water visible from y=150

    // Camera (Top-down view as requested, moved further back for less closeness)
    this.camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
    this.camera.position.set(0, 150, 0);
    this.camera.lookAt(0, 0, 0);

    this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    this.renderer.setSize(window.innerWidth, window.innerHeight);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5)); // Slightly reduced max pixel ratio for perf boost
    this.renderer.domElement.style.width = '100%';
    this.renderer.domElement.style.height = '100%';
    this.renderer.domElement.style.display = 'block';
    this.container.appendChild(this.renderer.domElement);

    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.4); // Stronger base visibility
    this.scene.add(ambientLight);

    const dirLight = new THREE.DirectionalLight(0x00e5ff, 2.5);
    dirLight.position.set(0, 150, 0); // Light straight down to cover everything evenly
    this.scene.add(dirLight);
    
    // Specular highlight light for bioluminescence
    const pointLight = new THREE.PointLight(0x00f3ff, 6, 1200); // Wider radius
    pointLight.position.set(0, 100, 0);
    this.scene.add(pointLight);

    // Water Surface using a slightly lower-density plane to boost FPS (100x100 is smooth but 60% fewer verts than 160x160)
    this.geometry = new THREE.PlaneGeometry(400, 400, 100, 100);
    this.geometry.rotateX(-Math.PI / 2);

    const material = new THREE.MeshStandardMaterial({
      color: 0x011322,
      emissive: 0x000510,
      roughness: 0.2,
      metalness: 0.8,
      flatShading: false,
    });

    this.mesh = new THREE.Mesh(this.geometry, material);
    this.scene.add(this.mesh);

    window.addEventListener('resize', this.boundResizeHandler);
  }

  public init() {
    this.animate();
  }

  public updateThoughts(thoughts: WaterThought[]) {
    this.thoughts = thoughts;
  }

  public updateRipples(ripples: WaterRipple[]) {
    this.ripples = ripples;
  }

  public setDashboardState(isOpen: boolean) {
    this.isDashboardOpen = isOpen;
  }

  private onResize() {
    this.camera.aspect = window.innerWidth / window.innerHeight;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(window.innerWidth, window.innerHeight);
  }

  private animate() {
    this.animationId = requestAnimationFrame(this.animate.bind(this));
    
    const time = this.clock.getElapsedTime();
    const position = this.geometry.attributes.position;
    
    const camPos = this.camera.position;
    const worldRatio = (2 * Math.tan(22.5 * Math.PI / 180) * camPos.y) / window.innerHeight;

    const thoughtWorldPos: {xMin: number, xMax: number, z: number, strength: number}[] = [];
    for (const t of this.thoughts) {
      const halfW = t.width / 2;
      this.tempVec.set(
        ((t.x - halfW) / window.innerWidth) * 2 - 1,
        -(t.y / window.innerHeight) * 2 + 1,
        0.5
      );
      this.tempVec.unproject(this.camera);
      this.tempVec.sub(camPos).normalize();
      const distLeft = -camPos.y / this.tempVec.y;
      const xMin = camPos.x + this.tempVec.x * distLeft;
      const z = camPos.z + this.tempVec.z * distLeft;

      this.tempVec.set(
        ((t.x + halfW) / window.innerWidth) * 2 - 1,
        -(t.y / window.innerHeight) * 2 + 1,
        0.5
      );
      this.tempVec.unproject(this.camera);
      this.tempVec.sub(camPos).normalize();
      const distRight = -camPos.y / this.tempVec.y;
      const xMax = camPos.x + this.tempVec.x * distRight;
      
      thoughtWorldPos.push({
        xMin,
        xMax,
        z,
        strength: t.passengers
      });
    }

    // Pre-calculate world positions for click ripples
    const rippleWorldPos: {x: number, z: number, worldRadius: number, worldMaxRadius: number, strength: number}[] = [];
    for (const r of this.ripples) {
      this.tempVec.set(
        (r.x / window.innerWidth) * 2 - 1,
        -(r.y / window.innerHeight) * 2 + 1,
        0.5
      );
      this.tempVec.unproject(this.camera);
      this.tempVec.sub(camPos).normalize();
      const distance = -camPos.y / this.tempVec.y;
      
      rippleWorldPos.push({
        x: camPos.x + this.tempVec.x * distance,
        z: camPos.z + this.tempVec.z * distance,
        worldRadius: r.radius * worldRatio,
        worldMaxRadius: r.maxRadius * worldRatio,
        strength: r.strength
      });
    }
    
    // Modify vertices to create a dynamic fluid effect
    for (let i = 0; i < position.count; i++) {
      const x = position.getX(i);
      const z = position.getZ(i);
      
      // Slower, denser base waves flowing in the direction of the thoughts (upwards, -Z)
      const flowTime = time * 0.5; // Halved for calmer water
      const wave1 = Math.sin(x * 0.1 + (z + flowTime * 5.0) * 0.12) * 1.5;
      const wave2 = Math.cos((z + flowTime * 4.0) * 0.08) * 2.0;
      const wave3 = Math.sin(x * 0.05 - (z + flowTime * 3.0) * 0.06) * 1.2;
      
      let y = wave1 + wave2 + wave3;
      
      // Create a gravity well / depression in the center beneath the UI
      const distFromCenterSq = x * x + z * z;
      const wellRadiusX = this.isDashboardOpen ? 35 : 12; // Adjusted to match screen proportions (world units)
      const wellRadiusZ = this.isDashboardOpen ? 25 : 12;
      const wellDepth = this.isDashboardOpen ? 12 : 8;
      
      const normalizedDist = Math.sqrt((x * x) / (wellRadiusX * wellRadiusX) + (z * z) / (wellRadiusZ * wellRadiusZ));
      
      if (normalizedDist < 1.0) {
        const depression = Math.cos(normalizedDist * (Math.PI / 2)); 
        const floatBob = this.isDashboardOpen ? Math.sin(time * 1.5) * 2.5 : 0;
        y -= (depression * wellDepth) + floatBob; // Sink the water in the center
      }

      for (const tw of thoughtWorldPos) {
        const closestX = Math.max(tw.xMin, Math.min(x, tw.xMax));
        const dx = x - closestX;
        const dz = z - tw.z;
        const distSq = dx * dx + dz * dz;
        
        const radius = 30 + tw.strength * 5;
        const rSq = radius * radius;
        const isWithinRange = distSq < rSq;
        if (isWithinRange) {
           const dist = Math.sqrt(distSq);
           const rippleAlpha = Math.cos((dist / radius) * (Math.PI / 2));
           const bulge = rippleAlpha * (5 + tw.strength * 1.0);
           const wave = Math.sin(dist * 0.5 - time * 3) * 2.0 * rippleAlpha;
           y += bulge + wave; 
        }
      }

      // Tie click ripples to 3D water
      for (const wr of rippleWorldPos) {
        const dx = x - wr.x;
        const dz = z - wr.z;
        const dist = Math.sqrt(dx * dx + dz * dz);
        
        const width = 12; // wave ring width in world units
        const distToRing = Math.abs(dist - wr.worldRadius);
        
        if (distToRing < width && wr.worldRadius < wr.worldMaxRadius) {
          const alpha = 1 - (wr.worldRadius / wr.worldMaxRadius);
          const force = (wr.strength * 0.3) * alpha * (1 - distToRing / width);
          const wave = Math.sin((dist - wr.worldRadius) * 0.5) * force;
          y += wave;
        }
      }
      
      position.setY(i, y);
    }
    
    // Update normals so the lights catch the ripples
    this.geometry.computeVertexNormals();
    position.needsUpdate = true;

    this.renderer.render(this.scene, this.camera);
  }

  public dispose() {
    if (this.animationId !== undefined) {
      cancelAnimationFrame(this.animationId);
    }
    window.removeEventListener('resize', this.boundResizeHandler);
    this.geometry.dispose();
    if (this.mesh.material instanceof THREE.Material) {
      this.mesh.material.dispose();
    }
    if (this.renderer.domElement && this.renderer.domElement.parentNode) {
      this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
    }
    this.renderer.dispose();
  }
}
