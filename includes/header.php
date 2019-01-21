<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Font Awesome Solid CSS -->
    <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- Bootstrap DatePicker CSS-->
    <link rel="stylesheet" href="bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
    <!-- DataTables CSS-->
    <link rel="stylesheet" href="node_modules/datatables.net-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.5.2/css/buttons.dataTables.min.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="dist/css/style.min.css">
    
    <title><?php echo $title ?></title>
  </head>
  <body>
  <div class="container-flow">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-5">
                <a class="navbar-brand pl-3" href="#">SynCro Fiskalizacija</a>
                <?php
                $page = substr($title, 11);
                $activeHome = '"';
                $activeAdmin = '"';
                $activeBills = '"';
                $activeTotals = '"';
                switch ($page) {
                    case 'Home':
                        $activeHome = 'active "';
                        break;
                    case 'Administracija':
                        $activeAdmin = 'active "';
                        break;
                    case 'Računi':
                        $activeBills = 'active "';
                        break;
                    case 'Totali':
                        $activeTotals = 'active "';
                        break;
                }
                if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
                        echo '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ml-auto">
                            <li class="nav-item' ?> <?php echo $activeHome; ?><?php echo '>
                                <a class="nav-link" href="home.php">Naslovnica</a>
                            </li>
                            <li class="nav-item' ?> <?php echo $activeAdmin; ?><?php echo '>
                                <a class="nav-link" href="administration.php">Administracija</a>
                            </li>
                            <li class="nav-item' ?> <?php echo $activeBills; ?><?php echo '>
                                <a class="nav-link" href="bills.php">Računi</a>
                            </li>
                            <li class="nav-item' ?> <?php echo $activeTotals; ?><?php echo '>
                                    <a class="nav-link" href="totals.php">Totali</a>
                            </li>
                            <li class="nav-item">
                                    <a class="nav-link" href="#logoutModal" data-toggle="modal"><i class="fas fa-user-tie"></i>'?>
                                    <?php echo $user->getData()->ime_korisnika; ?>
                                    <?php echo '</a>
                            </li>
                        </ul>
                    </div>';
                }
                ?>
            </nav>
    </div>