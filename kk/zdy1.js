document.addEventListener("DOMContentLoaded", function() {
    // Find all forum links within the table
    const forumLinks = document.querySelectorAll(
        'table.fl_tb h2 a[href^="forum.php?mod=forumdisplay&fid="],' +
        'table.fl_tb td.fl_by a[href^="forum.php?mod=redirect&tid="]'
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
            parentTd.style.backgroundColor = '#ffe';
        });
        parentTd.addEventListener('mouseleave', () => {
            parentTd.style.backgroundColor = '';
        });
    });
});