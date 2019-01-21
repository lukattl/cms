    
    <footer class="footer shadow p-3 bg-white rounded ">
        <div class="container">
            <span class="text-muted">powered by TTL Syncro<!--<a class="float-right font-italic" href="mailto:ttl.luka@gmail.com">Kontakt: ttl.luka@gmail.com</a>--></span>
        </div>
    </footer>
    <!-- jQuery -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Parsley JS -->
    <script src="node_modules/parsleyjs/dist/parsley.min.js"></script>
    <script src="node_modules/parsleyjs/dist/i18n/hr.js"></script>
    <!-- Bootstrap datepicker JS-->
    <script src="bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="bootstrap-datepicker/dist/locales/bootstrap-datepicker.hr.min.js"></script>
    <!-- DataTables JS-->
    <script src="node_modules/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="node_modules/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="node_modules\datatables.net-buttons\js\dataTables.buttons.min.js"></script>
    <script src="node_modules\datatables.net-buttons-bs4\js\buttons.bootstrap4.min.js"></script>
    <script src="node_modules\datatables.net-buttons\js\buttons.html5.min.js"></script>
    <script src="node_modules\datatables.net-buttons\js\buttons.print.min.js"></script>
    <script src="node_modules\datatables.net-buttons\js\buttons.flash.min.js"></script>
    <script src="node_modules\datatables.net-buttons\js\buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <!-- DataTables FixedHeader -->
    <script src="node_modules\datatables.net-fixedheader\js\dataTables.fixedHeader.min.js"></script>
    <script src="node_modules\datatables.net-fixedheader-bs4\js\fixedHeader.bootstrap4.min.js"></script>
    <!-- DataTables Responsive -->
    <script src="node_modules\datatables.net-responsive\js\dataTables.responsive.min.js"></script>
    <script src="node_modules\datatables.net-responsive-bs4\js\responsive.bootstrap4.min.js"></script>
    <?php
    switch ($page) {
        case 'totals':
            $pageJs = 'Totals.init()';
            break;
        case 'bills':
            $pageJs = 'Bills.init()';
            break;
        case 'admin':
            $pageJs = 'Admin.init()';
            break;
        case 'app':
            $pageJs = 'App.init()';
            break;
    }
    ?>
    <!-- Page JS -->
    <script src="dist/js/<?php echo $page?>.min.js"></script>
    <script>
        $(document).ready(function(){
            <?php echo $pageJs ?>;
        });
    </script>
  </body>
</html>