const admin = localStorage.getItem('admin');
if (!admin) {
    window.location.href = 'index.html';
}
