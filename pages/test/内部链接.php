<?php

$_SESSION['page_id'] = '67e4103838c28';

$html = new DocPHT\Lib\DocPHT(['内部链接']);
$values = [
$html->title('内部链接','内部链接'), 
$html->markdown(<<<'a'
如果需要在左侧显示，平行的概念应分别创建条目。
若需要链接指向该条目，语法为`[[条目名]]`，如[[拓扑空间]]

如果在同一个页面添加小节，小节标题和链接会在页面右上角显示，单击后跳到小节。与Wikipedia类似，可分别编辑每个小节，使得长页面更容易编辑。
若需要链接指向该小节，语法为`[[条目名#小节名]]`，如[[拓扑空间#平凡空间]]
a),
$html->markdown(<<<'a'

a),
$html->addButton(),
];
$GLOBALS["page_author"] = 'abababa 2025-04-13 05:58';