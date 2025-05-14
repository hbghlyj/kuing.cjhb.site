<?php

use DocPHT\Lib\DocPHT;

$_SESSION['page_id'] = '67e401612786c';

$html = new DocPHT(['开集']);
$values = [
$html->title('开集','开集'), 
$html->markdown('设$A$是度量空间$X$的一个子集，若对任意的$a\\in A$都存在实数$\\varepsilon_a>0$使得[[球形邻域]]$B(a,\\varepsilon_a)\\subseteq A$，则称$A$是度量空间$X$中的一个<b>开集</b>。'), 
$html->addButton(),
];