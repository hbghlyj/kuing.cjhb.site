document.addEventListener("DOMContentLoaded", function() {
    // Find all forum links within the table
    const forumLinks = document.querySelectorAll('table.fl_tb h2 a[href^="forum.php?mod=forumdisplay&fid="],table.fl_tb td.fl_by a[href^="forum.php?mod=redirect&tid="]');

    forumLinks.forEach(link => {
        // Get the parent <td> of the link
        const parentTd = link.closest('td');
        if (parentTd) {
            // Add an onclick event to the <td>
            parentTd.onclick = function() {
                // Simulate a click on the forum link
                window.location.href = link.href;
            };

            // Change the cursor to a pointer to indicate clickable behavior
            parentTd.style.cursor = 'pointer';
        }
    });
});