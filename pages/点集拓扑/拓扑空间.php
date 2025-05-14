<?php

use DocPHT\Lib\DocPHT;

$_SESSION['page_id'] = '67e372bcf197f';

$html = new DocPHT(['拓扑空间的定义','平凡空间','离散空间']);
$values = [
$html->title('拓扑空间的定义','拓扑空间的定义'), 
$html->markdown('设$X$是一个集合，$\\mathcal{T}$是$X$的一个子集族，满足：
1. $X,\\varnothing\\in\\mathcal{T}$。
1. 若$A,B\\in\\mathcal{T}$则$A\\cap B\\in\\mathcal{T}$。
1. 若$\\mathcal{T}\'\\subseteq\\mathcal{T}$则$\\bigcup_{A\\in\\mathcal{T}\'}A\\in\\mathcal{T}$。

则称$\\mathcal{T}$是$X$上的一个**拓扑**，称$(X,\\mathcal{T})$是一个**拓扑空间**，称$\\mathcal{T}$中的每个元素为$X$中的一个**开集**。'), 
$html->title('平凡空间','平凡空间'), 
$html->markdown('设$X$是一个集合，令$\\mathcal{T} = \\{X,\\varnothing\\}$，则$\\mathcal{T}$是$X$的一个拓扑，称为$X$的<b>平凡拓扑</b>，称$(X,\\mathcal{T})$是<b>平凡空间</b>。'), 
$html->title('离散空间','离散空间'), 
$html->markdown('设$X$是一个集合，令$\\mathcal{T} = \\mathcal{P}(X)$，即由$X$的所有子集构成的集族，则$\\mathcal{T}$是$X$的一个拓扑，称为$X$的<b>离散拓扑</b>，称$(X,\\mathcal{T})$是<b>离散空间</b>。'), 
$html->addButton(),
];