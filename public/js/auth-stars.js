(function () {
  const canvas = document.getElementById('auth-stars');
  if (!canvas || !canvas.getContext) {
    return;
  }

  const ctx = canvas.getContext('2d');
  const STAR_COUNT = 520;
  const stars = [];
  let width = 0;
  let height = 0;
  let centerX = 0;
  let centerY = 0;
  let maxRadius = 0;
  let pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

  class Star {
    constructor(initial) {
      this.reset(initial);
    }

    reset(initial) {
      this.radius = initial ? Math.random() * maxRadius : 0;
      this.angle = Math.random() * Math.PI * 2;
      this.radialSpeed = 18 + Math.random() * 22;
      this.angularSpeed = (Math.random() * 0.6 + 0.25) * (Math.random() < 0.5 ? -1 : 1);
      this.size = Math.random() * 1.4 + 0.6;
      this.alpha = Math.random() * 0.6 + 0.2;
    }

    update(delta) {
      this.angle += this.angularSpeed * delta * 0.0015;
      this.radius += this.radialSpeed * delta * 0.04;
      if (this.radius > maxRadius) {
        this.reset(false);
      }
    }

    draw() {
      const x = centerX + Math.cos(this.angle) * this.radius;
      const y = centerY + Math.sin(this.angle) * this.radius;
      ctx.beginPath();
      ctx.fillStyle = `rgba(226, 232, 240, ${this.alpha})`;
      ctx.arc(x, y, this.size, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width = Math.floor(width * pixelRatio);
    canvas.height = Math.floor(height * pixelRatio);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';

    ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    centerX = width / 2;
    centerY = height / 2;
    maxRadius = Math.sqrt(centerX * centerX + centerY * centerY);

    if (!stars.length) {
      for (let i = 0; i < STAR_COUNT; i++) {
        stars.push(new Star(true));
      }
    }
  }

  let last = performance.now();

  function frame(now) {
    const delta = now - last;
    last = now;

    ctx.clearRect(0, 0, width, height);
    ctx.globalCompositeOperation = 'lighter';

    for (let i = 0; i < stars.length; i++) {
      const star = stars[i];
      star.update(delta);
      star.draw();
    }

    ctx.globalCompositeOperation = 'source-over';
    requestAnimationFrame(frame);
  }

  resize();
  requestAnimationFrame(frame);
  window.addEventListener('resize', resize, { passive: true });
})();
