document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.createElement('canvas');
    canvas.id = 'stars';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let stars = [];
    const STAR_COUNT = 150;
    let mouseX = 0, mouseY = 0;

    const resize = () => {
        canvas.width = window.innerWidth;
        canvas.height = Math.max(document.body.scrollHeight, window.innerHeight);
        stars = Array.from({ length: STAR_COUNT }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            r: Math.random() * 1.5 + 0.2,
            s: Math.random() * 0.3 + 0.1,
            depth: Math.random() * 1.5 + 0.5 // profondeur
        }));
    };

    const animate = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        stars.forEach(star => {
            // effet de profondeur dynamique
            const offsetX = (mouseX - window.innerWidth / 2) * 0.002 * star.depth;
            const offsetY = (mouseY - window.innerHeight / 2) * 0.002 * star.depth;

            ctx.beginPath();
            ctx.arc(star.x + offsetX, star.y + offsetY, star.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${0.7 / star.depth})`;
            ctx.shadowBlur = 8;
            ctx.shadowColor = '#aaf';
            ctx.fill();

            star.y += star.s;
            if (star.y > canvas.height) star.y = 0;
        });
        requestAnimationFrame(animate);
    };

    const onMouseMove = (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    };

    const onScroll = () => {
        const scrollOffset = window.scrollY * 0.3;
        canvas.style.transform = `translateY(${scrollOffset}px)`;
    };

    resize();
    animate();
    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('scroll', onScroll);
});


