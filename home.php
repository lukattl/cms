<?php

require 'core/init.php';

$user = new User();

if(!$user->check()){
    Redirect::to('index');
}
$fisUser = new FiskalUser();
$users = $fisUser->setUsers();
$pps = $fisUser->setPps();

Helper::getHeader('SyncroF2 | Home', $user);
?>

    <div class="container">
        <div class="jumbotron jumbotron-fluid">
                <div class="container">
                <div class="row">
                    <div class="col-12 col-md-8 col-lg-6 offset-lg-1">
                            <div class="col-12 opc-6">
                                <h3 class="shadow-lg p-3 mb-5 rounded walltext"><?php echo $user->getCompany()->naziv_tvrtke; ?></h3>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
Helper::getFooter('app');
Helper::getModal('logout', $user);
?>

