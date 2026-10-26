    </div><!-- /.cms-content -->
</main><!-- /.cms-main -->

<script>
// Add the CSRF token to every POST form (checked in header.php)
document.querySelectorAll('form').forEach(function(form) {
    if (form.method.toLowerCase() !== 'post') return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    input.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(input);
});

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}

// Confirm helper used by delete buttons
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
}

// Auto-dismiss alerts smoothly
document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() {
        el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        setTimeout(function(){ el.remove(); }, 400);
    }, 4500);
});

// Live table filter using topbar search bar
const quickSearch = document.getElementById('cmsQuickSearch');
if (quickSearch) {
    quickSearch.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>
