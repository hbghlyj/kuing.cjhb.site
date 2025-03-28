
            </div> <!-- end container -->
            <footer class="footer">
                <form action="/misc.php?mod=tag" method="post" class="d-sm-flex justify-content-center justify-content-sm-between">
                    <input type="hidden" name="name" value="<?= rawurldecode(basename($_SERVER['REQUEST_URI'])) ?>">
                    <button type="submit" class="page-link text-muted"><?= DocPHT\Core\Translator\T::trans('Related threads') ?></button>
                    <?php if(isset($GLOBALS["page_author"])): ?>
                        <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center text-muted">
                            <i class="fa fa-pencil"></i> <a href="/home.php?mod=space&username=<?= $GLOBALS["page_author"]?>"><?= $GLOBALS["page_author"]?></a>
                        </span>
                    <?php endif; ?>
                </form>
            </footer>

            <?php if (isset($_SESSION['Active']) && $this->pageModel->hideBySlug('page/search')): ?>
            <ul class="list-inline text-left mt-4 ml-3">
                <li class="list-inline-item">
                    <button type="button" id="sk-goback" class="btn btn-outline-secondary btn-sm" onclick="goBack()">
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
    <script src="public/assets/js/netteForms.js"></script>
    <script src="public/assets/js/netteFormValidate.js"></script>
    <!-- jQuery -->
    <script src="public/assets/js/jquery-3.4.1.min.js"></script>
    <!-- jQuery-UI -->
    <script src="public/assets/js/jquery-ui.min.js"></script>
    <!-- Popper.JS -->
    <script src="public/assets/js/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="public/assets/js/bootstrap.min.js"></script>
    <!-- jQuery Custom Scroller -->
    <script src="public/assets/js/jquery.Scrollbar.concat.min.js"></script>
    <!-- Lightweight, robust, elegant syntax highlighting -->
    <script src="public/assets/js/clipboard.min.js"></script>
    <script src="public/assets/js/prism.js"></script>
    <!-- bootstrap select in form -->
    <script src="public/assets/js/bootstrap-select.min.js"></script>
    <!-- Shortcut key -->
    <script src="public/assets/js/jquery.key.js"></script>
    <script src="public/assets/js/map.shortcut.key.js"></script>
    <!-- Main JavaScript for DOC-PHT -->
    <script src="public/assets/js/doc-pht.js"></script>
    <!-- AnchorJS -->
    <script src="public/assets/js/anchor.min.js"></script>
    <script>
        anchors.options.placement = 'left';
        anchors.add('h2');
    </script>
</body>

</html>
