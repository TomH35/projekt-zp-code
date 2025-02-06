class Gun {
    constructor(scene, x, y, gunTexture) {
        this.scene = scene;
        this.robot = null;

        this.gunSprite = this.scene.physics.add.sprite(x, y, gunTexture);
        this.gunSprite.setCollideWorldBounds(true);
        this.gunSprite.setDepth(9999);
        this.gunSprite.setOrigin(0.25, 0.5);

        this.gunRelativeAngle = 0; 
        this.gunRotationSpeed = 2;
        this.maxRotationSpeed = 0.05;

        this.actionQueue = [];
        this.currentAction = null;
        this.targetGunRelativeAngle = null;
        this.gunRotationDirection = null;

        this.fireCooldown = 350; 
        this.lastFireTime = 0;
    }

    rotateRight(degrees) {
        this.enqueueAction(() => this.startGunTurning(degrees));
    }

    rotateLeft(degrees) {
        this.enqueueAction(() => this.startGunTurning(-degrees));
    }

    fire(power = 1.0) {
        if (power < 0.1) power = 0.1;
        if (power > 3.0) power = 3.0;

        this.enqueueAction(() => this.startGunFiring(power));
    }

    enqueueAction(action) {
        this.actionQueue.push(action);
    }

    startGunTurning(degrees) {
        this.targetGunRelativeAngle = Phaser.Math.Angle.WrapDegrees(this.gunRelativeAngle + degrees);
        this.gunRotationDirection = Math.sign(degrees);
        this.currentAction = null;
    }

    startGunFiring(power) {
        const now = this.scene.time.now; 
        if (now - this.lastFireTime < this.fireCooldown) {
            console.log('Gun still cooling. No bullet fired.');
            this.currentAction = null;
            return;
        }

        console.log(`Gun: firing with power = ${power}`);
        this.lastFireTime = now;

        const tankAngleDeg = this.robot.bodySprite.angle;
        const actualGunDeg = tankAngleDeg + this.gunRelativeAngle;

        if (typeof this.scene.createBullet === 'function') {
            this.scene.createBullet({
                x: this.gunSprite.x,
                y: this.gunSprite.y,
                angleDeg: actualGunDeg,
                power: power,
                shooterRobot: this.robot
            });
        }

        this.currentAction = null;
    }

    update(otherRobots) {
        if (
            this.targetGunRelativeAngle === null &&
            this.currentAction === null &&
            this.actionQueue.length > 0
        ) {
            this.currentAction = this.actionQueue.shift();
            this.currentAction();
        }

        if (this.targetGunRelativeAngle !== null) {
            const angleDiff = Phaser.Math.Angle.ShortestBetween(this.gunRelativeAngle, this.targetGunRelativeAngle);
            if (Math.abs(angleDiff) <= this.gunRotationSpeed) {
                this.gunRelativeAngle = this.targetGunRelativeAngle;
                this.targetGunRelativeAngle = null;
                this.gunRotationDirection = null;
                this.currentAction = null;
            } else {
                this.gunRelativeAngle += this.gunRotationDirection * this.gunRotationSpeed;
                this.gunRelativeAngle = Phaser.Math.Angle.WrapDegrees(this.gunRelativeAngle);
            }
        }

        this.updateGunPositionAndRotation();

        this.checkLineOfSight(otherRobots);
    }

    updateGunPositionAndRotation() {
        if (!this.robot) return;
        this.gunSprite.x = this.robot.bodySprite.x;
        this.gunSprite.y = this.robot.bodySprite.y;

        const tankAngleDeg = this.robot.bodySprite.angle;
        const actualGunDeg = tankAngleDeg + this.gunRelativeAngle;
        this.gunSprite.rotation = Phaser.Math.DegToRad(actualGunDeg);
    }

    checkLineOfSight(otherRobots) {
        if (!this.robot) return;

        const currentGunRotationDeg = Phaser.Math.RadToDeg(this.gunSprite.rotation);

        otherRobots.forEach(target => {
            if (target === this.robot) return;

            const dx = target.bodySprite.x - this.gunSprite.x;
            const dy = target.bodySprite.y - this.gunSprite.y;
            const angleToTarget = Phaser.Math.RadToDeg(Math.atan2(dy, dx));

            const normalizedGunAngle = Phaser.Math.Angle.WrapDegrees(currentGunRotationDeg);
            const normalizedTargetAngle = Phaser.Math.Angle.WrapDegrees(angleToTarget);

            const tolerance = 5;
            const diff = Phaser.Math.Angle.ShortestBetween(normalizedGunAngle, normalizedTargetAngle);

            if (Math.abs(diff) <= tolerance) {
                if (typeof this.robot.onDetectEnemy === 'function') {
                    this.robot.onDetectEnemy(target);
                }
            }
        });
    }
}
