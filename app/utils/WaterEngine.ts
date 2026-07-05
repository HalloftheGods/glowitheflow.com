import * as THREE from 'three';

export interface WaterThought {
  x: number;
  y: number;
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

    window.addEventListener('resize', this.onResize.bind(this));
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

    // Pre-calculate world positions for thoughts using re-usable tempVec
    const thoughtWorldPos: {x: number, z: number, strength: number}[] = [];
    for (const t of this.thoughts) {
      this.tempVec.set(
        (t.x / window.innerWidth) * 2 - 1,
        -(t.y / window.innerHeight) * 2 + 1,
        0.5
      );
      this.tempVec.unproject(this.camera);
      this.tempVec.sub(camPos).normalize();
      const distance = -camPos.y / this.tempVec.y;
      
      thoughtWorldPos.push({
        x: camPos.x + this.tempVec.x * distance,
        z: camPos.z + this.tempVec.z * distance,
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
      const flowTime = time * 1.0;
      const wave1 = Math.sin(x * 0.1 + (z + flowTime * 5.0) * 0.12) * 1.5;
      const wave2 = Math.cos((z + flowTime * 4.0) * 0.08) * 2.0;
      const wave3 = Math.sin(x * 0.05 - (z + flowTime * 3.0) * 0.06) * 1.2;
      
      let y = wave1 + wave2 + wave3;
      
      // Create a gravity well / depression in the center beneath the FlowComposer
      const distFromCenterSq = x * x + z * z;
      if (distFromCenterSq < 2500) { // 50 * 50
        const distFromCenter = Math.sqrt(distFromCenterSq);
        const depression = Math.cos((distFromCenter / 50) * (Math.PI / 2)); 
        y -= depression * 15; // Sink the water in the center
      }

      // Tie water ripples directly to flowing text (thoughts)
      for (const tw of thoughtWorldPos) {
        const dx = x - tw.x;
        const dz = z - tw.z;
        const distSq = dx * dx + dz * dz;
        
        const radius = 30 + tw.strength * 5;
        const rSq = radius * radius;
        if (distSq < rSq) {
           const dist = Math.sqrt(distSq);
           const rippleAlpha = Math.cos((dist / radius) * (Math.PI / 2));
           // Create a bulge (pushing up) and expanding wake ripples
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
    window.removeEventListener('resize', this.onResize.bind(this));
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
