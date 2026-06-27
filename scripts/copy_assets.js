const fs = require('fs');
const path = require('path');

const srcDestPairs = [
  { src: 'node_modules/bootstrap/dist/css/bootstrap.min.css', dest: 'public/assets/vendor/bootstrap/css/bootstrap.min.css' },
  { src: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', dest: 'public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js' },
  { src: 'node_modules/bootstrap-icons/font/bootstrap-icons.css', dest: 'public/assets/vendor/bootstrap-icons/bootstrap-icons.css' },
  { src: 'node_modules/bootstrap-icons/font/fonts', dest: 'public/assets/vendor/bootstrap-icons/fonts' },
  { src: 'node_modules/jquery/dist/jquery.min.js', dest: 'public/assets/vendor/jquery/jquery.min.js' },
  { src: 'node_modules/datatables.net/js/jquery.dataTables.min.js', dest: 'public/assets/vendor/datatables/js/jquery.dataTables.min.js' },
  { src: 'node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js', dest: 'public/assets/vendor/datatables/js/dataTables.bootstrap5.min.js' },
  { src: 'node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', dest: 'public/assets/vendor/datatables/css/dataTables.bootstrap5.min.css' },
  { src: 'node_modules/sweetalert2/dist/sweetalert2.min.css', dest: 'public/assets/vendor/sweetalert2/sweetalert2.min.css' },
  { src: 'node_modules/sweetalert2/dist/sweetalert2.all.min.js', dest: 'public/assets/vendor/sweetalert2/sweetalert2.all.min.js' },
  { src: 'node_modules/chart.js/dist/chart.umd.js', dest: 'public/assets/vendor/chartjs/chart.umd.js' },
  { src: 'node_modules/flatpickr/dist/flatpickr.min.css', dest: 'public/assets/vendor/flatpickr/flatpickr.min.css' },
  { src: 'node_modules/flatpickr/dist/flatpickr.min.js', dest: 'public/assets/vendor/flatpickr/flatpickr.min.js' }
];

function copyRecursiveSync(src, dest) {
  const exists = fs.existsSync(src);
  const stats = exists && fs.statSync(src);
  const isDirectory = exists && stats.isDirectory();
  if (isDirectory) {
    fs.mkdirSync(dest, { recursive: true });
    fs.readdirSync(src).forEach(function(childItemName) {
      copyRecursiveSync(path.join(src, childItemName),
                        path.join(dest, childItemName));
    });
  } else {
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.copyFileSync(src, dest);
  }
}

console.log('Starting copy of vendor assets...');

srcDestPairs.forEach(pair => {
  if (fs.existsSync(pair.src)) {
    console.log(`Copying ${pair.src} to ${pair.dest}...`);
    copyRecursiveSync(pair.src, pair.dest);
  } else {
    console.error(`Source not found: ${pair.src}`);
  }
});

console.log('Vendor assets copied successfully!');
