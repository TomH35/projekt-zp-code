class Robot {
    constructor(scene, x, y, bodyTexture) {
        this.scene = scene;

        this.bodySprite = scene.physics.add.sprite(x, y, bodyTexture);
        this.bodySprite.setCollideWorldBounds(true);

        this.bodySprite.angle = 0;
        this.bodySprite.rotation = 0;

        this.bodySprite.body.onWorldBounds = true;
        this.scene.physics.world.on(
            'worldbounds',
            (body) => {
                if (body.gameObject === this.bodySprite && typeof this.onHitWall === 'function') {
                    this.onHitWall();
                }
            },
            this
        );

        this.rotationSpeed = 2; 
        this.moveSpeed = 200;
        this.actionQueue = [];
        this.currentAction = null;
        this.targetRotation = null;
        this.rotationDirection = null;
        this.targetDistance = null;
        this.startPosition = null;

        this.energy = 100;

        this.previousPosition = new Phaser.Math.Vector2(x, y);
        this.stalledFrames = 0;
        this.movementDirection = null;

        this.gun = null;
    }

    setGun(gun) {
        this.gun = gun;
        gun.robot = this; 
    }

    TurnRight(degrees) { this.enqueueAction(() => this.startTurning(degrees)); }
    TurnLeft(degrees)  { this.enqueueAction(() => this.startTurning(-degrees)); }
    MoveForward(distance)  { this.enqueueAction(() => this.startMoving(distance)); }
    MoveBackward(distance) { this.enqueueAction(() => this.startMoving(-distance)); }

    enqueueAction(action) {
        this.actionQueue.push(action);
    }


    startTurning(degrees) {
        this.targetRotation = Phaser.Math.Angle.WrapDegrees(this.bodySprite.angle + degrees);
        this.rotationDirection = Math.sign(degrees);
        this.rotationSpeed = 2;
        this.bodySprite.rotation = Phaser.Math.DegToRad(this.bodySprite.angle);
    }

    startMoving(distance) {
        this.targetDistance = Math.abs(distance);
        this.startPosition = new Phaser.Math.Vector2(this.bodySprite.x, this.bodySprite.y);

        const directionMultiplier = Math.sign(distance);
        const adjustedAngle = this.bodySprite.angle + (directionMultiplier === -1 ? 180 : 0);
        const angleInRadians = Phaser.Math.DegToRad(adjustedAngle);

        this.scene.physics.velocityFromRotation(
            angleInRadians,
            this.moveSpeed,
            this.bodySprite.body.velocity
        );
        this.movementDirection = new Phaser.Math.Vector2(this.bodySprite.body.velocity).normalize();
    }

    stopMovement() {
        this.bodySprite.body.setVelocity(0, 0);
        this.rotationSpeed = 0;
        this.targetRotation = null;
        this.rotationDirection = null;
        this.targetDistance = null;
        this.currentAction = null;
        this.movementDirection = null;
        this.stalledFrames = 0;
    }

    update() {
    
        if (
            this.targetRotation === null &&
            this.targetDistance === null &&
            this.currentAction === null &&
            this.actionQueue.length > 0
        ) {
            this.currentAction = this.actionQueue.shift();
            this.currentAction();
        }

        if (this.targetRotation !== null) {
            const angleDiff = Phaser.Math.Angle.ShortestBetween(this.bodySprite.angle, this.targetRotation);
            if (Math.abs(angleDiff) <= this.rotationSpeed) {
                this.bodySprite.angle = this.targetRotation;
                this.bodySprite.rotation = Phaser.Math.DegToRad(this.bodySprite.angle);
                this.targetRotation = null;
                this.rotationDirection = null;
                this.currentAction = null;
            } else {
                this.bodySprite.angle += this.rotationDirection * this.rotationSpeed;
                this.bodySprite.angle = Phaser.Math.Angle.WrapDegrees(this.bodySprite.angle);
                this.bodySprite.rotation = Phaser.Math.DegToRad(this.bodySprite.angle);
            }
        }

        if (this.targetDistance !== null) {
            const currentPosition = new Phaser.Math.Vector2(this.bodySprite.x, this.bodySprite.y);
            const displacement = currentPosition.clone().subtract(this.startPosition);
            const distanceMoved = displacement.dot(this.movementDirection);

            if (distanceMoved >= this.targetDistance) {
                this.stopMovement();
            } else if (distanceMoved < -1) {
                this.stopMovement();
            } else if (this.bodySprite.body.speed < 1) {
                this.stalledFrames++;
                if (this.stalledFrames > 5) {
                    this.stopMovement();
                    if (typeof this.onHitWall === 'function') {
                        this.onHitWall();
                    }
                }
            } else {
                this.stalledFrames = 0;
            }
            this.previousPosition.copy(currentPosition);
        }

        if (this.gun) {
            const allRobots = this.scene.robotInstances || [];
            this.gun.update(allRobots);
        }
    }

    run() {}
    onHitWall() {}
    onDetectEnemy(enemyRobot) {}
}
