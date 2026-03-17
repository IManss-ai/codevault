(function () {
    var container = document.getElementById('wave-container');
    if (!container) return;

    var scene    = new THREE.Scene();
    var camera   = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 10000);
    camera.position.set(0, 350, 1200);
    camera.lookAt(0, 0, 0);

    var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x000000, 0);
    container.appendChild(renderer.domElement);

    var COLS = 60, ROWS = 40, SEP = 150;
    var total = COLS * ROWS;

    var positions = new Float32Array(total * 3);
    var geometry  = new THREE.BufferGeometry();

    var ix, iy, i = 0;
    for (iy = 0; iy < ROWS; iy++) {
        for (ix = 0; ix < COLS; ix++) {
            positions[i * 3]     = (ix - COLS / 2) * SEP;
            positions[i * 3 + 1] = 0;
            positions[i * 3 + 2] = (iy - ROWS / 2) * SEP;
            i++;
        }
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

    var material = new THREE.PointsMaterial({
        size:        7,
        opacity:     0.5,
        transparent: true,
        color:       new THREE.Color('rgb(100,100,120)'),
        sizeAttenuation: true,
    });

    var points = new THREE.Points(geometry, material);
    scene.add(points);

    var count = 0;

    function animate() {
        requestAnimationFrame(animate);

        var pos = geometry.attributes.position;
        var idx = 0;
        for (var row = 0; row < ROWS; row++) {
            for (var col = 0; col < COLS; col++) {
                pos.array[idx * 3 + 1] =
                    Math.sin((col + count) * 0.3) * 50 +
                    Math.sin((row + count) * 0.5) * 50;
                idx++;
            }
        }
        pos.needsUpdate = true;
        count += 0.05;

        renderer.render(scene, camera);
    }

    animate();

    window.addEventListener('resize', function () {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
})();
