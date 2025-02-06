class Bullet extends Phaser.Physics.Arcade.Sprite {
    /**
     * @param {Phaser.Scene} scene 
     * @param {number} x 
     * @param {number} y 
     * @param {string} textureKey 
     * @param {object} shooter 
     * @param {number} power
     * @param {number} angleDeg 
     */
    constructor(scene, x, y, textureKey, shooter, power, angleDeg) {
        super(scene, x, y, textureKey);

        scene.add.existing(this);
        scene.physics.add.existing(this);

        this.scene = scene;
        this.shooter = shooter;
        this.power = power;

        this.speed = (20 - (3 * power)) * 100;
        this.damage = (4 * power) + 2 * Math.max(0, power - 1);
        this.energyReturnOnHit = 3 * power;

        const angleRad = Phaser.Math.DegToRad(angleDeg);
        this.body.setVelocity(
            Math.cos(angleRad) * this.speed,
            Math.sin(angleRad) * this.speed
        );

        this.rotation = angleRad;

      
    }

    update() {
    }
}
