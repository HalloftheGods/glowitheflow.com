import * as THREE from 'three';

export class RockBottomEngine {
  private container: HTMLElement;
  private scene: THREE.Scene;
  private camera: THREE.PerspectiveCamera;
  private renderer: THREE.WebGLRenderer;
  private animationId?: number;
  private nodes: THREE.Mesh[] = [];

  constructor(container: HTMLElement) {
    this.container = container;

    // 1. Setup Scene
    this.scene = new THREE.Scene();
    this.scene.fog = new THREE.FogExp2(0x050510, 0.002);

    // 2. Setup Camera
    this.camera = new THREE.PerspectiveCamera(
      60,
      window.innerWidth / window.innerHeight,
      0.1,
      1000
    );
    // Start camera above looking down into the "bottom"
    this.camera.position.set(0, 50, 100);
    this.camera.lookAt(0, 0, 0);

    // 3. Setup Renderer
    this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    this.renderer.setSize(window.innerWidth, window.innerHeight);
    this.renderer.setPixelRatio(window.devicePixelRatio);
    this.container.appendChild(this.renderer.domElement);

    // 4. Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.2);
    this.scene.add(ambientLight);

    const pointLight = new THREE.PointLight(0x00f3ff, 2, 200);
    pointLight.position.set(0, 20, 0);
    this.scene.add(pointLight);

    window.addEventListener('resize', this.onResize.bind(this));
  }

  public init() {
    this.animate();
  }

  public addNode(content: string) {
    const geometry = new THREE.SphereGeometry(2, 16, 16);
    const material = new THREE.MeshStandardMaterial({
      color: 0x00f3ff,
      emissive: 0x00f3ff,
      emissiveIntensity: 0.5,
      transparent: true,
      opacity: 0.8
    });
    
    const sphere = new THREE.Mesh(geometry, material);
    
    // Position randomly, but with a specific depth (Y axis) based on "gravity"
    // Everything starts low (y = -100).
    const x = (Math.random() - 0.5) * 40;
    const z = (Math.random() - 0.5) * 40;
    const y = -100 + (Math.random() * 20);

    sphere.position.set(x, y, z);
    sphere.userData = { content, upvotes: 0, targetY: y };
    
    this.scene.add(sphere);
    this.nodes.push(sphere);

    const light = new THREE.PointLight(0x00f3ff, 1, 20);
    light.position.copy(sphere.position);
    this.scene.add(light);
  }

  private onResize() {
    this.camera.aspect = window.innerWidth / window.innerHeight;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(window.innerWidth, window.innerHeight);
  }

  private animate() {
    this.animationId = requestAnimationFrame(this.animate.bind(this));
    
    // Rotate and float nodes
    this.nodes.forEach(node => {
      node.rotation.y += 0.01;
      node.rotation.x += 0.005;
      
      // Simulate gravity / upvoting buoyancy
      if (node.position.y < node.userData.targetY) {
        node.position.y += 0.1; 
      }
    });

    this.renderer.render(this.scene, this.camera);
  }

  public dispose() {
    if (this.animationId !== undefined) {
      cancelAnimationFrame(this.animationId);
    }
    
    window.removeEventListener('resize', this.onResize.bind(this));
    
    if (this.renderer.domElement && this.renderer.domElement.parentNode) {
      this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
    }
    this.renderer.dispose();
  }
}
