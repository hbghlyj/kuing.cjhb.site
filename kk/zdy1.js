document.addEventListener("DOMContentLoaded", function() {
    // Find all forum links within the table
    const forumLinks = document.querySelectorAll(
        'table.fl_tb :is(.fl_by,.fl_icn) a[href^="forum.php?mod='
    );

    forumLinks.forEach(link => {
        const parentTd = link.closest('td');
        if (!parentTd) return;

        // make the entire cell clickable
        parentTd.onclick = e => {
            if (e.target.closest('a')) return;
            window.location.href = link.href;
        };

        // indicate clickable
        parentTd.style.cursor = 'pointer';

        // hover background
        parentTd.addEventListener('mouseenter', () => {
            parentTd.style.boxShadow = 'rgba(0, 0, 0, 0.3) 0px 2px 4px inset';
            link.style.textDecoration = 'underline';
        });
        parentTd.addEventListener('mouseleave', () => {
            parentTd.style.boxShadow = '';
            link.style.textDecoration = 'none';
        });
    });
});