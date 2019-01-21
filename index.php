<?php
require 'core/init.php';

$user = new User();

if($user->check()){
	Redirect::to('home');
}

$validation = new Validation();
$user = new User();

$check_params = array(
	'username_korisnika' => array(
		'required' => true,
        'role' => 'Admin',
        'active' => true
	),
	'password_korisnika' => array(
		'required' => true,
	)
);

if (Input::exists()){
    if(Token::check(Input::get('csrf'))){
        $validate = $validation->check($check_params);
        if ($validate->passed()) {
            $login = $user->login(Input::get('username_korisnika'), Input::get('password_korisnika'));
			if($login){
                Redirect::to('home'); 
            }
            Session::flash('danger', 'Sorry, login faild! Please try again.');
			Redirect::to('index');
        }
    }
}
require_once 'notifications.php';

Helper::getHeader('SyncroF2', $user);
?>
    <form id="loginForm" method="POST">
        <div class="flex-fill d-flex justify-content-center pt-5">
            <div class="flex-column align-items-center">
                    <input type="hidden" name="csrf" value="<?php echo Token::generate(); ?>">
                    <div class="form-group body">
                        <label for="username_korisnika">Korisničko ime</label>
                        <input type="text" class="form-control" name="username_korisnika" id="username_korisnika" placeholder="Enter your email" autofocus autocomplete required/>
                        <?php echo ($validation->hasError('username_korisnika')) ?'<p class="text-danger">'.$validation->hasError('username_korisnika').'</p>' : '' ?>
                    </div>
                    <div class="form-group">
                        <label for="password_korisnika">Lozinka</label>
                        <input type="password" class="form-control" name="password_korisnika" id="password_korisnika" placeholder="Enter your password" required/>
                        <?php echo ($validation->hasError('password_korisnika')) ?'<p class="text-danger">'.$validation->hasError('password_korisnika').'</p>' : '' ?>
                    </div>
                    <!-- <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Zapamti me</label>
                    </div> -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
            </div>
        </div>
    </form>
    <?php  
Helper::getFooter('login');
?>
