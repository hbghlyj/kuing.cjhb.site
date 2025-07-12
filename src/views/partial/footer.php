
            </div> <!-- end container -->
            <?php if (isset($_SESSION['Active']) && $this->pageModel->hideBySlug('page/search')): ?>
            <ul class="list-inline text-left mt-4 ml-3">
                <li class="list-inline-item">
                    <button type="button" id="sk-goback" class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">
                        <i class="fa fa-undo" aria-hidden="true"></i>
                    </button>
                </li>
            </ul>
            <?php endif ?>
        </div> <!-- end Page Content  -->
    </div> <!-- end wrapper -->

    <a href="#page-top" class="top">
      <i class="fa fa-chevron-circle-up fa-2x" aria-hidden="true"></i>
    </a>

    <!-- Nette Forms -->
    <script src="/public/assets/js/netteForms.js"></script>
    <script src="/public/assets/js/netteFormValidate.js"></script>
    <!-- jQuery -->
    <script src="/public/assets/js/jquery-3.4.1.min.js"></script>
    <!-- Popper.JS -->
    <script src="/public/assets/js/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="/public/assets/js/bootstrap.min.js"></script>
    <!-- jQuery Custom Scroller -->
    <script src="/public/assets/js/jquery.Scrollbar.concat.min.js"></script>
    <!-- Lightweight, robust, elegant syntax highlighting -->
    <script src="/public/assets/js/clipboard.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="//unpkg.com/highlightjs-lean/dist/lean.min.js"></script>
    <script src="//unpkg.com/highlightjs-copy/dist/highlightjs-copy.min.js"></script>
    <link rel="stylesheet" href="//unpkg.com/highlightjs-copy/dist/highlightjs-copy.min.css">
    <script>
        hljs.addPlugin(new CopyButtonPlugin());
        hljs.highlightAll();
    </script>
    <!-- bootstrap select in form -->
    <script src="/public/assets/js/bootstrap-select.min.js"></script>
    <!-- Shortcut key -->
    <script src="/public/assets/js/jquery.key.js"></script>
    <script src="/public/assets/js/map.shortcut.key.js"></script>
    <!-- Main JavaScript for DOC-PHT -->
    <script src="/public/assets/js/doc-pht.js"></script>
    <!-- AnchorJS -->
    <script src="/public/assets/js/anchor.min.js"></script>
    <script>
        anchors.options.placement = 'left';
        anchors.add('#content :is(h1,h2,h3,h4,h5,h6)');
    </script>