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
            parentTd.style.backgroundColor = '#f9f9f9';
            parentTd.style.boxShadow = 'inset 0px 0px 5px #c1c1c1';
        });
        parentTd.addEventListener('mouseleave', () => {
            parentTd.style.backgroundColor = '';
            parentTd.style.boxShadow = '';
        });
    });
});