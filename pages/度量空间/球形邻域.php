<?php

$_SESSION['page_id'] = '67e40192f231f';

$html = new DocPHT\Lib\DocPHT(['球形邻域']);
$values = [
$html->title('球形邻域','球形邻域'), 
$html->markdown(<<<'c'
设$(X,\rho)$是[[度量空间]]，$a\in X$。对任意给定的$\varepsilon>0$，集合$B(a,\varepsilon)=\{x\in X: \rho(x,a)<\varepsilon\}$称为点$a$的一个$\varepsilon$<b>球形邻域</b>。
c),
$html->addButton(),
];
$GLOBALS["page_author"] = 'hbghlyj 2025-03-28 21:49';