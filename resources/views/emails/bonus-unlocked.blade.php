<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonus Unlocked!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            color: #10b981;
            margin-bottom: 30px;
        }

        .icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 50px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
        }

        .info-box {
            background: #f0fdf4;
            border: 2px solid #10b981;
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

        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin: 16px 0;
        }

        .highlight {
            color: #10b981;
            font-weight: 700;
        }

        .success-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin: 16px 0;
        }
    </style>
</head>

<body>
    <canvas id="fireworksCanvas"></canvas>

    <div class="card">
        <div class="logo">Boostio.uz</div>
        <div class="icon">🏆</div>
        <h1>Bonus Unlocked!</h1>
        <div class="success-badge">✨ CONGRATULATIONS ✨</div>
        <p>Great news, {{ $userName }}!</p>

        <div class="amount">+{{ $amount }} UZS</div>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Unlocked Bonus</span>
                <span class="info-value">{{ $amount }} UZS</span>
            </div>
            <div class="info-row">
                <span class="info-label">New Total Balance</span>
                <span class="info-value">{{ $newBalance }} UZS</span>
            </div>
        </div>

        <p>
            You've reached the spending threshold! Your <span class="highlight">{{ $amount }} UZS</span>
            bonus has been unlocked and added to your main balance.
            You can now use it for any orders!
        </p>
        <p style="font-size: 14px; color: #9ca3af; margin-top: 24px;">
            Thank you for being a valued customer of Boostio.uz! 🎉
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
                const particleCount = 40 + Math.random() * 40;
                for (let i = 0; i < particleCount; i++) {
                    this.particles.push({
                        x: x,
                        y: y,
                        vx: (Math.random() - 0.5) * 10,
                        vy: (Math.random() - 0.5) * 10,
                        life: 1,
                        color: `hsl(${[60, 120, 180, 240, 300][Math.floor(Math.random() * 5)]}, 70%, 60%)`
                    });
                }
            }

            update() {
                this.particles.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.15;
                    p.life -= 0.008;
                });
                this.particles = this.particles.filter(p => p.life > 0);
            }

            draw() {
                this.particles.forEach(p => {
                    ctx.globalAlpha = p.life;
                    ctx.fillStyle = p.color;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
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
            ctx.fillStyle = 'rgba(16, 185, 129, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.globalAlpha = 1;

            if (Math.random() < 0.08) {
                fireworks.push(new Firework(
                    Math.random() * canvas.width,
                    Math.random() * canvas.height * 0.6
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