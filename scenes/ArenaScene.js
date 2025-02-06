class ArenaScene extends Phaser.Scene {
    constructor(robotsData, userPreferences) {
        super({ key: 'ArenaScene' });
        this.robotsData = robotsData;
        this.userPreferences = userPreferences; 

        this.robotInstances = [];

        this.robotGroup = null;
        this.bulletGroup = null;

        this.gameOver = false;
    }

    preload() {
        const arenaBg  = this.userPreferences?.arena_image || './img/arena_bg2.png';
        const gunImage = this.userPreferences?.gun_image   || './img/Gun_01.png';
        const hullImage= this.userPreferences?.hull_image  || './img/Hull_01.png';

        this.load.image('background-image', arenaBg);
        this.load.image('robot-gun', gunImage);
        this.load.image('robot-body', hullImage);
        this.load.image('bullet-image', './img/Medium_Shell.png');

        const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        letters.forEach(letter => {
            this.load.image(`explosion-${letter}`, `./img/Explosion_${letter}.png`);
        });
    }

    create() {

        const bg = this.add.image(0, 0, 'background-image');
        bg.setOrigin(0, 0);
        bg.setDisplaySize(this.sys.game.config.width, this.sys.game.config.height);

        this.physics.world.setBounds(
            0,
            0,
            this.sys.game.config.width,
            this.sys.game.config.height
        );

        this.robotGroup = this.physics.add.group();
        this.bulletGroup = this.physics.add.group();

        this.anims.create({
            key: 'explosion',
            frames: [
                { key: 'explosion-A' },
                { key: 'explosion-B' },
                { key: 'explosion-C' },
                { key: 'explosion-D' },
                { key: 'explosion-E' },
                { key: 'explosion-F' },
                { key: 'explosion-G' },
                { key: 'explosion-H' }
            ],
            frameRate: 12, 
            repeat: 0       
        });

        this.robotsData.forEach(robotData => {
            const robotInstance = this.createRobotInstance(robotData);
            if (robotInstance) {
                this.robotInstances.push(robotInstance);
                this.robotGroup.add(robotInstance.bodySprite);
                robotInstance.bodySprite.setCollideWorldBounds(true);

                const style = { font: '14px Arial', fill: '#ffffff' };
                const energyText = this.add.text(
                    robotInstance.bodySprite.x,
                    robotInstance.bodySprite.y - 40,
                    `Energy: ${robotInstance.energy}`,
                    style
                );
                robotInstance.energyText = energyText;
            }
        });

        this.physics.add.collider(
            this.robotGroup,
            this.robotGroup,
            this.handleRobotCollision,
            null,
            this
        );

        this.physics.add.overlap(
            this.bulletGroup,
            this.robotGroup,
            this.handleBulletHit,
            null,
            this
        );
    }

    createRobotInstance(robotData) {
        try {
            eval(`(function() { ${robotData.code} window['${robotData.script_name}'] = ${robotData.script_name}; })();`);
            const RobotClass = window[robotData.script_name];

            if (typeof RobotClass === 'function') {
                const x = Phaser.Math.Between(50, this.sys.game.config.width - 50);
                const y = Phaser.Math.Between(50, this.sys.game.config.height - 50);

                const robotInstance = new RobotClass(this, x, y, 'robot-body');
                const gunInstance = new Gun(this, x, y, 'robot-gun');
                robotInstance.setGun(gunInstance);

                this.add.existing(robotInstance.bodySprite);
                this.add.existing(gunInstance.gunSprite);

                robotInstance.runInterpreter = this.createRobotInterpreter(robotInstance);

                robotInstance.robotName = robotData.script_name || "Unnamed Robot";

                return robotInstance;
            } else {
                console.error(`Class ${robotData.script_name} not found after eval.`);
                return null;
            }
        } catch (error) {
            console.error(`Error creating robot instance for ${robotData.script_name}:`, error);
            return null;
        }
    }

    createRobotInterpreter(robotInstance) {
        return () => {
            if (
                robotInstance.actionQueue.length === 0 &&
                robotInstance.currentAction === null &&
                typeof robotInstance.run === 'function'
            ) {
                robotInstance.run();
            }
            robotInstance.update();
        };
    }

    handleRobotCollision(robotSpriteA, robotSpriteB) {
        const robot1 = this.robotInstances.find(r => r.bodySprite === robotSpriteA);
        const robot2 = this.robotInstances.find(r => r.bodySprite === robotSpriteB);

        if (robot1 && robot2 && typeof robot1.onDetectEnemy === 'function') {
            robot1.onDetectEnemy(robot2);
        }
    }

    handleBulletHit(bulletSprite, robotSprite) {
        console.log('handleBulletHit => Overlap bullet vs robot');

        const bulletData = bulletSprite.bulletData;
        const robot = this.robotInstances.find(r => r.bodySprite === robotSprite);

        if (!bulletData || !robot) {
            console.warn('No valid bullet or robot found in handleBulletHit, returning.');
            return;
        }

        if (bulletData.shooter && bulletData.shooter.bodySprite === robotSprite) {
            console.log('Ignoring friendly-fire collision with shooter.');
            return;
        }

        console.log(`Bullet hit robot! Power=${bulletData.power}, Damage=${bulletData.damage}`);

        const explosion = this.add.sprite(bulletSprite.x, bulletSprite.y, 'explosion-A');
        explosion.setDepth(9999); 
        explosion.play('explosion'); 

        explosion.on('animationcomplete', () => {
            explosion.destroy();
        });

        
        robot.energy -= bulletData.damage;
        console.log(`Robot energy after damage: ${robot.energy}`);

        if (robot.energy <= 0) {
            this.destroyRobot(robot);
        }


        if (bulletData.shooter) {
            bulletData.shooter.energy += bulletData.energyReturnOnHit;
            console.log(`Shooter gained ${bulletData.energyReturnOnHit}, new energy=${bulletData.shooter.energy}`);
        }


        bulletSprite.destroy();
    }

    createBullet(params) {
        const { x, y, angleDeg, power, shooterRobot } = params;

        const speed = 3 * power * 150;
        const damage = (10 * power) + 2 * Math.max(0, power - 1);
        const energyReturnOnHit = 0;

        const offsetDistance = 20;
        const angleRad = Phaser.Math.DegToRad(angleDeg);
        const spawnX = x + Math.cos(angleRad) * offsetDistance;
        const spawnY = y + Math.sin(angleRad) * offsetDistance;

        const bulletSprite = this.bulletGroup.create(spawnX, spawnY, 'bullet-image');
        bulletSprite.setCollideWorldBounds(false);
        bulletSprite.setDepth(9999);

        bulletSprite.bulletData = {
            power,
            speed,
            damage,
            energyReturnOnHit,
            shooter: shooterRobot
        };

        bulletSprite.body.velocity.x = Math.cos(angleRad) * speed;
        bulletSprite.body.velocity.y = Math.sin(angleRad) * speed;
        bulletSprite.rotation = angleRad;

        bulletSprite.body.onWorldBounds = true;
        bulletSprite.body.worldBounce = true;
        this.physics.world.on('worldbounds', body => {
            if (body.gameObject === bulletSprite) {
                bulletSprite.destroy();
            }
        });
    }

    destroyRobot(robot) {
        console.log('destroyRobot => Destroying robot:', robot);

        if (robot.energyText) {
            robot.energyText.destroy();
        }
        if (robot.bodySprite) {
            robot.bodySprite.destroy();
        }
        if (robot.gun && robot.gun.gunSprite) {
            robot.gun.gunSprite.destroy();
        }

        this.robotInstances = this.robotInstances.filter(r => r !== robot);
    }

    showWinnerModal(winningRobot) {
        const modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0,0,0,0.7)';
        modal.style.display = 'flex';
        modal.style.flexDirection = 'column';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '9999';

        const winnerText = document.createElement('h1');
        winnerText.style.color = '#fff';
        winnerText.innerText = `Winner: ${winningRobot.robotName || '???'}`;

        const btnContainer = document.createElement('div');
        btnContainer.style.marginTop = '20px';

        const restartBtn = document.createElement('button');
        restartBtn.innerText = 'Restart';
        restartBtn.style.marginRight = '10px';
        restartBtn.onclick = () => {
            window.location.reload();
        };

        const leaveBtn = document.createElement('button');
        leaveBtn.innerText = 'Leave';
        leaveBtn.onclick = () => {
            window.location.href = 'dashboard.html';
        };

        btnContainer.appendChild(restartBtn);
        btnContainer.appendChild(leaveBtn);

        modal.appendChild(winnerText);
        modal.appendChild(btnContainer);

        document.body.appendChild(modal);
    }

    update() {
        this.robotInstances.forEach(robotInstance => {
            if (robotInstance.runInterpreter) {
                robotInstance.runInterpreter();
            }

            if (robotInstance.energyText && robotInstance.bodySprite) {
                robotInstance.energyText.x = robotInstance.bodySprite.x - 20;
                robotInstance.energyText.y = robotInstance.bodySprite.y - 40;
                robotInstance.energyText.setText(`Energy: ${robotInstance.energy.toFixed(1)}`);
            }
        });

        if (!this.gameOver && this.robotInstances.length === 1) {
            this.gameOver = true;
            this.time.delayedCall(3000, () => {
                const winningRobot = this.robotInstances[0];
                if (winningRobot) {
                    this.showWinnerModal(winningRobot);
                }
            });
        }
    }
}
