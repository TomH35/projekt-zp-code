class Robot {
    constructor(scene, x, y, image) {
        this.scene = scene;
        this.sprite = scene.add.sprite(x, y, image);
    }

    MoveRight(distance) {
        this.sprite.x += distance;
    }

    MoveLeft(distance) {
        this.sprite.x -= distance;
    }

    MoveUp(distance) {
        this.sprite.y -= distance;
    }

    MoveDown(distance) {
        this.sprite.y += distance;
    }
}