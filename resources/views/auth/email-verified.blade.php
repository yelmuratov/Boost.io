<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified - Boostio.uz</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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

        #confettiCanvas {
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
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.1); }
        }

        .error-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            animation: none;
        }

        .error-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        h1 {
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .bonus-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 700;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
        }

        .bonus-amount {
            font-size: 56px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
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

        .info-row:last-child { margin-bottom: 0; }
        .info-label { color: #6b7280; font-weight: 500; }
        .info-value { color: #1f2937; font-weight: 700; }

        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin: 16px 0;
        }

        .highlight {
            color: #667eea;
            font-weight: 700;
        }

        .locked-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef3c7;
            color: #92400e;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }

        .success-message {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .success-message p {
            margin: 0;
            color: #065f46;
            font-size: 15px;
        }
    </style>
</head>

<body>
    @if($success && isset($bonusAmount))
        <canvas id="confettiCanvas"></canvas>
    @endif
    
    <div class="card">
        <div class="logo">Boostio.uz</div>

        @if($success)
            <div class="icon">🎉</div>
            <h1>Email Verified!</h1>
            
            @if(isset($bonusAmount))
                <div class="bonus-badge">✨ WELCOME BONUS AWARDED ✨</div>
                
                <div class="bonus-amount">{{ $bonusAmount }} UZS</div>
                
                <div class="locked-badge">
                    🔒 Locked - Unlock by Spending
                </div>
                
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Bonus Amount</span>
                        <span class="info-value">{{ $bonusAmount }} UZS</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Unlock After Spending</span>
                        <span class="info-value">{{ $unlockThreshold }} UZS</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value" style="color: #f59e0b;">Locked 🔒</span>
                    </div>
                </div>
                
                <div class="success-message">
                    <p>
                        🎊 <strong>Congratulations!</strong> Your email has been verified and you've received a 
                        <span class="highlight">{{ $bonusAmount }} UZS welcome bonus</span>!
                    </p>
                </div>
                
                <p style="font-size: 14px;">
                    To unlock your bonus, simply spend <strong>{{ $unlockThreshold }} UZS</strong> on orders. 
                    Once unlocked, the bonus will be added to your main balance!
                </p>
            @else
                <p>Your email has been successfully verified. You can now enjoy all the features of Boostio.uz.</p>
            @endif
        @else
            <div class="icon error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1>Verification Failed</h1>
            <p>{{ $message ?? 'The verification link is invalid or has expired. Please request a new verification link.' }}</p>
        @endif
    </div>

    @if($success && isset($bonusAmount))
    <script>
        // Confetti cannons - fire from both sides to center
        const canvas = document.getElementById('confettiCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        class Confetti {
            constructor(x, y, fromLeft) {
                const colors = ['#667eea', '#764ba2', '#10b981', '#fbbf24', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];
                this.x = x;
                this.y = y;
                this.color = colors[Math.floor(Math.random() * colors.length)];
                this.size = Math.random() * 8 + 4;
                this.rotation = Math.random() * 360;
                this.rotationSpeed = (Math.random() - 0.5) * 15;
                
                // Shoot toward center
                const centerX = canvas.width / 2;
                const angleToCenter = Math.atan2(canvas.height / 2 - y, centerX - x);
                const spread = (Math.random() - 0.5) * 1.2;
                const speed = 10 + Math.random() * 8;
                
                this.vx = Math.cos(angleToCenter + spread) * speed;
                this.vy = Math.sin(angleToCenter + spread) * speed - (Math.random() * 4);
                
                this.gravity = 0.4;
                this.life = 1;
                this.shape = Math.random() < 0.5 ? 'rect' : 'circle';
            }

            update() {
                this.x += this.vx;
                this.y += this.vy;
                this.vy += this.gravity;
                this.rotation += this.rotationSpeed;
                this.life -= 0.006;
                this.vx *= 0.98;
            }

            draw() {
                ctx.save();
                ctx.globalAlpha = this.life;
                ctx.translate(this.x, this.y);
                ctx.rotate((this.rotation * Math.PI) / 180);
                ctx.fillStyle = this.color;
                
                if (this.shape === 'rect') {
                    ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size);
                } else {
                    ctx.beginPath();
                    ctx.arc(0, 0, this.size / 2, 0, Math.PI * 2);
                    ctx.fill();
                }
                
                ctx.restore();
            }

            isDead() {
                return this.life <= 0 || this.y > canvas.height + 50;
            }
        }

        let confettiPieces = [];
        let frameCount = 0;

        function shootConfetti(fromLeft) {
            const startX = fromLeft ? 0 : canvas.width;
            const startY = canvas.height * 0.5 + (Math.random() - 0.5) * 150;
            const burstSize = 20 + Math.random() * 20;
            
            for (let i = 0; i < burstSize; i++) {
                confettiPieces.push(new Confetti(startX, startY, fromLeft));
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            frameCount++;

            // Shoot confetti frequently at start, then slow down
            if (frameCount < 120) {
                if (frameCount % 4 === 0) {
                    shootConfetti(true);
                    shootConfetti(false);
                }
            } else if (frameCount < 200) {
                if (frameCount % 12 === 0) {
                    shootConfetti(true);
                    shootConfetti(false);
                }
            }

            confettiPieces.forEach((confetti, index) => {
                confetti.update();
                confetti.draw();
                
                if (confetti.isDead()) {
                    confettiPieces.splice(index, 1);
                }
            });

            requestAnimationFrame(animate);
        }

        // Start animation
        animate();

        // Initial burst from both sides
        for (let i = 0; i < 10; i++) {
            setTimeout(() => {
                shootConfetti(true);
                shootConfetti(false);
            }, i * 80);
        }

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
    @endif
</body>
</html>