// Toast notification utility
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-5 right-5 z-50 px-4 py-2 rounded shadow text-white ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => document.body.removeChild(toast), 500);
    }, 3000);
}

// AJAX search for sidebar menu items
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('sidebar-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', () => {
        const filter = searchInput.value.toLowerCase();
        const menuItems = document.querySelectorAll('#sidebar-menu li');
        menuItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(filter)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
