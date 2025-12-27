<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Bonus Awarded!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        #fireworksCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 30px;
        }

        .icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 50px;
        }

        h1 {
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .amount {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
        }

        .info-box {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: #6b7280;
        }

        .info-value {
            color: #1f2937;
            font-weight: 600;
        }

        .progress-bar {
            background: #e5e7eb;
            border-radius: 8px;
            height: 8px;
            margin-top: 8px;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            width: 0%;
            transition: width 1s ease;
        }

        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin: 16px 0;
        }

        .highlight {
            color: #667eea;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <canvas id="fireworksCanvas"></canvas>

    <div class="card">
        <div class="logo">Boostio.uz</div>
        <div class="icon">🎉</div>
        <h1>Welcome Bonus Awarded!</h1>
        <p>Congratulations, {{ $userName }}!</p>

        <div class="amount">{{ $amount }} UZS</div>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Bonus Amount (Locked)</span>
                <span class="info-value">{{ $amount }} UZS</span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Balance</span>
                <span class="info-value">{{ $currentBalance }} UZS</span>
            </div>
            <div class="info-row">
                <span class="info-label">Unlock Threshold</span>
                <span class="info-value">{{ $unlockThreshold }} UZS</span>
            </div>
        </div>

        <p>
            Your bonus is currently <span class="highlight">locked</span>.
            To unlock it, spend <strong>{{ $unlockThreshold }} UZS</strong> on orders.
            Once unlocked, the bonus will be added to your main balance!
        </p>
    </div>

    <script>
        // Fireworks animation
        const canvas = document.getElementById('fireworksCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        class Firework {
            constructor(x, y) {
                this.x = x;
                this.y = y;
                this.particles = [];
                const particleCount = 30 + Math.random() * 30;
                for (let i = 0; i < particleCount; i++) {
                    this.particles.push({
                        x: x,
                        y: y,
                        vx: (Math.random() - 0.5) * 8,
                        vy: (Math.random() - 0.5) * 8,
                        life: 1,
                        color: `hsl(${Math.random() * 360}, 70%, 60%)`
                    });
                }
            }

            update() {
                this.particles.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.1;
                    p.life -= 0.01;
                });
                this.particles = this.particles.filter(p => p.life > 0);
            }

            draw() {
                this.particles.forEach(p => {
                    ctx.globalAlpha = p.life;
                    ctx.fillStyle = p.color;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                    ctx.fill();
                });
            }

            isDead() {
                return this.particles.length === 0;
            }
        }

        let fireworks = [];

        function animate() {
            ctx.globalAlpha = 0.1;
            ctx.fillStyle = 'rgba(102, 126, 234, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.globalAlpha = 1;

            if (Math.random() < 0.05) {
                fireworks.push(new Firework(
                    Math.random() * canvas.width,
                    Math.random() * canvas.height * 0.7
                ));
            }

            fireworks.forEach((fw, index) => {
                fw.update();
                fw.draw();
                if (fw.isDead()) {
                    fireworks.splice(index, 1);
                }
            });

            requestAnimationFrame(animate);
        }

        animate();

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>

</html>